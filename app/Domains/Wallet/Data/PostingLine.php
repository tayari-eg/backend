<?php

namespace App\Domains\Wallet\Data;

use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Models\Wallet;
use Brick\Math\BigDecimal;

/**
 * One wallet transaction a posting will write: a signed amount on a wallet.
 */
final readonly class PostingLine
{
    public function __construct(
        public Wallet $wallet,
        public TransactionType $type,
        public BigDecimal $amount,
        public ?int $orderId = null,
    ) {}
}
