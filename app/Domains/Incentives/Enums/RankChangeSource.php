<?php

namespace App\Domains\Incentives\Enums;

/**
 * Who changed a tayar's rank. Only the nightly job changes ranks for now.
 */
enum RankChangeSource: string
{
    case Auto = 'auto';
}
