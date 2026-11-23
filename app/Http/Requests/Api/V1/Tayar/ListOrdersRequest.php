<?php

namespace App\Http\Requests\Api\V1\Tayar;

use App\Domains\Orders\Enums\OrderStatus;
use App\Http\Requests\Concerns\PaginatesResults;
use Illuminate\Foundation\Http\FormRequest;

class ListOrdersRequest extends FormRequest
{
    use PaginatesResults;

    public const string ACTIVE = 'active';

    public const string PAST = 'past';

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
            'status' => ['sometimes', 'in:'.self::ACTIVE.','.self::PAST],
            'cursor' => ['sometimes', 'string', 'max:1000'],
            ...$this->paginationRules(),
        ];
    }

    /**
     * Active: accepted and not finished. Past: delivered, not delivered or cancelled.
     *
     * @return list<OrderStatus>
     */
    public function statuses(): array
    {
        return $this->validated('status', self::ACTIVE) === self::PAST ? OrderStatus::pastCases() : OrderStatus::withTayarCases();
    }
}
