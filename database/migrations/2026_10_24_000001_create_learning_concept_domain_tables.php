<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_concepts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_objective_id')->nullable()->constrained('learning_objectives')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'code'], 'concept_course_code_unique');
            $table->index(['course_id', 'is_active', 'order'], 'concept_course_active_order_idx');
            $table->index(['learning_objective_id', 'order'], 'concept_objective_order_idx');
        });

        Schema::create('concept_prerequisites', function (Blueprint $table): void {
            $table->foreignId('concept_id')->constrained('learning_concepts')->cascadeOnDelete();
            $table->foreignId('prerequisite_concept_id')->constrained('learning_concepts')->cascadeOnDelete();
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->primary(['concept_id', 'prerequisite_concept_id'], 'concept_prerequisite_primary');
            $table->index(['prerequisite_concept_id', 'concept_id'], 'prerequisite_concept_lookup_idx');
        });

        Schema::create('material_concepts', function (Blueprint $table): void {
            $table->foreignId('learning_material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('learning_concept_id')->constrained('learning_concepts')->cascadeOnDelete();
            $table->decimal('relevance_weight', 5, 2)->default(1.00);
            $table->timestamps();

            $table->primary(['learning_material_id', 'learning_concept_id'], 'material_concept_primary');
            $table->index(['learning_concept_id', 'learning_material_id'], 'concept_material_lookup_idx');
        });

        Schema::create('question_concepts', function (Blueprint $table): void {
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('learning_concept_id')->constrained('learning_concepts')->cascadeOnDelete();
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->string('difficulty_level', 20)->nullable();
            $table->timestamps();

            $table->primary(['question_id', 'learning_concept_id'], 'question_concept_primary');
            $table->index(['learning_concept_id', 'question_id'], 'concept_question_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_concepts');
        Schema::dropIfExists('material_concepts');
        Schema::dropIfExists('concept_prerequisites');
        Schema::dropIfExists('learning_concepts');
    }
};
