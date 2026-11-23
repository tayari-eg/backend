<?php

namespace Database\Seeders;

use App\Domains\Wallet\Models\WalletAdjustmentReason;
use Illuminate\Database\Seeder;

class WalletAdjustmentReasonSeeder extends Seeder
{
    /**
     * @var list<array{ar: string, en: string}>
     */
    private const array REASONS = [
        ['ar' => 'تصحيح', 'en' => 'Correction'],
        ['ar' => 'تعويض', 'en' => 'Compensation'],
        ['ar' => 'غرامة', 'en' => 'Penalty'],
        ['ar' => 'سبب آخر', 'en' => 'Other'],
    ];

    /**
     * Idempotent: creates a missing reason by its English name and never overwrites what staff have edited.
     */
    public function run(): void
    {
        foreach (self::REASONS as $index => $name) {
            if (! WalletAdjustmentReason::query()->where('name->en', $name['en'])->exists()) {
                WalletAdjustmentReason::query()->create(['name' => $name, 'is_active' => true, 'sort_order' => $index + 1]);
            }
        }
    }
}
