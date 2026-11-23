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
        Schema::create('order_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tayar_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('work_session_id')->constrained('tayar_work_sessions')->restrictOnDelete();
            $table->unsignedSmallInteger('round');
            // Straight line from the tayar to the pickup when the offer was made.
            $table->decimal('distance_km', 8, 2);
            // What this tayar earns with this vehicle; copied to the order on accept.
            $table->decimal('tayar_share_percent', 5, 2);
            $table->decimal('tayar_earning', 10, 2);
            $table->decimal('platform_fee', 10, 2);
            $table->string('status', 16);
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // One offer per tayar per round; also covers the order_id foreign key.
            $table->unique(['order_id', 'tayar_id', 'round']);
            $table->index(['order_id', 'status']);
            // The tayar's live offers; also covers the tayar_id foreign key.
            $table->index(['tayar_id', 'status', 'expires_at']);
            $table->index(['status', 'expires_at']);
            $table->index('work_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_offers');
    }
};
