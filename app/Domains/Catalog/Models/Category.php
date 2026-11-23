<?php

namespace App\Domains\Catalog\Models;

use App\Models\Concerns\HasTranslatedNameSearch;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * A main category (no parent) or a subcategory of one. Two levels at most.
 */
#[Fillable(['parent_id', 'name', 'is_food', 'is_active', 'sort_order'])]
#[UseFactory(CategoryFactory::class)]
class Category extends Model implements HasMedia
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasTranslatedNameSearch, HasTranslations, InteractsWithMedia;

    public const string ICON_COLLECTION = 'icon';

    /**
     * @var list<string>
     */
    public const array ICON_MIMES = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @var list<string>
     */
    public array $translatable = ['name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'is_food' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ICON_COLLECTION)
            ->useDisk(config('tayari.media.public_disk'))
            ->singleFile();
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isMain(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * The public URL of the icon, or null. Needs the media relation loaded.
     */
    public function iconUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::ICON_COLLECTION);

        return $url === '' ? null : $url;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function main(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * The categories directly under a parent; a null parent means the main categories.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function under(Builder $query, ?int $parentId): void
    {
        $parentId === null ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);
    }
}
