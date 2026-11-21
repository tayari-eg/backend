<?php

namespace App\Domains\Accounts\Models;

use App\Domains\Accounts\Enums\OtpPurpose;
use Carbon\CarbonInterface;
use Database\Factories\OtpCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

#[Fillable(['phone', 'purpose', 'code_hash', 'attempts', 'expires_at', 'used_at'])]
#[Hidden(['code_hash'])]
#[UseFactory(OtpCodeFactory::class)]
class OtpCode extends Model
{
    /** @use HasFactory<OtpCodeFactory> */
    use HasFactory, Prunable;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Codes used or expired more than a day ago; removed daily by model:prune.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $cutoff = now()->subDay();

        return static::query()->where(fn (Builder $query) => $query
            ->where('used_at', '<', $cutoff)
            ->orWhere('expires_at', '<', $cutoff));
    }

    public function isExpired(CarbonInterface $now): bool
    {
        return $this->expires_at->lessThanOrEqualTo($now);
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function hasAttemptsLeft(int $maxAttempts): bool
    {
        return $this->attempts < $maxAttempts;
    }

    public function canBeUsed(CarbonInterface $now, int $maxAttempts): bool
    {
        return ! $this->isUsed() && ! $this->isExpired($now) && $this->hasAttemptsLeft($maxAttempts);
    }

    /**
     * Seconds left before a new code may be sent for the same phone and purpose; 0 when allowed now.
     */
    public function secondsUntilResendAllowed(CarbonInterface $now, int $cooldownSeconds): int
    {
        $allowedAt = $this->created_at->copy()->addSeconds($cooldownSeconds);

        return $allowedAt->greaterThan($now) ? (int) ceil($now->diffInSeconds($allowedAt)) : 0;
    }
}
