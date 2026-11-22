<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\PaginatesResults;
use App\Http\Requests\Concerns\SearchesUsers;
use Illuminate\Foundation\Http\FormRequest;

class ListTayarsRequest extends FormRequest
{
    use PaginatesResults, SearchesUsers;

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
            // Query strings send booleans as text.
            'is_online' => ['sometimes', 'in:true,false,1,0'],
            'blocked' => ['sometimes', 'in:true,false,1,0'],
            'vehicle_type_id' => ['sometimes', 'integer'],
            ...$this->userSearchRules(),
            ...$this->paginationRules(),
        ];
    }

    public function isOnline(): ?bool
    {
        return $this->has('is_online') ? $this->boolean('is_online') : null;
    }

    public function blocked(): ?bool
    {
        return $this->has('blocked') ? $this->boolean('blocked') : null;
    }
}
