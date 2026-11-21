<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Domains\Accounts\Enums\BusinessLinkType;
use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\BusinessType;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreBusinessProfileRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::forUser($this->user())->inspect('create', BusinessProfile::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $document = ['nullable', 'file', 'mimes:'.implode(',', config('tayari.media.allowed_mimes')), 'max:'.config('tayari.media.max_upload_kb')];

        return [
            'business_type_id' => ['required', 'integer', Rule::exists(BusinessType::class, 'id')->where(fn (Builder $query) => $query->where('is_active', true))],
            'business_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'avg_daily_orders' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'tax_registration_number' => ['nullable', 'string', 'max:50'],
            'links' => ['sometimes', 'array', 'max:10'],
            'links.*.type' => ['required', Rule::enum(BusinessLinkType::class)],
            'links.*.url' => ['required', 'url', 'max:2048'],
            DocumentCollection::NationalIdFront->value => ['required', ...array_slice($document, 1)],
            DocumentCollection::NationalIdBack->value => ['required', ...array_slice($document, 1)],
            DocumentCollection::TaxRegister->value => $document,
            DocumentCollection::TaxCard->value => $document,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        return $this->safe()->only(['business_type_id', 'business_name', 'description', 'avg_daily_orders', 'tax_registration_number']);
    }

    /**
     * @return array<string, UploadedFile>
     */
    public function documents(): array
    {
        $documents = [];

        foreach (DocumentCollection::forBusinessProfile() as $collection) {
            if ($this->hasFile($collection->value)) {
                $documents[$collection->value] = $this->file($collection->value);
            }
        }

        return $documents;
    }

    /**
     * @return list<array{type: string, url: string}>
     */
    public function links(): array
    {
        return array_values($this->validated('links', []));
    }
}
