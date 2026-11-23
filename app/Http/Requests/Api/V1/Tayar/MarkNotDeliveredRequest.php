<?php

namespace App\Http\Requests\Api\V1\Tayar;

use App\Http\Requests\Concerns\ValidatesNotDeliveredReason;

class MarkNotDeliveredRequest extends DeliverOrderRequest
{
    use ValidatesNotDeliveredReason;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->notDeliveredReasonRules(noteRequired: false),
        ];
    }
}
