<?php

namespace App\Domains\Accounts\Models;

use App\Domains\Accounts\Enums\BusinessLinkType;
use Database\Factories\BusinessLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'url'])]
#[UseFactory(BusinessLinkFactory::class)]
class BusinessLink extends Model
{
    /** @use HasFactory<BusinessLinkFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BusinessLinkType::class,
        ];
    }

    /**
     * @return BelongsTo<BusinessProfile, $this>
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }
}
