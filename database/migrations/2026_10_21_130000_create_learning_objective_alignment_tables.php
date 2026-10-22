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

            $table->unique(['course_id', 'code'], 'lo_course_code_unique');
            $table->index(['course_id', 'is_active', 'order'], 'lo_course_active_order_idx');
        });

        Schema::create('learning_material_learning_objective', function (Blueprint $table): void {
            $table->unsignedBigInteger('learning_material_id');
            $table->unsignedBigInteger('learning_objective_id');
            $table->timestamps();

            $table->foreign('learning_material_id', 'lm_lo_material_fk')
                ->references('id')->on('learning_materials')->cascadeOnDelete();
            $table->foreign('learning_objective_id', 'lm_lo_objective_fk')
                ->references('id')->on('learning_objectives')->cascadeOnDelete();
            $table->primary(['learning_material_id', 'learning_objective_id'], 'lm_lo_primary');
        });

        Schema::create('learning_objective_question', function (Blueprint $table): void {
            $table->unsignedBigInteger('learning_objective_id');
            $table->unsignedBigInteger('question_id');
            $table->timestamps();

            $table->foreign('learning_objective_id', 'lo_q_objective_fk')
                ->references('id')->on('learning_objectives')->cascadeOnDelete();
            $table->foreign('question_id', 'lo_q_question_fk')
                ->references('id')->on('questions')->cascadeOnDelete();
            $table->primary(['learning_objective_id', 'question_id'], 'lo_q_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_objective_question');
        Schema::dropIfExists('learning_material_learning_objective');
        Schema::dropIfExists('learning_objectives');
    }
};
