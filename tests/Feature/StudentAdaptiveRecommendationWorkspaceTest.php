<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LearningConcept;
use App\Models\Role;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAdaptiveRecommendationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    private User $otherStudent;

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
        $this->student = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
            'name' => 'Ruby',
        ]);
        $this->otherStudent = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
            'name' => 'Siswa Lain',
        ]);
    }

    public function test_student_sees_active_baseline_recommendations_for_enrolled_course(): void
    {
        $course = $this->course('PEMROGRAMAN DASAR');
        $this->enroll($this->student, $course);
        [$source, $target] = $this->conceptPair($course);

        $baseline = app(AdaptiveAlgorithmRegistry::class)->baseline();
        $this->recommendation(
            student: $this->student,
            course: $course,
            source: $source,
            target: $target,
            algorithmId: $baseline->id,
            action: 'prerequisite_remediation',
            reason: 'prerequisite_below_threshold',
            fingerprint: str_repeat('a', 64),
        );

        $this->actingAs($this->student)
            ->get(route('student.adaptive-recommendations.index'))
            ->assertOk()
            ->assertSee('Rekomendasi Belajar untuk Ruby')
            ->assertSee('PEMROGRAMAN DASAR')
            ->assertSee('Perkuat Konsep Prasyarat')
            ->assertSee('C02')
            ->assertSee('Operator dan Ekspresi')
            ->assertSee('C01')
            ->assertSee('Variabel dan Tipe Data')
            ->assertSee('Konsep prasyarat masih perlu diperkuat')
            ->assertSee('rule-based-v1@1.0.0');
    }

    public function test_workspace_hides_other_students_unenrolled_and_experimental_recommendations(): void
    {
        $course = $this->course('Course Aktif');
        $this->enroll($this->student, $course);
        [$source, $target] = $this->conceptPair($course);

        $otherCourse = $this->course('Course Tidak Diikuti');
        [$otherSource, $otherTarget] = $this->conceptPair($otherCourse, 'X01', 'X02');

        $registry = app(AdaptiveAlgorithmRegistry::class);
        $baseline = $registry->baseline();
        $fuzzy = $registry->comparison();

        $this->recommendation(
            $this->otherStudent,
            $course,
            $source,
            $target,
            $baseline->id,
            'remediation',
            'mastery_low',
            str_repeat('b', 64),
        );
        $this->recommendation(
            $this->student,
            $otherCourse,
            $otherSource,
            $otherTarget,
            $baseline->id,
            'reinforcement',
            'mastery_medium',
            str_repeat('c', 64),
        );
        $this->recommendation(
            $this->student,
            $course,
            $source,
            $target,
            $fuzzy->id,
            'enrichment',
            'fuzzy_mamdani_centroid',
            str_repeat('d', 64),
        );

        $this->actingAs($this->student)
            ->get(route('student.adaptive-recommendations.index'))
            ->assertOk()
            ->assertSee('Belum ada rekomendasi adaptif aktif')
            ->assertDontSee('Course Tidak Diikuti')
            ->assertDontSee('fuzzy-v2@2.0.0-experimental');
    }

    public function test_non_student_cannot_open_student_recommendation_workspace(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('student.adaptive-recommendations.index'))
            ->assertForbidden();
    }

    private function course(string $title): Course
    {
        return Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'title' => $title,
            'status' => 'published',
        ]);
    }

    private function enroll(User $student, Course $course): CourseEnrollment
    {
        return CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);
    }

    /** @return array{LearningConcept, LearningConcept} */
    private function conceptPair(Course $course, string $firstCode = 'C01', string $secondCode = 'C02'): array
    {
        $first = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => $firstCode,
            'name' => $firstCode === 'C01' ? 'Variabel dan Tipe Data' : $firstCode,
            'order' => 1,
            'is_active' => true,
        ]);
        $second = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => $secondCode,
            'name' => $secondCode === 'C02' ? 'Operator dan Ekspresi' : $secondCode,
            'order' => 2,
            'is_active' => true,
        ]);

        return [$second, $first];
    }

    private function recommendation(
        User $student,
        Course $course,
        LearningConcept $source,
        LearningConcept $target,
        int $algorithmId,
        string $action,
        string $reason,
        string $fingerprint,
    ): AdaptiveRecommendation {
        return AdaptiveRecommendation::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'source_concept_id' => $source->id,
            'target_concept_id' => $target->id,
            'algorithm_version_id' => $algorithmId,
            'action' => $action,
            'reason_code' => $reason,
            'decision_fingerprint' => $fingerprint,
            'status' => AdaptiveRecommendation::STATUS_ACTIVE,
            'inputs' => ['mastery_score' => 50, 'evidence_count' => 2],
            'metadata' => ['target_concept_code' => $target->code],
            'generated_at' => now(),
        ]);
    }
}
