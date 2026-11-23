<?php

namespace Tests\Unit\Domains\Orders;

use App\Domains\Orders\Enums\OfferStatus;
use PHPUnit\Framework\TestCase;

class OfferStatusTest extends TestCase
{
    public function test_only_an_offered_offer_moves_and_every_other_status_is_final(): void
    {
        foreach (OfferStatus::cases() as $from) {
            foreach (OfferStatus::cases() as $to) {
                $this->assertSame(
                    $from === OfferStatus::Offered && $to !== OfferStatus::Offered,
                    $from->canTransitionTo($to),
                    "{$from->value} → {$to->value}",
                );
            }
        }
    }
}
