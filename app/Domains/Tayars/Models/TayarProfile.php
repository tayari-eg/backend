<?php

namespace App\Domains\Tayars\Models;

use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Tayars\Data\BoundingBox;
use App\Domains\Tayars\Enums\TayarDocument;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Enums\VehicleStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\TayarProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['national_id_number'])]
#[UseFactory(TayarProfileFactory::class)]
class TayarProfile extends Model implements HasMedia
{
    /** @use HasFactory<TayarProfileFactory> */
    use HasFactory, InteractsWithMedia;

    private const int EARTH_RADIUS_KM = 6371;

    /**
     * Changing one of these sends an approved or rejected application back to review.
     *
     * @var list<string>
     */
    public const array REVIEWED_ATTRIBUTES = ['national_id_number'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TayarStatus::Pending->value,
        'is_online' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TayarStatus::class,
            'can_reapply_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'is_online' => 'boolean',
            'current_lat' => 'decimal:7',
            'current_lng' => 'decimal:7',
            'location_updated_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        foreach (TayarDocument::cases() as $document) {
            $this->addMediaCollection($document->value)
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
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * The vehicle sent with the application (one vehicle per tayar for now).
     *
     * @return HasOne<Vehicle, $this>
     */
    public function vehicle(): HasOne
    {
        return $this->hasOne(Vehicle::class)->oldestOfMany();
    }

    /**
     * @return HasMany<TayarWorkSession, $this>
     */
    public function workSessions(): HasMany
    {
        return $this->hasMany(TayarWorkSession::class);
    }

    /**
     * @return BelongsTo<TayarWorkSession, $this>
     */
    public function currentWorkSession(): BelongsTo
    {
        return $this->belongsTo(TayarWorkSession::class, 'current_work_session_id');
    }

    /**
     * @return HasMany<TayarReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(TayarReview::class);
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
        return $this->status === TayarStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === TayarStatus::Approved;
    }

    /**
     * A rejected applicant waits until can_reapply_at before changing the application.
     */
    public function canChangeApplication(CarbonInterface $now): bool
    {
        return $this->status !== TayarStatus::Rejected || $this->can_reapply_at === null || $this->can_reapply_at->lte($now);
    }

    /**
     * @throws ValidationException while a rejected applicant still has to wait
     */
    public function ensureCanChangeApplication(): void
    {
        if (! $this->canChangeApplication(now())) {
            throw ValidationException::withMessages(['status' => __('tayars.reapply_later', [
                'date' => $this->can_reapply_at->timezone(config('app.business_timezone'))->format('Y-m-d H:i'),
            ])]);
        }
    }

    /**
     * Clears the previous decision so the application is reviewed again; the caller saves the profile. The vehicles go
     * back to review at once (a query), so call it inside the caller's transaction. Returns whether the status changed.
     */
    public function returnToPendingReview(): bool
    {
        if ($this->isPending()) {
            return false;
        }

        $this->vehicles()->update(['status' => VehicleStatus::Pending, 'is_active' => false]);

        $this->status = TayarStatus::Pending;
        $this->rejection_reason = null;
        $this->can_reapply_at = null;
        $this->reviewed_by = null;
        $this->reviewed_at = null;

        return true;
    }

    /**
     * Approved tayars only.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', TayarStatus::Approved);
    }

    /**
     * Online, approved tayars of active accounts within the radius, nearest first, with distance_km. A lat/lng box narrows the
     * rows through the index first; the distance uses math functions MySQL and PostgreSQL share. Moving to PostGIS
     * later only changes this scope.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function nearestOnline(Builder $query, float $lat, float $lng, ?float $radiusKm = null): void
    {
        $radiusKm ??= (float) config('tayari.tayars.nearest_radius_km');
        $box = BoundingBox::around($lat, $lng, $radiusKm);

        // least() keeps rounding from pushing acos() past 1 for a tayar at the exact point.
        $distance = '('.self::EARTH_RADIUS_KM.' * acos(least(1, cos(radians(?)) * cos(radians(tayar_profiles.current_lat))'
            .' * cos(radians(tayar_profiles.current_lng) - radians(?)) + sin(radians(?)) * sin(radians(tayar_profiles.current_lat)))))';
        $bindings = [$lat, $lng, $lat];

        $query
            ->where('tayar_profiles.is_online', true)
            // An application sent back to review stays online until GoOfflineAction runs; it must not get offers.
            ->where('tayar_profiles.status', TayarStatus::Approved)
            ->whereBetween('tayar_profiles.current_lat', [$box->minLat, $box->maxLat])
            ->whereBetween('tayar_profiles.current_lng', [$box->minLng, $box->maxLng])
            ->whereHas('user', fn (Builder $users) => $users->where('status', UserStatus::Active))
            ->whereRaw("{$distance} <= ?", [...$bindings, $radiusKm])
            ->select('tayar_profiles.*')
            ->selectRaw("{$distance} as distance_km", $bindings)
            ->orderBy('distance_km');
    }
}
