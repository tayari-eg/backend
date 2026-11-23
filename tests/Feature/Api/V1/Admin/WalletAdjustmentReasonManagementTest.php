<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Wallet\Actions\AdjustWalletAction;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletAdjustmentReasonManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = $this->createStaffUser(StaffRole::Admin);
        Sanctum::actingAs($this->staff);
    }

    public function test_the_list_shows_active_and_inactive_reasons_in_sort_order_and_filters(): void
    {
        $second = WalletAdjustmentReason::factory()->inactive()->create(['sort_order' => 2, 'name' => ['ar' => 'غرامة', 'en' => 'Penalty']]);
        $first = WalletAdjustmentReason::factory()->create(['sort_order' => 1]);

        $this->getJson(route('api.v1.admin.wallet-adjustment-reasons.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.postings_count', 0)
            ->assertJsonPath('data.1.name', ['ar' => 'غرامة', 'en' => 'Penalty'])
            ->assertJsonPath('meta.total', 2);

        $this->getJson(route('api.v1.admin.wallet-adjustment-reasons.index', ['is_active' => 'false']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $second->id);
        $this->getJson(route('api.v1.admin.wallet-adjustment-reasons.index', ['search' => 'PENAL']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $second->id);
        $this->getJson(route('api.v1.admin.wallet-adjustment-reasons.show', $first))->assertOk()->assertJsonPath('data.id', $first->id);
    }

    public function test_a_reason_is_created_at_the_end_with_unique_names_and_updated_with_only_the_fields_sent(): void
    {
        WalletAdjustmentReason::factory()->create(['sort_order' => 4, 'name' => ['ar' => 'تصحيح', 'en' => 'Correction']]);

        $id = $this->postJson(route('api.v1.admin.wallet-adjustment-reasons.store'), ['name' => ['ar' => 'تعويض', 'en' => 'Compensation']])
            ->assertCreated()
            ->assertJsonPath('data.sort_order', 5)
            ->assertJsonPath('data.is_active', true)
            ->json('data.id');

        $this->postJson(route('api.v1.admin.wallet-adjustment-reasons.store'), ['name' => ['ar' => 'آخر', 'en' => 'correction']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name.en');
        $this->postJson(route('api.v1.admin.wallet-adjustment-reasons.store'), ['name' => ['en' => 'Only English']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name.ar');

        $this->patchJson(route('api.v1.admin.wallet-adjustment-reasons.update', $id), ['sort_order' => 1])
            ->assertOk()
            ->assertJsonPath('data.sort_order', 1)
            ->assertJsonPath('data.name.en', 'Compensation');
    }

    public function test_deactivate_and_activate(): void
    {
        $reason = WalletAdjustmentReason::factory()->create();

        $this->postJson(route('api.v1.admin.wallet-adjustment-reasons.deactivate', $reason))->assertOk()->assertJsonPath('data.is_active', false);
        $this->postJson(route('api.v1.admin.wallet-adjustment-reasons.activate', $reason))->assertOk()->assertJsonPath('data.is_active', true);
    }

    public function test_an_unused_reason_is_deleted_and_a_used_one_is_not(): void
    {
        $unused = WalletAdjustmentReason::factory()->create();
        $used = WalletAdjustmentReason::factory()->create();
        app(AdjustWalletAction::class)->handle(Wallet::factory()->create(), BigDecimal::of('1.00'), $used, 'Test', (string) Str::uuid(), $this->staff);

        $this->deleteJson(route('api.v1.admin.wallet-adjustment-reasons.destroy', $unused))->assertNoContent();
        $this->deleteJson(route('api.v1.admin.wallet-adjustment-reasons.destroy', $used))
            ->assertUnprocessable()
            ->assertJsonPath('errors.wallet_adjustment_reason.0', __('admin.wallet_adjustment_reason_in_use'));

        $this->assertModelMissing($unused);
        $this->assertModelExists($used);
    }

    public function test_support_can_view_the_reasons_but_not_change_them(): void
    {
        Sanctum::actingAs(User::factory()->create()->assignRole(StaffRole::Support->value));

        $this->getJson(route('api.v1.admin.wallet-adjustment-reasons.index'))->assertOk();
        $this->postJson(route('api.v1.admin.wallet-adjustment-reasons.store'), ['name' => ['ar' => 'أ', 'en' => 'A']])->assertForbidden();
    }
}
