<?php

namespace App\Models;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Accounts\Enums\AppMode;
use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Enums\OnboardingStep;
use App\Domains\Accounts\Enums\RegisterAs;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\UserDevice;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\Concerns\EscapesLikeValues;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'phone', 'email', 'password', 'account_type', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use EscapesLikeValues, HasApiTokens, HasFactory, HasRoles, InteractsWithMedia, Notifiable;

    /**
     * Guard used by spatie/laravel-permission, so roles resolve the same
     * for session (web) and Sanctum token requests.
     */
    protected $guard_name = 'web';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'account_type' => AccountType::Individual->value,
        'locale' => Locale::Ar->value,
        'status' => UserStatus::Active->value,
        'last_mode' => AppMode::Client->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'id_verified_at' => 'datetime',
            'id_reviewed_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
            'register_as' => RegisterAs::class,
            'locale' => Locale::class,
            'status' => UserStatus::class,
            'last_mode' => AppMode::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        foreach (DocumentCollection::forNationalId() as $collection) {
            $this->addMediaCollection($collection->value)
                ->useDisk(config('tayari.media.private_disk'))
                ->singleFile();
        }
    }

    /**
     * @return HasOne<BusinessProfile, $this>
     */
    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    /**
     * @return HasOne<TayarProfile, $this>
     */
    public function tayarProfile(): HasOne
    {
        return $this->hasOne(TayarProfile::class);
    }

    /**
     * @return HasMany<UserDevice, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function idReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_reviewed_by');
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function isBlocked(): bool
    {
        return $this->status === UserStatus::Blocked;
    }

    public function isBusiness(): bool
    {
        return $this->account_type === AccountType::Business;
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(StaffRole::values());
    }

    /**
     * Customer and business accounts: users without a staff role.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function customers(Builder $query): void
    {
        $query->whereDoesntHave('roles', fn (Builder $roles) => $roles->whereIn('name', StaffRole::values()));
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function staff(Builder $query): void
    {
        $query->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', StaffRole::values()));
    }

    /**
     * The admin search: an exact phone, part of a phone, or part of the name (at most one is given).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereMatchesSearch(Builder $query, ?PhoneNumber $phone, ?string $phoneDigits, ?string $name): void
    {
        $query
            ->when($phone, fn (Builder $query) => $query->where('phone', $phone->value))
            ->when($phoneDigits, fn (Builder $query) => $query->whereLike('phone', static::likeContains($phoneDigits)))
            ->when($name, fn (Builder $query) => $query->whereLike('name', static::likeContains($name)));
    }

    /**
     * The same rules as nationalIdStatus(); null means no national ID was uploaded.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereNationalIdStatus(Builder $query, ?VerificationStatus $status): void
    {
        $hasFront = fn (Builder $media) => $media->where('collection_name', DocumentCollection::NationalIdFront->value);

        match ($status) {
            VerificationStatus::Approved => $query->whereNotNull('id_verified_at'),
            VerificationStatus::Rejected => $query->whereNull('id_verified_at')->whereNotNull('id_reviewed_at'),
            VerificationStatus::Pending => $query->whereNull('id_verified_at')->whereNull('id_reviewed_at')->whereHas('media', $hasFront),
            null => $query->whereNull('id_verified_at')->whereNull('id_reviewed_at')->whereDoesntHave('media', $hasFront),
        };
    }

    /**
     * The onboarding path the user is on: what they registered as, or for accounts from before that choice, a business
     * account type or an existing tayar application. Null means no path (staff, or an older individual customer).
     */
    public function onboardingPath(): ?RegisterAs
    {
        if ($this->register_as !== null) {
            return $this->register_as;
        }

        return match (true) {
            $this->isBusiness() => RegisterAs::Business,
            $this->tayarProfile !== null => RegisterAs::Tayar,
            default => null,
        };
    }

    /**
     * The screen to open after login. Only the path the user registered for counts; an application on the other side
     * shows in its own status. Reads the businessProfile, tayarProfile and roles relations.
     */
    public function onboardingStep(): OnboardingStep
    {
        if ($this->isStaff()) {
            return OnboardingStep::Ready;
        }

        return match ($this->onboardingPath()) {
            RegisterAs::Business => match ($this->businessProfile?->verification_status) {
                null => OnboardingStep::CompleteBusinessProfile,
                VerificationStatus::Pending => OnboardingStep::BusinessUnderReview,
                VerificationStatus::Rejected => OnboardingStep::BusinessRejected,
                VerificationStatus::Approved => OnboardingStep::Ready,
            },
            RegisterAs::Tayar => match ($this->tayarProfile?->status) {
                null => OnboardingStep::CompleteTayarApplication,
                TayarStatus::Pending => OnboardingStep::TayarUnderReview,
                TayarStatus::Rejected => OnboardingStep::TayarRejected,
                TayarStatus::Approved => OnboardingStep::Ready,
            },
            null => OnboardingStep::Ready,
        };
    }

    /**
     * Pending means documents were uploaded and not reviewed yet; null means nothing was uploaded.
     */
    public function nationalIdStatus(): ?VerificationStatus
    {
        return match (true) {
            $this->id_verified_at !== null => VerificationStatus::Approved,
            $this->id_reviewed_at !== null => VerificationStatus::Rejected,
            $this->hasMedia(DocumentCollection::NationalIdFront->value) => VerificationStatus::Pending,
            default => null,
        };
    }
}
