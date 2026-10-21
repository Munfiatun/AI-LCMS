<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_objectives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'code']);
            $table->index(['course_id', 'is_active', 'order']);
        });

        Schema::create('learning_material_learning_objective', function (Blueprint $table): void {
            $table->foreignId('learning_material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('learning_objective_id')->constrained('learning_objectives')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['learning_material_id', 'learning_objective_id']);
        });

        Schema::create('learning_objective_question', function (Blueprint $table): void {
            $table->foreignId('learning_objective_id')->constrained('learning_objectives')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['learning_objective_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_objective_question');
        Schema::dropIfExists('learning_material_learning_objective');
        Schema::dropIfExists('learning_objectives');
    }
};
