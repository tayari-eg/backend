<?php

namespace App\Domains\Tayars\Models;

use App\Domains\Tayars\Enums\VehicleDocument;
use App\Domains\Tayars\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['vehicle_type_id', 'plate_number', 'model', 'color', 'year'])]
#[UseFactory(VehicleFactory::class)]
class Vehicle extends Model implements HasMedia
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Changing one of these sends the application back to review.
     *
     * @var list<string>
     */
    public const array REVIEWED_ATTRIBUTES = ['vehicle_type_id', 'plate_number'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => VehicleStatus::Pending->value,
        'is_active' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
            'is_active' => 'boolean',
            'year' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(VehicleDocument::VehicleLicense->value)
            ->useDisk(config('tayari.media.private_disk'))
            ->singleFile();

        $this->addMediaCollection(VehicleDocument::VehiclePhotos->value)
            ->useDisk(config('tayari.media.private_disk'));
    }

    /**
     * @return BelongsTo<TayarProfile, $this>
     */
    public function tayarProfile(): BelongsTo
    {
        return $this->belongsTo(TayarProfile::class);
    }

    /**
     * @return BelongsTo<VehicleType, $this>
     */
    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function canBeUsed(): bool
    {
        return $this->is_active && $this->status === VehicleStatus::Approved;
    }
}
