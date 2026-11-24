<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Incentives\Models\TayarRankHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TayarRankHistory
 */
class TayarRankHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_rank' => $this->fromRank === null ? null : self::rank($this->fromRank),
            'to_rank' => self::rank($this->toRank),
            'source' => $this->source,
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @return array{id: int, code: string, name: string, level: int}
     */
    private static function rank(TayarRank $rank): array
    {
        return ['id' => $rank->id, 'code' => $rank->code, 'name' => $rank->getTranslation('name', app()->getLocale()), 'level' => $rank->level];
    }
}
