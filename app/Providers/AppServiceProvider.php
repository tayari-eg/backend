<?php

namespace App\Providers;

use App\Contracts\PushSender;
use App\Contracts\SmsSender;
use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Policies\BusinessProfilePolicy;
use App\Domains\Accounts\Policies\MediaPolicy;
use App\Domains\Accounts\Policies\UserPolicy;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Policies\TayarProfilePolicy;
use App\Http\ClientContext;
use App\Models\User;
use App\Services\Push\FcmPushSender;
use App\Services\Push\LogPushSender;
use App\Services\Sms\LogSmsSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope is a dev dependency, so it must not be registered outside local.
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->scoped(ClientContext::class, fn (Application $app): ClientContext => ClientContext::fromRequest($app->make('request')));

        $this->app->bind(SmsSender::class, fn (): SmsSender => match (config('tayari.sms.driver')) {
            'log' => new LogSmsSender,
            default => throw new InvalidArgumentException('Unsupported SMS driver ['.config('tayari.sms.driver').'].'),
        });

        $this->app->bind(PushSender::class, fn (): PushSender => $this->makePushSender());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::define('viewApiDocs', fn (User $user): bool => in_array($user->email, config('scramble.allowed_emails'), true));

        Gate::before(fn (User $user): ?bool => $user->hasRole(StaffRole::SuperAdmin->value) ? true : null);

        Gate::policy(BusinessProfile::class, BusinessProfilePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(TayarProfile::class, TayarProfilePolicy::class);

        // Admin customer routes never reach staff and staff routes never reach customers (404). Bound here, not in
        // the route file, so the scopes still apply when routes are cached.
        Route::bind('customer', fn (string $value): User => User::query()->customers()->findOrFail($value));
        Route::bind('staff', fn (string $value): User => User::query()->staff()->findOrFail($value));
        Route::bind('tayar', fn (string $value): TayarProfile => TayarProfile::query()->approved()->findOrFail($value));

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $otpLimits = function (string $name, Request $request): array {
            $decayMinutes = config('tayari.otp.rate_limits.decay_minutes');
            $phone = $this->phoneLimiterKey($request);

            return [
                Limit::perMinutes($decayMinutes, config('tayari.otp.rate_limits.per_phone'))->by("{$name}:phone:{$phone}"),
                Limit::perMinutes($decayMinutes, config('tayari.otp.rate_limits.per_ip'))->by("{$name}:ip:{$request->ip()}"),
            ];
        };

        RateLimiter::for('otp-send', fn (Request $request): array => $otpLimits('otp-send', $request));
        RateLimiter::for('otp-verify', fn (Request $request): array => $otpLimits('otp-verify', $request));

        RateLimiter::for('tayar-location', fn (Request $request): Limit => Limit::perMinute(config('tayari.tayars.location_updates_per_minute'))
            ->by('tayar-location:'.$request->user()->id));

        RateLimiter::for('login', function (Request $request): array {
            $phone = $this->phoneLimiterKey($request);

            return [
                Limit::perMinute(config('tayari.auth.login_attempts_per_minute'))->by("login:{$phone}|{$request->ip()}"),
                Limit::perHour(config('tayari.auth.login_attempts_per_phone_per_hour'))->by("login:phone:{$phone}"),
            ];
        });
    }

    /**
     * Missing or unreadable credentials (local and tests) fall back to logging, so nothing crashes and nothing is sent.
     */
    private function makePushSender(): PushSender
    {
        $path = config('services.firebase.credentials');

        if (blank($path)) {
            return new LogPushSender('FIREBASE_CREDENTIALS is empty');
        }

        if (! is_file($path) || ! is_readable($path)) {
            return new LogPushSender('the FIREBASE_CREDENTIALS file is missing or unreadable');
        }

        $credentials = json_decode((string) file_get_contents($path), true);

        if (! is_array($credentials) || ! isset($credentials['project_id'], $credentials['client_email'], $credentials['private_key'])) {
            return new LogPushSender('the FIREBASE_CREDENTIALS file is not a service account JSON');
        }

        return new FcmPushSender($credentials);
    }

    /**
     * Runs before validation, so the input may be anything (e.g. phone[]=x); only a scalar is ever cast to a string.
     */
    private function phoneLimiterKey(Request $request): string
    {
        $phone = $request->input('phone');

        return PhoneNumber::tryFrom($phone)?->value ?? (is_scalar($phone) ? (string) $phone : '');
    }
}
