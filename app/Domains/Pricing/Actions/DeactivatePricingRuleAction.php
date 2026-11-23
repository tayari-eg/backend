<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Pricing\Models\PricingRule;

class DeactivatePricingRuleAction
{
    public function handle(PricingRule $pricingRule): PricingRule
    {
        $pricingRule->forceFill(['is_active' => false])->save();

        return $pricingRule;
    }
}
