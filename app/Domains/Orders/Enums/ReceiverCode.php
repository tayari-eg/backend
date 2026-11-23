<?php

namespace App\Domains\Orders\Enums;

/**
 * The two codes the receiver gets by SMS at pickup: one to confirm delivery, one to refuse the order.
 */
enum ReceiverCode: string
{
    case Delivery = 'delivery';
    case Refusal = 'refusal';

    public function hashColumn(): string
    {
        return "{$this->value}_code_hash";
    }
}
