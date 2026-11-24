<?php

namespace App\Domains\Incentives\Models;

use App\Domains\Incentives\Enums\RankChangeSource;
use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One rank change of a tayar; never edited. from_rank_id is null for the starting rank.
 */
#[Fillable(['tayar_profile_id', 'from_rank_id', 'to_rank_id', 'source'])]
class TayarRankHistory extends Model
{
    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tayar_profile_id' => 'integer',
            'from_rank_id' => 'integer',
            'to_rank_id' => 'integer',
            'source' => RankChangeSource::class,
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
     * @return BelongsTo<TayarRank, $this>
     */
    public function fromRank(): BelongsTo
    {
        return $this->belongsTo(TayarRank::class, 'from_rank_id');
    }

    /**
     * @return BelongsTo<TayarRank, $this>
     */
    public function toRank(): BelongsTo
    {
        return $this->belongsTo(TayarRank::class, 'to_rank_id');
    }
}
