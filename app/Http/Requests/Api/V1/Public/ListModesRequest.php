<?php

namespace App\Http\Requests\Api\V1\Public;

use App\Http\Requests\Concerns\PaginatesResults;
use Illuminate\Foundation\Http\FormRequest;

class ListModesRequest extends FormRequest
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
        return $this->paginationRules();
    }
}
