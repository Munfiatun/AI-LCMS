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
        Schema::create('quiz_attempt_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_question_id')->constrained('quiz_attempt_questions')->cascadeOnDelete();
            $table->foreignId('option_id');
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->foreign('option_id')->references('id')->on('question_options')->cascadeOnDelete();
            }
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->unique(['attempt_question_id', 'option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_options');
    }
};
