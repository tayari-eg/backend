<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Events\NationalIdSubmitted;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class SubmitNationalIdAction
{
    /**
     * A new upload replaces both sides and clears the previous review. Files are written outside any DB transaction;
     * if the back fails, the new front is removed so no orphan file is left.
     *
     * @throws Throwable when a file cannot be stored
     */
    public function handle(User $user, UploadedFile $front, UploadedFile $back): User
    {
        /** @var Media|null $frontMedia */
        $frontMedia = null;

        try {
            $frontMedia = $user->addMedia($front)->toMediaCollection(DocumentCollection::NationalIdFront->value);
            $user->addMedia($back)->toMediaCollection(DocumentCollection::NationalIdBack->value);
        } catch (Throwable $exception) {
            $frontMedia?->delete();

            throw $exception;
        }

        $user->forceFill([
            'id_verified_at' => null,
            'id_rejection_reason' => null,
            'id_reviewed_by' => null,
            'id_reviewed_at' => null,
        ])->save();

        NationalIdSubmitted::dispatch($user);

        return $user;
    }
}
