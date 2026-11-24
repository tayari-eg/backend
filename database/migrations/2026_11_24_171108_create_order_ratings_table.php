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
        Schema::create('order_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('rater_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('rated_user_id')->constrained('users')->restrictOnDelete();
            $table->string('rater_role', 16);
            $table->unsignedTinyInteger('stars');
            $table->string('comment', 500)->nullable();
            $table->timestamp('comment_hidden_at')->nullable();
            $table->foreignId('comment_hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('comment_hidden_reason', 500)->nullable();
            // The end of the rating window: the rated party sees it, and it counts, from then on.
            $table->timestamp('revealed_at');
            $table->timestamps();

            // One rating per rater per order; also covers the order_id foreign key.
            $table->unique(['order_id', 'rater_id']);
            $table->index(['rated_user_id', 'rater_role', 'revealed_at']);
            // The tayar's ratings list pages by id only.
            $table->index(['rated_user_id', 'rater_role', 'id']);
            $table->index('rater_id');
            $table->index('comment_hidden_by');
            $table->index('comment_hidden_at');
            $table->index('stars');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_ratings');
    }
};
