<?php

namespace App\Models;

use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\UserDevice;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'phone', 'email', 'password', 'account_type', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, InteractsWithMedia, Notifiable;

    /**
     * Guard used by spatie/laravel-permission, so roles resolve the same
     * for session (web) and Sanctum token requests.
     */
    protected $guard_name = 'web';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'account_type' => AccountType::Individual->value,
        'locale' => Locale::Ar->value,
        'status' => UserStatus::Active->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'id_verified_at' => 'datetime',
            'id_reviewed_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
            'locale' => Locale::class,
            'status' => UserStatus::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        foreach (DocumentCollection::forNationalId() as $collection) {
            $this->addMediaCollection($collection->value)
                ->useDisk(config('tayari.media.private_disk'))
                ->singleFile();
        }
    }

    /**
     * @return HasOne<BusinessProfile, $this>
     */
    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    /**
     * @return HasMany<UserDevice, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function idReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_reviewed_by');
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function isBlocked(): bool
    {
        return $this->status === UserStatus::Blocked;
    }

    public function isBusiness(): bool
    {
        return $this->account_type === AccountType::Business;
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(StaffRole::values());
    }

    /**
     * Customer and business accounts: users without a staff role.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function customers(Builder $query): void
    {
        $query->whereDoesntHave('roles', fn (Builder $roles) => $roles->whereIn('name', StaffRole::values()));
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function staff(Builder $query): void
    {
        $query->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', StaffRole::values()));
    }

    /**
     * The same rules as nationalIdStatus(); null means no national ID was uploaded.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereNationalIdStatus(Builder $query, ?VerificationStatus $status): void
    {
        $hasFront = fn (Builder $media) => $media->where('collection_name', DocumentCollection::NationalIdFront->value);

        match ($status) {
            VerificationStatus::Approved => $query->whereNotNull('id_verified_at'),
            VerificationStatus::Rejected => $query->whereNull('id_verified_at')->whereNotNull('id_reviewed_at'),
            VerificationStatus::Pending => $query->whereNull('id_verified_at')->whereNull('id_reviewed_at')->whereHas('media', $hasFront),
            null => $query->whereNull('id_verified_at')->whereNull('id_reviewed_at')->whereDoesntHave('media', $hasFront),
        };
    }

    /**
     * Pending means documents were uploaded and not reviewed yet; null means nothing was uploaded.
     */
    public function nationalIdStatus(): ?VerificationStatus
    {
        return match (true) {
            $this->id_verified_at !== null => VerificationStatus::Approved,
            $this->id_reviewed_at !== null => VerificationStatus::Rejected,
            $this->hasMedia(DocumentCollection::NationalIdFront->value) => VerificationStatus::Pending,
            default => null,
        };
    }
}
