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
            $table->string('phone', 20)->unique()->after('name');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('email')->nullable()->change();
            $table->string('account_type', 20)->default('individual')->after('password');
            $table->string('locale', 5)->default('ar')->after('account_type');
            $table->string('status', 20)->default('active')->after('locale');
            $table->string('blocked_reason')->nullable()->after('status');
            $table->timestamp('id_verified_at')->nullable()->after('blocked_reason');
            $table->string('id_rejection_reason')->nullable()->after('id_verified_at');
            $table->foreignId('id_reviewed_by')->nullable()->after('id_rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('id_reviewed_at')->nullable()->index()->after('id_reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_reviewed_by');
            $table->dropIndex(['id_reviewed_at']);
            $table->dropUnique(['phone']);
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'account_type',
                'locale',
                'status',
                'blocked_reason',
                'id_verified_at',
                'id_rejection_reason',
                'id_reviewed_at',
            ]);
            $table->string('email')->nullable(false)->change();
        });
    }
};
