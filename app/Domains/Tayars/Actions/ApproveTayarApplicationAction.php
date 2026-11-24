<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Incentives\Actions\AssignStartingRankAction;
use App\Domains\Tayars\Enums\ReviewDecision;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Enums\VehicleStatus;
use App\Domains\Tayars\Events\TayarApplicationApproved;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Wallet\Actions\EnsureWalletsAction;
use App\Domains\Wallet\Actions\PostSignupBonusAction;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\WalletType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveTayarApplicationAction
{
    public function __construct(
        private EnsureWalletsAction $ensureWallets,
        private PostSignupBonusAction $postSignupBonus,
        private AssignStartingRankAction $assignStartingRank,
    ) {}

    /**
     * Approves the application with its vehicle, which becomes the active one, and records the review. A first approval
     * posts the signup bonus in the same transaction (a tayar sent back to review and approved again gets none); its
     * wallets are created before the transaction opens. A tayar without a rank gets the lowest one.
     *
     * @throws ValidationException when the application is not pending
     */
    public function handle(TayarProfile $profile, User $reviewer): TayarProfile
    {
        $this->ensureWallets->handle([[$profile->user_id, WalletType::Tayar]], [SystemWallet::BonusExpense]);

        $profile = DB::transaction(function () use ($profile, $reviewer): TayarProfile {
            $profile = TayarProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if (! $profile->isPending()) {
                throw ValidationException::withMessages(['status' => __('tayars.not_pending')]);
            }

            $isFirstApproval = ! $profile->reviews()->where('decision', ReviewDecision::Approved)->exists();

            $profile->forceFill([
                'status' => TayarStatus::Approved,
                'rejection_reason' => null,
                'can_reapply_at' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $profile->vehicles()->update(['status' => VehicleStatus::Approved]);
            $profile->vehicle?->forceFill(['is_active' => true])->save();

            $profile->reviews()->create(['decision' => ReviewDecision::Approved, 'reviewed_by' => $reviewer->id]);

            if ($isFirstApproval) {
                $this->postSignupBonus->handle($profile);
            }

            $this->assignStartingRank->handle($profile);

            return $profile;
        });

        TayarApplicationApproved::dispatch($profile);

        return $profile;
    }
}
