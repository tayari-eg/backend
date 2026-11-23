<?php

namespace Database\Seeders;

use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Mode;
use Illuminate\Database\Seeder;

class ModeSeeder extends Seeder
{
    /**
     * @var list<array{code: ModeCode, name: array{ar: string, en: string}, description: array{ar: string, en: string}, allows_food: bool}>
     */
    private const array MODES = [
        [
            'code' => ModeCode::LongDistance,
            'name' => ['ar' => 'مسافات طويلة', 'en' => 'Long distance'],
            'description' => ['ar' => 'عدة استلامات وتوصيلات في اتجاه واحد.', 'en' => 'Several pickups and drop-offs in one direction.'],
            'allows_food' => false,
        ],
        [
            'code' => ModeCode::Zone,
            'name' => ['ar' => 'حولك', 'en' => 'Zone'],
            'description' => ['ar' => 'مشاوير قصيرة حولك، منها توصيل الطعام.', 'en' => 'Short trips around you, including food.'],
            'allows_food' => true,
        ],
        [
            'code' => ModeCode::Area,
            'name' => ['ar' => 'منطقة', 'en' => 'Area'],
            'description' => ['ar' => 'حي كامل، مثل مدينة نصر.', 'en' => 'A whole district, for example Nasr City.'],
            'allows_food' => true,
        ],
    ];

    /**
     * Idempotent: creates a missing mode by code and never overwrites what staff have edited.
     */
    public function run(): void
    {
        foreach (self::MODES as $index => $mode) {
            Mode::query()->firstOrCreate(['code' => $mode['code']], [
                'name' => $mode['name'],
                'description' => $mode['description'],
                'allows_food' => $mode['allows_food'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
