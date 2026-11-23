<?php

namespace App\Http\Requests\Api\V1\Tayar;

use App\Domains\Orders\Models\Order;
use Illuminate\Http\UploadedFile;

class ConfirmPickupRequest extends TayarPositionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'photos' => ['required', 'array', 'min:1', 'max:'.Order::MAX_PICKUP_PHOTOS],
            'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('tayari.media.max_upload_kb')],
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function photos(): array
    {
        return array_values($this->file('photos', []));
    }
}
