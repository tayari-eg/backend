<?php

namespace App\Domains\Incentives\Enums;

/**
 * The data.type of the rank pushes. The titles and bodies are under push.<type> in lang/{ar,en}/push.php.
 */
enum RankPushType: string
{
    case RankChanged = 'rank_changed';
    case RankAtRisk = 'rank_at_risk';

    public function titleKey(): string
    {
        return "push.{$this->value}.title";
    }

    public function bodyKey(): string
    {
        return "push.{$this->value}.body";
    }
}
