<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpCodePruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_codes_used_or_expired_more_than_a_day_ago_are_prunable(): void
    {
        $usedLongAgo = OtpCode::factory()->create(['used_at' => now()->subDays(2), 'expires_at' => now()->subDays(2)->addMinutes(5)]);
        $expiredLongAgo = OtpCode::factory()->create(['expires_at' => now()->subDays(2)]);
        $usedToday = OtpCode::factory()->create(['used_at' => now()->subHour(), 'expires_at' => now()->addMinutes(4)]);
        $expiredToday = OtpCode::factory()->create(['expires_at' => now()->subHours(3)]);
        $open = OtpCode::factory()->create();

        $prunable = (new OtpCode)->prunable()->pluck('id')->sort()->values()->all();

        $this->assertSame([$usedLongAgo->id, $expiredLongAgo->id], $prunable);

        $this->artisan('model:prune', ['--model' => [OtpCode::class]])->assertSuccessful();

        $this->assertModelMissing($usedLongAgo);
        $this->assertModelMissing($expiredLongAgo);
        $this->assertModelExists($usedToday);
        $this->assertModelExists($expiredToday);
        $this->assertModelExists($open);
    }
}
