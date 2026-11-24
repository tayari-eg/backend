<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Incentives\Models\TayarRank;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTayarRankRequest extends FormRequest
{
    use ValidatesUniqueTranslatedNames;

    /**
     * @var list<string>
     */
    private const array THRESHOLDS = ['min_delivered_orders_30d', 'min_rating'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Send only the fields that change; a name always needs both languages. The code and level never change.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->uniqueTranslatedNameRules('sometimes', TayarRank::class, 'admin.tayar_rank_name_taken', $this->route('tayarRank')?->id),
            'min_delivered_orders_30d' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'min_rating' => ['sometimes', 'nullable', 'decimal:0,2', 'min:1', 'max:5'],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * Each threshold sent must be at least the previous level's and at most the next level's; no rating needed counts
     * as 0. Runs after the field rules, also for a null min_rating (closure rules skip nullable nulls).
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $rank = $this->route('tayarRank');

            if (! $rank instanceof TayarRank) {
                return;
            }

            $previous = TayarRank::query()->where('level', '<', $rank->level)->orderByDesc('level')->first();
            $next = TayarRank::query()->where('level', '>', $rank->level)->orderBy('level')->first();

            foreach (self::THRESHOLDS as $field) {
                if (! $this->exists($field) || $validator->errors()->has($field)) {
                    continue;
                }

                $threshold = self::threshold($this->input($field));

                if ($previous !== null && $threshold->isLessThan(self::threshold($previous->getAttribute($field)))) {
                    $validator->errors()->add($field, __('admin.tayar_rank_below_previous', ['rank' => $previous->getTranslation('name', app()->getLocale())]));
                } elseif ($next !== null && $threshold->isGreaterThan(self::threshold($next->getAttribute($field)))) {
                    $validator->errors()->add($field, __('admin.tayar_rank_above_next', ['rank' => $next->getTranslation('name', app()->getLocale())]));
                }
            }
        }];
    }

    private static function threshold(mixed $value): BigDecimal
    {
        return BigDecimal::of($value === null ? '0' : (string) $value);
    }
}
