<?php

namespace App\Http\Requests\Concerns;

trait PaginatesResults
{
    /**
     * @return array<string, list<string>>
     */
    protected function paginationRules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('tayari.pagination.max_per_page')],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', config('tayari.pagination.per_page'));
    }
}
