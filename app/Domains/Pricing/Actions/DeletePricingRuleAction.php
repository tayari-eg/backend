<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Pricing\Models\PricingRule;

class DeletePricingRuleAction
{
    /**
     * Nothing points at a rule: orders keep their own fee.
     */
    public function handle(PricingRule $pricingRule): void
    {
        $pricingRule->delete();
    }
}
