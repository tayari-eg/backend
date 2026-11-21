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
        Schema::table('users', function (Blueprint $table) {
            $table->index('account_type');
            $table->index('status');
            $table->index('phone_verified_at');
            $table->index('id_verified_at');
        });

        Schema::table('business_types', function (Blueprint $table) {
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['phone_verified_at']);
            $table->dropIndex(['id_verified_at']);
        });

        Schema::table('business_types', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
        });
    }
};
