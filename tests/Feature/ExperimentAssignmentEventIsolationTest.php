<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\ExperimentAssignment;
use App\Models\LearningMaterial;
use App\Models\ResearchEvent;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperimentAssignmentEventIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_material_access_records_current_assignment_without_rewriting_previous_event_snapshots(): void
    {
        $this->freezeTime();
        [$instructor, $student, $course, $material] = $this->context();
        $assignment = $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'pilot-v1');

        $this->actingAs($student)
            ->get(route('student.materials.show', [$course, $material]))
            ->assertOk()
            ->assertSee($material->title);

        $adaptiveEvent = ResearchEvent::query()->sole();
        $adaptiveSnapshot = $adaptiveEvent->getRawOriginal();
        $this->assertDatabaseHas('research_events', [
            'id' => $adaptiveEvent->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'experiment_assignment_id' => $assignment->id,
            'event_type' => ResearchEvent::MATERIAL_OPENED,
            'source_type' => 'LearningMaterial',
            'source_id' => $material->id,
            'treatment_condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
            'assignment_version' => 'pilot-v1',
        ]);

        $this->travel(1)->minute();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'pilot-v2');

        $this->actingAs($student)
            ->get(route('student.materials.show', [
                'course' => $course,
                'material' => $material,
                'treatment_condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
                'assignment_version' => 'spoofed-version',
            ]))
            ->assertOk()
            ->assertSee($material->title);

        $controlEvent = ResearchEvent::query()->latest('id')->firstOrFail();
        $controlSnapshot = $controlEvent->getRawOriginal();
        $this->assertDatabaseHas('research_events', [
            'id' => $controlEvent->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'experiment_assignment_id' => $assignment->id,
            'event_type' => ResearchEvent::MATERIAL_OPENED,
            'source_id' => $material->id,
            'treatment_condition' => ExperimentAssignment::CONDITION_NON_ADAPTIVE,
            'assignment_version' => 'pilot-v2',
        ]);
        $this->assertSame($adaptiveSnapshot, $adaptiveEvent->refresh()->getRawOriginal());

        $this->travel(1)->minute();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'pilot-v3');

        $this->actingAs($student)
            ->get(route('student.materials.show', [$course, $material]))
            ->assertOk()
            ->assertSee($material->title);

        $this->assertDatabaseHas('research_events', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'experiment_assignment_id' => $assignment->id,
            'event_type' => ResearchEvent::MATERIAL_OPENED,
            'source_id' => $material->id,
            'treatment_condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
            'assignment_version' => 'pilot-v3',
        ]);
        $this->assertSame($adaptiveSnapshot, $adaptiveEvent->refresh()->getRawOriginal());
        $this->assertSame($controlSnapshot, $controlEvent->refresh()->getRawOriginal());
        $this->assertDatabaseCount('research_events', 3);
        $this->assertDatabaseCount('experiment_assignments', 1);
        $this->assertDatabaseCount('adaptive_recommendations', 0);
    }

    public function test_repeated_material_completion_preserves_original_milestone_across_assignment_changes(): void
    {
        $this->freezeTime();
        [$instructor, $student, $course, $material] = $this->context();
        $assignment = $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'pilot-v1');

        $this->actingAs($student)
            ->post(route('student.materials.complete', $material))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $milestone = ResearchEvent::query()->sole();
        $snapshot = $milestone->getRawOriginal();
        $this->assertDatabaseHas('research_events', [
            'id' => $milestone->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'experiment_assignment_id' => $assignment->id,
            'event_type' => ResearchEvent::MATERIAL_COMPLETED,
            'source_type' => 'LearningMaterial',
            'source_id' => $material->id,
            'treatment_condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
            'assignment_version' => 'pilot-v1',
        ]);

        $this->travel(1)->minute();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'pilot-v2');

        $this->actingAs($student)
            ->post(route('student.materials.complete', $material))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('research_events', 1);
        $this->assertSame($snapshot, $milestone->refresh()->getRawOriginal());

        $this->travel(1)->minute();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'pilot-v3');

        $this->actingAs($student)
            ->post(route('student.materials.complete', $material))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('research_events', 1);
        $this->assertSame($snapshot, $milestone->refresh()->getRawOriginal());
        $this->assertDatabaseCount('material_progress', 1);
        $this->assertDatabaseHas('material_progress', [
            'student_id' => $student->id,
            'learning_material_id' => $material->id,
            'status' => 'completed',
        ]);
    }

    /** @return array{User, User, Course, LearningMaterial} */
    private function context(): array
    {
        $this->seed(RoleSeeder::class);
        $instructor = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail()->id,
            'is_active' => true,
        ]);
        $student = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_STUDENT)->firstOrFail()->id,
            'is_active' => true,
        ]);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $section = CourseSection::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'title' => 'Materi Bersama untuk Kedua Kelompok',
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        return [$instructor, $student, $course, $material];
    }

    private function assign(User $instructor, User $student, Course $course, string $condition, string $version): ExperimentAssignment
    {
        $this->actingAs($instructor)
            ->put(route('instructor.courses.research.assignment', $course), [
                'student_id' => $student->id,
                'condition' => $condition,
                'assignment_version' => $version,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('experiment_assignments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'condition' => $condition,
            'assignment_version' => $version,
            'assigned_by' => $instructor->id,
        ]);

        return ExperimentAssignment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->sole();
    }
}
