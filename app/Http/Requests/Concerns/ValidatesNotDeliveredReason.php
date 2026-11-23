<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Orders\Enums\OrderReasonType;
use App\Domains\Orders\Models\OrderReason;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait ValidatesNotDeliveredReason
{
    /**
     * @return array<string, mixed>
     */
    protected function notDeliveredReasonRules(bool $noteRequired): array
    {
        return [
            'not_delivered_reason_id' => ['required', 'integer', Rule::exists(OrderReason::class, 'id')->where(fn (Builder $query) => $query
                ->where('type', OrderReasonType::NotDelivered->value)
                ->where('is_active', true))],
            'note' => [$noteRequired ? 'required' : 'sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function notDeliveredReason(): OrderReason
    {
        return OrderReason::query()->findOrFail((int) $this->validated('not_delivered_reason_id'));
    }

    public function note(): ?string
    {
        $note = trim((string) $this->validated('note'));

        return $note === '' ? null : $note;
    }
}
