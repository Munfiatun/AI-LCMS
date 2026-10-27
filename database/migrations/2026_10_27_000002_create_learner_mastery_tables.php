<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_mastery', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('concept_id')->constrained('learning_concepts')->cascadeOnDelete();
            $table->decimal('mastery_score', 5, 2)->default(0);
            $table->string('status', 32)->default('observed');
            $table->unsignedInteger('evidence_count')->default(0);
            $table->timestamp('last_evidence_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'concept_id']);
            $table->index(['concept_id', 'status']);
        });

        Schema::create('mastery_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('concept_id')->constrained('learning_concepts')->cascadeOnDelete();
            $table->decimal('before_score', 5, 2)->nullable();
            $table->decimal('after_score', 5, 2);
            $table->unsignedInteger('evidence_count')->default(0);
            $table->string('evidence_type', 64);
            $table->unsignedBigInteger('evidence_id');
            $table->string('model_version', 64);
            $table->json('evidence_payload')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'concept_id', 'evidence_type', 'evidence_id', 'model_version'],
                'mastery_history_evidence_unique'
            );
            $table->index(['student_id', 'concept_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mastery_history');
        Schema::dropIfExists('learner_mastery');
    }
};
