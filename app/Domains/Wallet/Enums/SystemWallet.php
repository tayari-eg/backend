<?php

namespace App\Domains\Wallet\Enums;

/**
 * Tayari's own side of every posting. User wallets hold what Tayari owes the user, so these hold the other side and
 * all balances together always add up to zero.
 */
enum SystemWallet: string
{
    // Tayari's commission on delivered orders.
    case Revenue = 'revenue';
    // Bonuses Tayari pays out of its own budget.
    case BonusExpense = 'bonus_expense';
    // The other side of staff adjustments.
    case Adjustments = 'adjustments';
    // Money in Tayari's bank or cash box: cash handed in lowers it, payouts raise it (it mirrors what Tayari holds).
    case Treasury = 'treasury';
}
