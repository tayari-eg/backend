<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nullable only for the rows below; CreateOrderAction always sets it.
            $table->string('tracking_token', 64)->nullable()->unique()->after('code');
            // Hashes only: the plain codes go to the receiver by SMS and are never stored.
            $table->string('delivery_code_hash')->nullable()->after('platform_fee');
            $table->string('refusal_code_hash')->nullable()->after('delivery_code_hash');
            $table->unsignedTinyInteger('code_attempts')->default(0)->after('refusal_code_hash');
            $table->timestamp('codes_sent_at')->nullable()->after('code_attempts');
            $table->timestamp('codes_expire_at')->nullable()->after('codes_sent_at');
            $table->timestamp('picked_up_at')->nullable()->after('accepted_at');
            $table->timestamp('receiver_confirmed_at')->nullable()->after('picked_up_at');
            $table->timestamp('delivered_at')->nullable()->after('receiver_confirmed_at');
            $table->timestamp('not_delivered_at')->nullable()->after('delivered_at');
            $table->foreignId('not_delivered_reason_id')->nullable()->after('not_delivered_at')->constrained('order_reasons')->restrictOnDelete();
            $table->string('not_delivered_note', 500)->nullable()->after('not_delivered_reason_id');

            // PostgreSQL doesn't index foreign keys by itself.
            $table->index('not_delivered_reason_id');
        });

        // Portable backfill: one update per row, in id order.
        DB::table('orders')->whereNull('tracking_token')->select('id')->chunkById(500, function ($orders): void {
            foreach ($orders as $order) {
                DB::table('orders')->where('id', $order->id)->update(['tracking_token' => Str::random(40)]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['not_delivered_reason_id']);
            $table->dropIndex(['not_delivered_reason_id']);
            $table->dropUnique(['tracking_token']);
            $table->dropColumn([
                'tracking_token', 'delivery_code_hash', 'refusal_code_hash', 'code_attempts', 'codes_sent_at', 'codes_expire_at',
                'picked_up_at', 'receiver_confirmed_at', 'delivered_at', 'not_delivered_at', 'not_delivered_reason_id', 'not_delivered_note',
            ]);
        });
    }
};
