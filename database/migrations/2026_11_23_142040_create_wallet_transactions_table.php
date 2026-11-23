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
        // The ledger: rows are never updated or deleted; a correction is a new reversing posting.
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posting_id')->constrained('wallet_postings')->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 32);
            // Positive: Tayari owes the wallet's owner more; negative: less.
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 14, 2);
            // When the amount can be paid out.
            $table->timestamp('available_at');
            $table->timestamp('created_at');

            // Also covers the posting_id foreign key.
            $table->unique(['posting_id', 'wallet_id', 'type']);
            // A wallet's transactions, newest first; also covers the wallet_id foreign key.
            $table->index(['wallet_id', 'id']);
            // A wallet's transactions of one type, newest first (the type filter).
            $table->index(['wallet_id', 'type', 'id']);
            $table->index(['wallet_id', 'available_at']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
