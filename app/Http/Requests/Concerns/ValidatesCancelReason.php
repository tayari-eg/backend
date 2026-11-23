<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Orders\Enums\OrderReasonType;
use App\Domains\Orders\Models\OrderReason;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait ValidatesCancelReason
{
    /**
     * @return array<string, mixed>
     */
    protected function cancelReasonRules(): array
    {
        return [
            'cancel_reason_id' => ['required', 'integer', Rule::exists(OrderReason::class, 'id')->where(fn (Builder $query) => $query
                ->where('type', OrderReasonType::Cancel->value)
                ->where('is_active', true))],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function cancelReason(): OrderReason
    {
        return OrderReason::query()->findOrFail((int) $this->validated('cancel_reason_id'));
    }

    public function note(): ?string
    {
        $note = trim((string) $this->validated('note'));

        return $note === '' ? null : $note;
    }
}
