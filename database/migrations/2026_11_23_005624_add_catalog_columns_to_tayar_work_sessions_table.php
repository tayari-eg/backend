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
        // Nullable because sessions from before modes existed have none; GoOnlineAction always sets mode_id.
        Schema::table('tayar_work_sessions', function (Blueprint $table) {
            $table->foreignId('mode_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('radius_km', 5, 2)->nullable();
            $table->string('destination_address')->nullable();
            $table->decimal('destination_lat', 10, 7)->nullable();
            $table->decimal('destination_lng', 10, 7)->nullable();

            // PostgreSQL doesn't index foreign keys by itself.
            $table->index('mode_id');
            $table->index('area_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tayar_work_sessions', function (Blueprint $table) {
            $table->dropForeign(['mode_id']);
            $table->dropForeign(['area_id']);
            $table->dropIndex(['mode_id']);
            $table->dropIndex(['area_id']);
            $table->dropColumn(['mode_id', 'area_id', 'radius_km', 'destination_address', 'destination_lat', 'destination_lng']);
        });
    }
};
