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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tayar_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained();
            $table->string('plate_number', 20)->nullable()->unique();
            $table->string('model', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // PostgreSQL doesn't index foreign keys by itself.
            $table->index(['tayar_profile_id', 'status', 'is_active']);
            $table->index('vehicle_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
