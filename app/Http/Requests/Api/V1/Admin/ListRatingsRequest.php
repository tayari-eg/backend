<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Orders\Enums\RaterRole;
use App\Http\Requests\Concerns\PaginatesResults;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRatingsRequest extends FormRequest
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
            'rater_role' => ['sometimes', Rule::enum(RaterRole::class)],
            'rated_user_id' => ['sometimes', 'integer', 'min:1'],
            'rater_id' => ['sometimes', 'integer', 'min:1'],
            'order_id' => ['sometimes', 'integer', 'min:1'],
            'stars' => ['sometimes', 'integer', 'min:1', 'max:5'],
            // Query strings send booleans as text.
            'has_comment' => ['sometimes', 'in:true,false,1,0'],
            'comment_hidden' => ['sometimes', 'in:true,false,1,0'],
            'created_from' => ['sometimes', 'date_format:'.self::DATE_FORMAT],
            'created_to' => ['sometimes', 'date_format:'.self::DATE_FORMAT, 'after_or_equal:created_from'],
            ...$this->paginationRules(),
        ];
    }

    public function raterRole(): ?RaterRole
    {
        return RaterRole::tryFrom((string) $this->validated('rater_role'));
    }

    public function integerFilter(string $field): ?int
    {
        return $this->has($field) ? (int) $this->validated($field) : null;
    }

    public function booleanFilter(string $field): ?bool
    {
        return $this->has($field) ? $this->boolean($field) : null;
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

    private function businessDay(string $field): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(self::DATE_FORMAT, (string) $this->validated($field), config('app.business_timezone'));
    }
}
