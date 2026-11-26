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
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id');
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            }
            $table->foreignId('selected_option_id')->nullable();
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->foreign('selected_option_id')->references('id')->on('question_options')->nullOnDelete();
            }
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('points_earned')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
