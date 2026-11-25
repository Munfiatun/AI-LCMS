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
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('question_id');
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            }
            $table->unsignedInteger('order')->default(1);
            $table->unsignedInteger('points')->default(10);
            $table->timestamps();

            $table->unique(['quiz_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
