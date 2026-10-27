<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adaptive_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('source_concept_id')->constrained('learning_concepts')->cascadeOnDelete();
            $table->foreignId('target_concept_id')->nullable()->constrained('learning_concepts')->nullOnDelete();
            $table->foreignId('algorithm_version_id')->constrained('adaptive_algorithm_versions')->restrictOnDelete();
            $table->string('action', 64);
            $table->string('reason_code', 96);
            $table->string('decision_fingerprint', 64)->unique();
            $table->string('status', 32)->default('active');
            $table->json('inputs');
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'course_id', 'status']);
            $table->index(['student_id', 'source_concept_id', 'algorithm_version_id', 'status'], 'adaptive_recommendation_active_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adaptive_recommendations');
    }
};
