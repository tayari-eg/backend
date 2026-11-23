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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            // Null for Tayari's own (system) wallets.
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 16);
            $table->string('system_code', 32)->nullable()->unique();
            // A cached total of the wallet's transactions, written in the same transaction as each one.
            $table->decimal('balance', 14, 2)->default(0);
            $table->timestamps();

            // One wallet per user per type; also covers the user_id foreign key.
            $table->unique(['user_id', 'type']);
            $table->index(['type', 'balance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
