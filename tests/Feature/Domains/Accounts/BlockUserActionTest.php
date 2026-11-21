<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Actions\BlockUserAction;
use App\Domains\Accounts\Actions\UnblockUserAction;
use App\Domains\Accounts\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockUserActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocking_a_user_stores_the_reason_and_deletes_every_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('phone');
        $user->createToken('tablet');
        $otherUser = User::factory()->create();
        $otherUser->createToken('phone');

        app(BlockUserAction::class)->handle($user, 'Fraud reports');

        $user->refresh();
        $this->assertSame(UserStatus::Blocked, $user->status);
        $this->assertSame('Fraud reports', $user->blocked_reason);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $otherUser->id]);
    }

    public function test_unblocking_a_user_makes_the_account_active_again(): void
    {
        $user = User::factory()->blocked()->create();

        app(UnblockUserAction::class)->handle($user);

        $user->refresh();
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNull($user->blocked_reason);
    }
}
