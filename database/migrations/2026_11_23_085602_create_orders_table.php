<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('mode_id')->constrained()->restrictOnDelete();
            $table->text('details')->nullable();

            $table->string('pickup_address');
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            $table->string('pickup_contact_phone', 20)->nullable();
            $table->string('dropoff_address');
            $table->decimal('dropoff_lat', 10, 7);
            $table->decimal('dropoff_lng', 10, 7);
            $table->string('receiver_name');
            $table->string('receiver_phone', 20);
            $table->foreignId('receiver_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('goods_value', 10, 2);
            $table->string('shipping_paid_by', 16);
            $table->decimal('amount_to_collect', 10, 2);

            // The fee as priced at creation; pricing rules can change or be deleted later.
            $table->decimal('distance_km', 8, 2);
            $table->decimal('base_fare', 10, 2);
            $table->decimal('price_per_km', 10, 2);
            $table->decimal('distance_fare', 10, 2);
            $table->decimal('minimum_fare', 10, 2);
            $table->decimal('food_extra', 10, 2);
            $table->decimal('shipping_fee', 10, 2);
            $table->decimal('tayar_share_percent', 5, 2);
            // Estimates from the general rule until matching fixes them for the accepting tayar.
            $table->decimal('tayar_earning', 10, 2);
            $table->decimal('platform_fee', 10, 2);

            $table->string('status', 32);
            $table->timestamp('price_accepted_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancel_reason_id')->nullable()->constrained('order_reasons')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Stops a double tap creating two orders; also covers the sender_id foreign key.
            $table->unique(['sender_id', 'idempotency_key']);
            $table->index(['sender_id', 'status', 'id']);
            $table->index(['status', 'id']);
            $table->index('created_at');
            $table->index('receiver_phone');
            $table->index('category_id');
            $table->index('mode_id');
            $table->index('receiver_user_id');
            $table->index('cancelled_by');
            $table->index('cancel_reason_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
