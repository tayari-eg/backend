<?php

namespace App\Domains\Accounts\Models;

use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Models\User;
use Database\Factories\BusinessProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['business_type_id', 'business_name', 'description', 'avg_daily_orders', 'tax_registration_number'])]
#[UseFactory(BusinessProfileFactory::class)]
class BusinessProfile extends Model implements HasMedia
{
    /** @use HasFactory<BusinessProfileFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Changing one of these sends an approved or rejected profile back to review.
     *
     * @var list<string>
     */
    public const array REVIEWED_ATTRIBUTES = ['business_name', 'business_type_id', 'tax_registration_number'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'verification_status' => VerificationStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verification_status' => VerificationStatus::class,
            'avg_daily_orders' => 'integer',
            'reviewed_at' => 'datetime',
            'rating_avg' => 'decimal:2',
            'ratings_count_30d' => 'integer',
            'rating_updated_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        foreach (DocumentCollection::forBusinessProfile() as $collection) {
            $this->addMediaCollection($collection->value)
                ->useDisk(config('tayari.media.private_disk'))
                ->singleFile();
        }
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<BusinessType, $this>
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * @return HasMany<BusinessLink, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(BusinessLink::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->verification_status === VerificationStatus::Pending;
    }

    /**
     * Clears the previous decision so the profile is reviewed again. Returns whether the status changed.
     */
    public function returnToPendingReview(): bool
    {
        if ($this->isPending()) {
            return false;
        }

        $this->verification_status = VerificationStatus::Pending;
        $this->rejection_reason = null;
        $this->reviewed_by = null;
        $this->reviewed_at = null;

        return true;
    }
}
