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
        Schema::create('tayar_work_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tayar_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->index(['tayar_profile_id', 'ended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tayar_work_sessions');
    }
};
