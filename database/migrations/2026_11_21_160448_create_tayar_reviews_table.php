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
        Schema::create('tayar_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tayar_profile_id')->constrained()->cascadeOnDelete();
            $table->string('decision', 20);
            $table->string('reason')->nullable();
            $table->foreignId('reviewed_by')->constrained('users');
            $table->timestamp('created_at')->nullable();

            $table->index('tayar_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tayar_reviews');
    }
};
