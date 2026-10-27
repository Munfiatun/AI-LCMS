<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\MasteryHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasteryProfileWorkspaceTest extends TestCase
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
            'name' => 'Siswa Diagnostic',
        ]);
    }

    public function test_instructor_sees_students_with_mastery_evidence_for_own_course_only(): void
    {
        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Pemrograman Dasar',
        ]);
        $otherCourse = Course::factory()->create([
            'instructor_id' => $this->otherInstructor->id,
            'title' => 'Course Lain',
        ]);

        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C01',
            'name' => 'Variabel dan Tipe Data',
        ]);
        $otherConcept = LearningConcept::factory()->create([
            'course_id' => $otherCourse->id,
            'code' => 'X01',
        ]);

        LearnerMastery::create([
            'student_id' => $this->student->id,
            'concept_id' => $concept->id,
            'mastery_score' => 33.33,
            'status' => 'observed',
            'evidence_count' => 3,
            'last_evidence_at' => now(),
        ]);
        LearnerMastery::create([
            'student_id' => $this->student->id,
            'concept_id' => $otherConcept->id,
            'mastery_score' => 100,
            'status' => 'observed',
            'evidence_count' => 1,
            'last_evidence_at' => now(),
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.mastery.index', ['course_id' => $course->id]))
            ->assertOk()
            ->assertSee('Profil Penguasaan Konsep Siswa')
            ->assertSee('Siswa Diagnostic')
            ->assertSee('1 concept observed')
            ->assertSee('3 evidence')
            ->assertDontSee('Course Lain');
    }

    public function test_profile_shows_all_active_concepts_and_explainable_history(): void
    {
        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Pemrograman Dasar',
        ]);
        $observed = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C01',
            'name' => 'Variabel dan Tipe Data',
            'order' => 1,
            'is_active' => true,
        ]);
        LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C02',
            'name' => 'Operator dan Ekspresi',
            'order' => 2,
            'is_active' => true,
        ]);

        LearnerMastery::create([
            'student_id' => $this->student->id,
            'concept_id' => $observed->id,
            'mastery_score' => 33.33,
            'status' => 'observed',
            'evidence_count' => 3,
            'last_evidence_at' => now(),
        ]);

        MasteryHistory::create([
            'student_id' => $this->student->id,
            'concept_id' => $observed->id,
            'before_score' => null,
            'after_score' => 33.33,
            'evidence_count' => 3,
            'evidence_type' => 'diagnostic_quiz_attempt',
            'evidence_id' => 99,
            'model_version' => 'diagnostic-weighted-v1',
            'evidence_payload' => ['quiz_id' => 10, 'calculation' => 'weighted_correctness'],
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.mastery.show', [
                'course' => $course,
                'student' => $this->student,
            ]))
            ->assertOk()
            ->assertSee('C01')
            ->assertSee('Variabel dan Tipe Data')
            ->assertSee('33.33%')
            ->assertSee('3 evidence')
            ->assertSee('C02')
            ->assertSee('Operator dan Ekspresi')
            ->assertSee('belum_observed')
            ->assertSee('diagnostic_quiz_attempt')
            ->assertSee('diagnostic-weighted-v1')
            ->assertSee('Attempt #99');
    }

    public function test_instructor_cannot_open_mastery_profile_from_another_instructors_course(): void
    {
        $foreignCourse = Course::factory()->create([
            'instructor_id' => $this->otherInstructor->id,
        ]);
        $concept = LearningConcept::factory()->create([
            'course_id' => $foreignCourse->id,
        ]);

        LearnerMastery::create([
            'student_id' => $this->student->id,
            'concept_id' => $concept->id,
            'mastery_score' => 50,
            'status' => 'observed',
            'evidence_count' => 2,
            'last_evidence_at' => now(),
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.mastery.show', [
                'course' => $foreignCourse,
                'student' => $this->student,
            ]))
            ->assertForbidden();
    }
}
