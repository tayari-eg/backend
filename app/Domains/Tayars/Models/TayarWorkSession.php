<?php

namespace App\Domains\Tayars\Models;

use Database\Factories\TayarWorkSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stretch of time a tayar was online, from going online to going offline.
 */
#[Fillable(['vehicle_id', 'started_at', 'ended_at'])]
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
}
