<?php

namespace App\Http\Requests\Api\V1\Tayar;

use Illuminate\Foundation\Http\FormRequest;

class TayarPositionRequest extends FormRequest
{
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
            'lat' => ['required', 'numeric', 'decimal:0,7', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'decimal:0,7', 'between:-180,180'],
        ];
    }

    public function lat(): float
    {
        return (float) $this->validated('lat');
    }

    public function lng(): float
    {
        return (float) $this->validated('lng');
    }
}
