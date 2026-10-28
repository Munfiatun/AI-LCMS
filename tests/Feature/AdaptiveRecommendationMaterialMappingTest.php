<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearningConcept;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\Learning\Adaptive\AdaptiveLearningResourceResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveRecommendationMaterialMappingTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_INSTRUCTOR)->value('id'),
            'is_active' => true,
        ]);
        $this->student = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_STUDENT)->value('id'),
            'is_active' => true,
            'name' => 'Ruby',
        ]);
    }

    public function test_it_selects_highest_relevance_published_material_in_the_same_course(): void
    {
        [$course, $concept, $recommendation] = $this->context('remediation');
        $section = $this->section($course);

        $lower = $this->material($section, 'Materi Relevance Rendah', LearningMaterial::STATUS_PUBLISHED, 1);
        $higher = $this->material($section, 'Materi Relevance Tinggi', LearningMaterial::STATUS_PUBLISHED, 2);
        $draft = $this->material($section, 'Materi Draft', LearningMaterial::STATUS_DRAFT, 3);

        $lower->learningConcepts()->attach($concept->id, ['relevance_weight' => 0.40]);
        $higher->learningConcepts()->attach($concept->id, ['relevance_weight' => 0.90]);
        $draft->learningConcepts()->attach($concept->id, ['relevance_weight' => 1.00]);

        $otherCourse = $this->course('Course Lain');
        $otherSection = $this->section($otherCourse);
        $otherMaterial = $this->material($otherSection, 'Materi Course Lain', LearningMaterial::STATUS_PUBLISHED, 1);
        $otherMaterial->learningConcepts()->attach($concept->id, ['relevance_weight' => 1.00]);

        $resolved = app(AdaptiveLearningResourceResolver::class)->materialFor($recommendation->load('targetConcept'));

        $this->assertNotNull($resolved);
        $this->assertSame($higher->id, $resolved->id);
        $this->assertSame(0.90, (float) $resolved->pivot->relevance_weight);
    }

    public function test_additional_diagnostic_does_not_force_a_material_mapping(): void
    {
        [$course, $concept, $recommendation] = $this->context('additional_diagnostic');
        $section = $this->section($course);
        $material = $this->material($section, 'Materi Konsep', LearningMaterial::STATUS_PUBLISHED, 1);
        $material->learningConcepts()->attach($concept->id, ['relevance_weight' => 1.00]);

        $resolved = app(AdaptiveLearningResourceResolver::class)->materialFor($recommendation->load('targetConcept'));

        $this->assertNull($resolved);
    }

    public function test_workspace_links_recommendation_directly_to_the_resolved_material(): void
    {
        [$course, $concept] = $this->courseAndConcept();
        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        $section = $this->section($course);
        $material = $this->material($section, 'Latihan Variabel dan Tipe Data', LearningMaterial::STATUS_PUBLISHED, 1);
        $material->learningConcepts()->attach($concept->id, ['relevance_weight' => 0.95]);

        $this->recommendation($course, $concept, 'remediation');

        $this->actingAs($this->student)
            ->get(route('student.adaptive-recommendations.index'))
            ->assertOk()
            ->assertSee('Latihan Variabel dan Tipe Data')
            ->assertSee('Mulai Materi')
            ->assertSee(route('student.materials.show', [$course, $material]), false)
            ->assertSee('0.95');
    }

    /** @return array{Course, LearningConcept, AdaptiveRecommendation} */
    private function context(string $action): array
    {
        [$course, $concept] = $this->courseAndConcept();

        return [$course, $concept, $this->recommendation($course, $concept, $action)];
    }

    /** @return array{Course, LearningConcept} */
    private function courseAndConcept(): array
    {
        $course = $this->course('PEMOGRAMAN DASAR');
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C01',
            'name' => 'Variabel dan Tipe Data',
            'order' => 1,
            'is_active' => true,
        ]);

        return [$course, $concept];
    }

    private function course(string $title): Course
    {
        return Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'title' => $title,
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    private function section(Course $course): CourseSection
    {
        return CourseSection::factory()->create([
            'course_id' => $course->id,
            'title' => 'Materi Inti',
            'order' => 1,
            'status' => 'active',
        ]);
    }

    private function material(CourseSection $section, string $title, string $status, int $order): LearningMaterial
    {
        return LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'title' => $title,
            'status' => $status,
            'order' => $order,
            'published_at' => $status === LearningMaterial::STATUS_PUBLISHED ? now() : null,
        ]);
    }

    private function recommendation(Course $course, LearningConcept $concept, string $action): AdaptiveRecommendation
    {
        $baseline = app(AdaptiveAlgorithmRegistry::class)->baseline();

        return AdaptiveRecommendation::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'source_concept_id' => $concept->id,
            'target_concept_id' => $concept->id,
            'algorithm_version_id' => $baseline->id,
            'action' => $action,
            'reason_code' => $action === 'additional_diagnostic'
                ? 'current_evidence_insufficient'
                : 'mastery_low',
            'decision_fingerprint' => hash('sha256', implode('|', [
                $this->student->id,
                $course->id,
                $concept->id,
                $action,
                microtime(true),
            ])),
            'status' => AdaptiveRecommendation::STATUS_ACTIVE,
            'inputs' => ['mastery_score' => 30, 'evidence_count' => 3],
            'metadata' => ['target_concept_code' => $concept->code],
            'generated_at' => now(),
        ]);
    }
}
