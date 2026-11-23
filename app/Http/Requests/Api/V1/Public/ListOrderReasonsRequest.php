<?php

namespace App\Http\Requests\Api\V1\Public;

use App\Domains\Orders\Enums\OrderReasonType;
use App\Http\Requests\Concerns\PaginatesResults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrderReasonsRequest extends FormRequest
{
    use PaginatesResults;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(OrderReasonType::class)],
            ...$this->paginationRules(),
        ];
    }

    public function type(): OrderReasonType
    {
        return OrderReasonType::from((string) $this->validated('type'));
    }
}
