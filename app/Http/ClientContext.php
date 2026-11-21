<?php

namespace App\Http;

use App\Domains\Accounts\Enums\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The calling client's platform and app version, read from the X-Platform and X-App-Version headers.
 * Bound as a scoped service so it never leaks between requests under Octane.
 */
final readonly class ClientContext
{
    public function __construct(
        public ?Platform $platform,
        public ?string $appVersion,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $platform = Platform::tryFrom(Str::lower(trim((string) $request->header('X-Platform'))));
        $appVersion = trim((string) $request->header('X-App-Version'));

        return new self($platform, $appVersion === '' ? null : $appVersion);
    }

    public function isBelowMinimumVersion(?string $minimumVersion): bool
    {
        if (! $this->platform?->isMobileApp() || $this->appVersion === null || blank($minimumVersion)) {
            return false;
        }

        // Flutter versions can carry a "+build" suffix, which version_compare() does not understand.
        return version_compare(Str::before($this->appVersion, '+'), Str::before($minimumVersion, '+'), '<');
    }
}
