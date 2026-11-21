<?php

namespace Database\Seeders;

use App\Domains\Accounts\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    /**
     * @var list<array{ar: string, en: string}>
     */
    private const array TYPES = [
        ['ar' => 'مطعم', 'en' => 'Restaurant'],
        ['ar' => 'كافيه', 'en' => 'Cafe'],
        ['ar' => 'سوبر ماركت', 'en' => 'Supermarket'],
        ['ar' => 'صيدلية', 'en' => 'Pharmacy'],
        ['ar' => 'مخبز وحلويات', 'en' => 'Bakery and sweets'],
        ['ar' => 'ملابس وأحذية', 'en' => 'Clothes and shoes'],
        ['ar' => 'إلكترونيات وموبايلات', 'en' => 'Electronics and phones'],
        ['ar' => 'مستحضرات تجميل', 'en' => 'Beauty and cosmetics'],
        ['ar' => 'متجر أونلاين', 'en' => 'Online store'],
        ['ar' => 'أخرى', 'en' => 'Other'],
    ];

    /**
     * Idempotent: matched on the English name, so running it again updates instead of duplicating.
     */
    public function run(): void
    {
        foreach (self::TYPES as $index => $name) {
            $businessType = BusinessType::query()->where('name->en', $name['en'])->first() ?? new BusinessType;

            $businessType->fill([
                'name' => $name,
                'is_active' => true,
                'sort_order' => $index + 1,
            ])->save();
        }
    }
}
