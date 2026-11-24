<?php

namespace App\Domains\Incentives\Models;

use App\Domains\Tayars\Models\TayarProfile;
use App\Models\Concerns\HasTranslatedNameSearch;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\TayarRankFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\HasTranslations;

/**
 * A tayar rank and what it needs over the last tayari.ranks.window_days. The code and level never change; staff edit
 * the name, the thresholds and the colour. The lowest level is every approved tayar's starting rank.
 */
#[Fillable(['name', 'min_delivered_orders_30d', 'min_rating', 'color'])]
#[UseFactory(TayarRankFactory::class)]
class TayarRank extends Model
{
    /** @use HasFactory<TayarRankFactory> */
    use HasFactory, HasTranslatedNameSearch, HasTranslations;

    private const string LADDER_CACHE_KEY = 'incentives:tayar-ranks:ladder';

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
            'level' => 'integer',
            'min_delivered_orders_30d' => 'integer',
            'min_rating' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetLadder());
        static::deleted(fn () => self::forgetLadder());
    }

    public static function forgetLadder(): void
    {
        Cache::forget(self::LADDER_CACHE_KEY);
    }

    /**
     * Every rank, lowest level first. The cache keeps the raw attributes only (stores don't unserialize objects), and
     * the models are rebuilt from them, so casts and translations work as on a query.
     *
     * @return Collection<int, self>
     */
    public static function ladder(): Collection
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = Cache::rememberForever(self::LADDER_CACHE_KEY, fn (): array => self::query()
            ->orderBy('level')
            ->get()
            ->map(fn (self $rank): array => $rank->getAttributes())
            ->all());

        return self::hydrate($rows);
    }

    /**
     * When a rank a tayar fell below drops: the start of the business day tayari.ranks.downgrade_grace_days calendar
     * days after the day they fell below it, so the nightly run's exact time and clock changes don't matter.
     */
    public static function dropsAt(CarbonInterface $belowSince): CarbonImmutable
    {
        return CarbonImmutable::instance($belowSince)
            ->setTimezone(config('app.business_timezone'))
            ->startOfDay()
            ->addDays(config()->integer('tayari.ranks.downgrade_grace_days'))
            ->utc();
    }

    /**
     * Whether a tayar with these 30-day numbers meets the rank. A rank that needs a rating needs an average, which only
     * exists from tayari.ratings.min_ratings_for_average ratings.
     */
    public function isMetBy(int $deliveredOrders, ?string $ratingAvg): bool
    {
        if ($deliveredOrders < $this->min_delivered_orders_30d) {
            return false;
        }

        if ($this->min_rating === null) {
            return true;
        }

        return $ratingAvg !== null && BigDecimal::of($ratingAvg)->isGreaterThanOrEqualTo((string) $this->min_rating);
    }

    /**
     * @return HasMany<TayarProfile, $this>
     */
    public function tayarProfiles(): HasMany
    {
        return $this->hasMany(TayarProfile::class, 'rank_id');
    }
}
