<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesPricingRuleFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRuleRequest extends FormRequest
{
    use ValidatesPricingRuleFields;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Send only the fields that change.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->pricingRuleRules('sometimes');
    }
}
