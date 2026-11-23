<?php

namespace Database\Seeders;

use App\Domains\Orders\Enums\OrderReasonType;
use App\Domains\Orders\Models\OrderReason;
use Illuminate\Database\Seeder;

class OrderReasonSeeder extends Seeder
{
    /**
     * @var list<array{ar: string, en: string}>
     */
    private const array CANCEL_REASONS = [
        ['ar' => 'لم أعد أحتاج الطلب', 'en' => 'I no longer need the order'],
        ['ar' => 'بيانات أو عنوان غير صحيح', 'en' => 'Wrong details or address'],
        ['ar' => 'المستلم غير متاح', 'en' => 'The receiver is not available'],
        ['ar' => 'الانتظار طويل', 'en' => 'Waiting too long for a tayar'],
        ['ar' => 'سبب آخر', 'en' => 'Other'],
    ];

    /**
     * Idempotent: creates a missing reason by its English name and never overwrites what staff have edited.
     */
    public function run(): void
    {
        foreach (self::CANCEL_REASONS as $index => $name) {
            $exists = OrderReason::query()
                ->ofType(OrderReasonType::Cancel)
                ->where('name->en', $name['en'])
                ->exists();

            if (! $exists) {
                OrderReason::query()->create([
                    'type' => OrderReasonType::Cancel,
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}
