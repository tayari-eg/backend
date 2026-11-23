<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesNotDeliveredReason;
use Illuminate\Foundation\Http\FormRequest;

class MarkNotDeliveredRequest extends FormRequest
{
    use ValidatesNotDeliveredReason;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->notDeliveredReasonRules(noteRequired: true);
    }

    public function requiredNote(): string
    {
        return (string) $this->note();
    }
}
