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
        Schema::create('wallet_postings', function (Blueprint $table) {
            $table->id();
            // The same money event can never post twice, e.g. order:15:delivered.
            $table->string('idempotency_key', 100)->unique();
            $table->string('type', 32);
            $table->nullableMorphs('source');
            // Unique: a posting can be reversed only once.
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('wallet_postings')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('reason_id')->nullable()->constrained('wallet_adjustment_reasons')->restrictOnDelete();
            $table->string('reference', 100)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at');

            $table->index('created_by');
            $table->index('reason_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_postings');
    }
};
