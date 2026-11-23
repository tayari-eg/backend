<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Pricing\Models\PricingRule;

class ActivatePricingRuleAction
{
    public function handle(PricingRule $pricingRule): PricingRule
    {
        $pricingRule->forceFill(['is_active' => true])->save();

        return $pricingRule;
    }
}
