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
        Schema::create('tayar_rank_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tayar_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_rank_id')->nullable()->constrained('tayar_ranks')->restrictOnDelete();
            $table->foreignId('to_rank_id')->constrained('tayar_ranks')->restrictOnDelete();
            $table->string('source', 16);
            $table->timestamp('created_at');

            $table->index(['tayar_profile_id', 'id']);
            $table->index('from_rank_id');
            $table->index('to_rank_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tayar_rank_histories');
    }
};
