<?php

namespace Tests\Unit\Domains\Accounts;

use App\Domains\Accounts\Models\OtpCode;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Uses the app only for Eloquent's date casts; no database is touched.
 */
class OtpCodeRulesTest extends TestCase
{
    private function otpCode(array $attributes = []): OtpCode
    {
        $now = Carbon::parse('2026-11-21 10:00:00');

        return (new OtpCode)->forceFill([
            'attempts' => 0,
            'expires_at' => $now->copy()->addMinutes(5),
            'used_at' => null,
            'created_at' => $now,
            ...$attributes,
        ]);
    }

    public function test_a_fresh_code_can_be_used(): void
    {
        $this->assertTrue($this->otpCode()->canBeUsed(Carbon::parse('2026-11-21 10:04:59'), 5));
    }

    public function test_a_code_expires_at_its_expiry_time(): void
    {
        $otpCode = $this->otpCode();

        $this->assertFalse($otpCode->isExpired(Carbon::parse('2026-11-21 10:04:59')));
        $this->assertTrue($otpCode->isExpired(Carbon::parse('2026-11-21 10:05:00')));
        $this->assertFalse($otpCode->canBeUsed(Carbon::parse('2026-11-21 10:05:00'), 5));
    }

    public function test_a_code_is_blocked_after_the_max_attempts(): void
    {
        $now = Carbon::parse('2026-11-21 10:01:00');

        $this->assertTrue($this->otpCode(['attempts' => 4])->hasAttemptsLeft(5));
        $this->assertFalse($this->otpCode(['attempts' => 5])->hasAttemptsLeft(5));
        $this->assertFalse($this->otpCode(['attempts' => 5])->canBeUsed($now, 5));
    }

    public function test_a_used_code_cannot_be_used_again(): void
    {
        $otpCode = $this->otpCode(['used_at' => Carbon::parse('2026-11-21 10:01:00')]);

        $this->assertTrue($otpCode->isUsed());
        $this->assertFalse($otpCode->canBeUsed(Carbon::parse('2026-11-21 10:02:00'), 5));
    }

    public function test_the_resend_cooldown_counts_from_when_the_code_was_created(): void
    {
        $otpCode = $this->otpCode();

        $this->assertSame(60, $otpCode->secondsUntilResendAllowed(Carbon::parse('2026-11-21 10:00:00'), 60));
        $this->assertSame(15, $otpCode->secondsUntilResendAllowed(Carbon::parse('2026-11-21 10:00:45'), 60));
        $this->assertSame(0, $otpCode->secondsUntilResendAllowed(Carbon::parse('2026-11-21 10:01:00'), 60));
        $this->assertSame(0, $otpCode->secondsUntilResendAllowed(Carbon::parse('2026-11-21 10:05:00'), 60));
    }
}
