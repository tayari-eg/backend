<?php

namespace App\Http\Requests\Concerns;

trait ValidatesRating
{
    /**
     * @return array<string, list<string>>
     */
    protected function ratingRules(): array
    {
        return [
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function stars(): int
    {
        return (int) $this->validated('stars');
    }

    /**
     * The comment, or null when it is left out or blank.
     */
    public function comment(): ?string
    {
        $comment = trim((string) $this->validated('comment'));

        return $comment === '' ? null : $comment;
    }
}
