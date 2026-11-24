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
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->nullable()->after('reviewed_at');
            $table->unsignedInteger('ratings_count_30d')->default(0)->after('rating_avg');
            $table->timestamp('rating_updated_at')->nullable()->after('ratings_count_30d');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn(['rating_avg', 'ratings_count_30d', 'rating_updated_at']);
        });
    }
};
