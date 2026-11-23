<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Http\Requests\Concerns\PaginatesResults;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrdersRequest extends FormRequest
{
    use PaginatesResults;

    private const string DATE_FORMAT = 'Y-m-d';

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
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'sender_id' => ['sometimes', 'integer', 'min:1'],
            'category_id' => ['sometimes', 'integer', 'min:1'],
            'mode_id' => ['sometimes', 'integer', 'min:1'],
            'created_from' => ['sometimes', 'date_format:'.self::DATE_FORMAT],
            'created_to' => ['sometimes', 'date_format:'.self::DATE_FORMAT, 'after_or_equal:created_from'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            ...$this->paginationRules(),
        ];
    }

    public function status(): ?OrderStatus
    {
        return OrderStatus::tryFrom((string) $this->validated('status'));
    }

    public function integerFilter(string $field): ?int
    {
        return $this->has($field) ? (int) $this->validated($field) : null;
    }

    /**
     * The start of the given business day, in UTC.
     */
    public function createdFrom(): ?CarbonImmutable
    {
        return $this->has('created_from') ? $this->businessDay('created_from')->startOfDay()->utc() : null;
    }

    /**
     * The end of the given business day, in UTC.
     */
    public function createdTo(): ?CarbonImmutable
    {
        return $this->has('created_to') ? $this->businessDay('created_to')->endOfDay()->utc() : null;
    }

    /**
     * A full Egyptian mobile number, matched against the receiver's and the sender's phone.
     */
    public function searchPhone(): ?PhoneNumber
    {
        return PhoneNumber::tryFrom($this->searchTerm());
    }

    /**
     * Anything that is not a phone is read as an order code, with or without the TY- prefix, any letter case.
     */
    public function searchCode(): ?string
    {
        $search = $this->searchTerm();

        if ($search === null || $this->searchPhone() !== null) {
            return null;
        }

        $code = strtoupper($search);

        return str_starts_with($code, Order::CODE_PREFIX) ? $code : Order::CODE_PREFIX.$code;
    }

    private function searchTerm(): ?string
    {
        $search = trim(PhoneNumber::westernDigits((string) $this->validated('search')));

        return $search === '' ? null : $search;
    }

    private function businessDay(string $field): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(self::DATE_FORMAT, (string) $this->validated($field), config('app.business_timezone'));
    }
}
