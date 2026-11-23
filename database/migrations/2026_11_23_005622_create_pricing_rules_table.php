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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mode_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('base_fare', 10, 2);
            $table->decimal('price_per_km', 10, 2);
            $table->decimal('minimum_fare', 10, 2);
            $table->decimal('food_extra', 10, 2)->nullable();
            $table->decimal('tayar_share_percent', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Several NULL vehicle types pass a unique index on both engines; CreatePricingRuleAction checks those.
            $table->unique(['mode_id', 'vehicle_type_id']);
            $table->index('vehicle_type_id');
            $table->index(['is_active', 'mode_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
