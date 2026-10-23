<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\ExperimentAssignment;
use App\Models\LearningMaterial;
use App\Models\LearningObjective;
use App\Models\ResearchEvent;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResearchInstrumentationTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $otherInstructor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();

        $this->instructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'is_active' => true,
        ]);
        $this->otherInstructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'is_active' => true,
        ]);
        $this->student = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);
    }

    public function test_instructor_can_assign_enrolled_student_to_research_condition(): void
    {
        $course = $this->courseFor($this->instructor);
        $this->enroll($course, $this->student);

        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.research.show', $course))
            ->assertOk()
            ->assertSee('Pengaturan Kondisi Eksperimen')
            ->assertSee($this->student->name)
            ->assertSee('Adaptive')
            ->assertSee('Non-Adaptive');

        $this->actingAs($this->instructor)
            ->put(route('instructor.courses.research.assignment', $course), [
                'student_id' => $this->student->id,
                'condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
                'class_cluster' => 'X PPLG 1',
                'assignment_version' => 'pilot-v1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('experiment_assignments', [
            'course_id' => $course->id,
            'student_id' => $this->student->id,
            'condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
            'class_cluster' => 'X PPLG 1',
            'assignment_version' => 'pilot-v1',
            'assigned_by' => $this->instructor->id,
        ]);
    }

    public function test_instructor_cannot_assign_student_who_is_not_enrolled_in_course(): void
    {
        $course = $this->courseFor($this->instructor);

        $this->actingAs($this->instructor)
            ->put(route('instructor.courses.research.assignment', $course), [
                'student_id' => $this->student->id,
                'condition' => ExperimentAssignment::CONDITION_NON_ADAPTIVE,
                'class_cluster' => 'X PPLG 2',
                'assignment_version' => 'v1',
            ])
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('experiment_assignments', 0);
    }

    public function test_other_instructor_cannot_open_or_change_research_setup(): void
    {
        $course = $this->courseFor($this->instructor);
        $this->enroll($course, $this->student);

        $this->actingAs($this->otherInstructor)
            ->get(route('instructor.courses.research.show', $course))
            ->assertForbidden();

        $this->actingAs($this->otherInstructor)
            ->put(route('instructor.courses.research.assignment', $course), [
                'student_id' => $this->student->id,
                'condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
                'assignment_version' => 'v1',
            ])
            ->assertForbidden();
    }

    public function test_material_open_event_captures_assignment_and_objective_snapshot(): void
    {
        $course = $this->courseFor($this->instructor, Course::STATUS_PUBLISHED);
        $section = CourseSection::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $objective = LearningObjective::create([
            'course_id' => $course->id,
            'code' => 'LO-01',
            'title' => 'Menjelaskan konsep algoritma',
            'order' => 1,
            'is_active' => true,
        ]);
        $material->learningObjectives()->sync([$objective->id]);
        $this->enroll($course, $this->student);

        $assignment = ExperimentAssignment::create([
            'course_id' => $course->id,
            'student_id' => $this->student->id,
            'condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
            'class_cluster' => 'X PPLG 1',
            'assignment_version' => 'pilot-v1',
            'assigned_by' => $this->instructor->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.materials.show', [$course, $material]))
            ->assertOk();

        $event = ResearchEvent::query()->where('event_type', ResearchEvent::MATERIAL_OPENED)->firstOrFail();

        $this->assertSame($this->student->id, $event->student_id);
        $this->assertSame($course->id, $event->course_id);
        $this->assertSame($objective->id, $event->learning_objective_id);
        $this->assertSame($assignment->id, $event->experiment_assignment_id);
        $this->assertSame(ExperimentAssignment::CONDITION_ADAPTIVE, $event->treatment_condition);
        $this->assertSame('pilot-v1', $event->assignment_version);
        $this->assertSame('LearningMaterial', $event->source_type);
        $this->assertSame($material->id, $event->source_id);
        $this->assertSame([$objective->id], $event->metadata['learning_objective_ids']);
    }

    public function test_material_completion_records_one_milestone_even_if_request_is_repeated(): void
    {
        $course = $this->courseFor($this->instructor, Course::STATUS_PUBLISHED);
        $section = CourseSection::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $this->enroll($course, $this->student);

        $this->actingAs($this->student)
            ->post(route('student.materials.complete', $material))
            ->assertRedirect();

        $this->actingAs($this->student)
            ->post(route('student.materials.complete', $material))
            ->assertRedirect();

        $this->assertDatabaseHas('research_events', [
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'event_type' => ResearchEvent::MATERIAL_COMPLETED,
            'source_type' => 'LearningMaterial',
            'source_id' => $material->id,
        ]);

        $this->assertSame(1, ResearchEvent::query()
            ->where('student_id', $this->student->id)
            ->where('course_id', $course->id)
            ->where('event_type', ResearchEvent::MATERIAL_COMPLETED)
            ->where('source_type', 'LearningMaterial')
            ->where('source_id', $material->id)
            ->count());
    }

    private function courseFor(User $instructor, string $status = Course::STATUS_DRAFT): Course
    {
        return Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => $status,
            'published_at' => $status === Course::STATUS_PUBLISHED ? now() : null,
        ]);
    }

    private function enroll(Course $course, User $student): CourseEnrollment
    {
        return CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);
    }
}
