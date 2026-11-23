<?php

namespace App\Domains\Tayars\Models;

use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Mode;
use Database\Factories\TayarWorkSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stretch of time a tayar was online in one mode, from going online to going offline or changing the mode.
 */
#[Fillable(['vehicle_id', 'mode_id', 'area_id', 'radius_km', 'destination_address', 'destination_lat', 'destination_lng', 'started_at', 'ended_at'])]
#[UseFactory(TayarWorkSessionFactory::class)]
class TayarWorkSession extends Model
{
    /** @use HasFactory<TayarWorkSessionFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode_id' => 'integer',
            'area_id' => 'integer',
            'radius_km' => 'decimal:2',
            'destination_lat' => 'decimal:7',
            'destination_lng' => 'decimal:7',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TayarProfile, $this>
     */
    public function tayarProfile(): BelongsTo
    {
        return $this->belongsTo(TayarProfile::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Mode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(Mode::class);
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
