<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Catalog\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryIconRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Raster images only: an SVG can carry scripts and the icon is served from the public disk.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'icon' => ['required', 'file', 'image', 'mimes:'.implode(',', Category::ICON_MIMES), 'max:'.config('tayari.media.max_upload_kb')],
        ];
    }
}
