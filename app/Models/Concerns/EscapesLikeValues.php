<?php

namespace App\Models\Concerns;

trait EscapesLikeValues
{
    /**
     * A "contains" pattern where %, _ and \ in the search are literal. Backslash is the default like escape on both
     * MySQL and PostgreSQL.
     */
    protected static function likeContains(string $value): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value).'%';
    }
}
