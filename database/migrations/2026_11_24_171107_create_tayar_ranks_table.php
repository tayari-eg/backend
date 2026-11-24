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
        Schema::create('tayar_ranks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->json('name');
            $table->unsignedTinyInteger('level')->unique();
            $table->unsignedInteger('min_delivered_orders_30d')->default(0);
            // Null: no rating needed (the first rank).
            $table->decimal('min_rating', 3, 2)->nullable();
            $table->string('color', 7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tayar_ranks');
    }
};
