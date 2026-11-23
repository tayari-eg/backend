<?php

namespace App\Http\Requests\Api\V1\Tayar;

class DeliverOrderRequest extends TayarPositionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'code' => ['required', 'string', 'digits:'.config()->integer('tayari.tracking.code_length')],
        ];
    }

    public function code(): string
    {
        return (string) $this->validated('code');
    }
}
