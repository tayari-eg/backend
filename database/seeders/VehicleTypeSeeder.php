<?php

namespace Database\Seeders;

use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * @var list<array{name: array{ar: string, en: string}, requires_license: bool}>
     */
    private const array TYPES = [
        ['name' => ['ar' => 'سيارة', 'en' => 'Car'], 'requires_license' => true],
        ['name' => ['ar' => 'موتوسيكل', 'en' => 'Motorcycle'], 'requires_license' => true],
        ['name' => ['ar' => 'سكوتر', 'en' => 'Scooter'], 'requires_license' => true],
        ['name' => ['ar' => 'عجلة', 'en' => 'Bicycle'], 'requires_license' => false],
    ];

    /**
     * Idempotent: matched on the English name, so running it again updates instead of duplicating.
     */
    public function run(): void
    {
        foreach (self::TYPES as $index => $type) {
            $vehicleType = VehicleType::query()->where('name->en', $type['name']['en'])->first() ?? new VehicleType;

            $vehicleType->fill([
                'name' => $type['name'],
                'requires_license' => $type['requires_license'],
                'can_carry_food' => true,
                'is_active' => true,
                'sort_order' => $index + 1,
            ])->save();
        }
    }
}
