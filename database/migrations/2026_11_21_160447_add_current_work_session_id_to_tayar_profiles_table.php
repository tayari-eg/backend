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
            $table->foreignId('current_work_session_id')->nullable()->after('is_online')->constrained('tayar_work_sessions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tayar_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_work_session_id');
        });
    }
};
