<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiment_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('condition', 32);
            $table->string('class_cluster', 100)->nullable();
            $table->string('assignment_version', 50)->default('v1');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['course_id', 'student_id'], 'experiment_assignment_course_student_unique');
            $table->index(['course_id', 'condition']);
        });

        Schema::create('research_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_objective_id')->nullable()->constrained('learning_objectives')->nullOnDelete();
            $table->foreignId('experiment_assignment_id')->nullable()->constrained('experiment_assignments')->nullOnDelete();
            $table->string('event_type', 64);
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('treatment_condition', 32)->nullable();
            $table->string('assignment_version', 50)->nullable();
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'course_id', 'occurred_at'], 'research_events_student_course_time_idx');
            $table->index(['course_id', 'event_type', 'occurred_at'], 'research_events_course_type_time_idx');
            $table->index(['source_type', 'source_id'], 'research_events_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_events');
        Schema::dropIfExists('experiment_assignments');
    }
};
