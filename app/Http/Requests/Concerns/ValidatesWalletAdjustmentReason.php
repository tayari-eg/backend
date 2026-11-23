<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Wallet\Models\WalletAdjustmentReason;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait ValidatesWalletAdjustmentReason
{
    /**
     * @return array<string, mixed>
     */
    protected function adjustmentReasonRules(): array
    {
        return [
            'reason_id' => ['required', 'integer', Rule::exists(WalletAdjustmentReason::class, 'id')->where(fn (Builder $query) => $query->where('is_active', true))],
            'note' => ['required', 'string', 'max:500'],
        ];
    }

    public function adjustmentReason(): WalletAdjustmentReason
    {
        return WalletAdjustmentReason::query()->findOrFail((int) $this->validated('reason_id'));
    }

    public function note(): string
    {
        return trim((string) $this->validated('note'));
    }
}
