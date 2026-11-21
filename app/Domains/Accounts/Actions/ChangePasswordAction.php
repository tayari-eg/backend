<?php

namespace App\Domains\Accounts\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangePasswordAction
{
    /**
     * Keeps the token used for this request and deletes the user's other tokens.
     */
    public function handle(User $user, string $password, ?int $currentTokenId): void
    {
        DB::transaction(function () use ($user, $password, $currentTokenId): void {
            $user->forceFill(['password' => $password])->save();

            $user->tokens()
                ->when($currentTokenId !== null, fn ($query) => $query->whereKeyNot($currentTokenId))
                ->delete();
        });
    }
}
