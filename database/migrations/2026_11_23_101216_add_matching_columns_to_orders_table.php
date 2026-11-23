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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('tayar_id')->nullable()->after('receiver_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('work_session_id')->nullable()->after('tayar_id')->constrained('tayar_work_sessions')->restrictOnDelete();
            $table->timestamp('accepted_at')->nullable()->after('price_accepted_at');
            // Owed by the sender to the tayar for cancelling an accepted order after the free period; settled by the wallet.
            $table->decimal('cancel_fee', 10, 2)->default(0)->after('cancel_reason_id');
            $table->foreignId('cancel_fee_owed_to_tayar_id')->nullable()->after('cancel_fee')->constrained('users')->restrictOnDelete();

            // The tayar's orders by status, newest first; also covers the tayar_id foreign key.
            $table->index(['tayar_id', 'status', 'id']);
            $table->index('work_session_id');
            $table->index('cancel_fee_owed_to_tayar_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['tayar_id']);
            $table->dropForeign(['work_session_id']);
            $table->dropForeign(['cancel_fee_owed_to_tayar_id']);
            $table->dropIndex(['tayar_id', 'status', 'id']);
            $table->dropIndex(['work_session_id']);
            $table->dropIndex(['cancel_fee_owed_to_tayar_id']);
            $table->dropColumn(['tayar_id', 'work_session_id', 'accepted_at', 'cancel_fee', 'cancel_fee_owed_to_tayar_id']);
        });
    }
};
