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
        Schema::table('tayar_profiles', function (Blueprint $table) {
            $table->foreignId('rank_id')->nullable()->after('current_work_session_id')->constrained('tayar_ranks')->restrictOnDelete();
            // When the tayar first fell below the current rank; the rank drops after the grace days.
            $table->timestamp('rank_below_since')->nullable()->after('rank_id');
            $table->decimal('rating_avg', 3, 2)->nullable()->after('rank_below_since');
            $table->unsignedInteger('ratings_count_30d')->default(0)->after('rating_avg');
            $table->unsignedInteger('delivered_orders_30d')->default(0)->after('ratings_count_30d');
            $table->timestamp('standing_updated_at')->nullable()->after('delivered_orders_30d');

            $table->index('rank_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tayar_profiles', function (Blueprint $table) {
            $table->dropForeign(['rank_id']);
            $table->dropIndex(['rank_id']);
            $table->dropColumn(['rank_id', 'rank_below_since', 'rating_avg', 'ratings_count_30d', 'delivered_orders_30d', 'standing_updated_at']);
        });
    }
};
