<?php

namespace App\Http\Requests\Api\V1\Tayar;

use App\Http\Requests\Concerns\ValidatesRating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RateOrderRequest extends FormRequest
{
    use ValidatesRating;

    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('rateAsTayar', $this->route('order'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->ratingRules();
    }
}
