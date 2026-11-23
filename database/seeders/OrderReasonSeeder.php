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
     * @var list<array{ar: string, en: string}>
     */
    private const array NOT_DELIVERED_REASONS = [
        ['ar' => 'المستلم رفض الطلب', 'en' => 'The receiver refused the order'],
        ['ar' => 'الطلب تالف', 'en' => 'The items are damaged'],
        ['ar' => 'الطلب غير مطابق', 'en' => 'Not what the receiver ordered'],
        ['ar' => 'لا يمكن الوصول للمستلم', 'en' => 'The receiver can\'t be reached'],
        ['ar' => 'سبب آخر', 'en' => 'Other'],
    ];

    /**
     * Idempotent: creates a missing reason by its type and English name and never overwrites what staff have edited.
     */
    public function run(): void
    {
        $this->seedReasons(OrderReasonType::Cancel, self::CANCEL_REASONS);
        $this->seedReasons(OrderReasonType::NotDelivered, self::NOT_DELIVERED_REASONS);
    }

    /**
     * @param  list<array{ar: string, en: string}>  $names
     */
    private function seedReasons(OrderReasonType $type, array $names): void
    {
        foreach ($names as $index => $name) {
            $exists = OrderReason::query()
                ->ofType($type)
                ->where('name->en', $name['en'])
                ->exists();

            if (! $exists) {
                OrderReason::query()->create([
                    'type' => $type,
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}
