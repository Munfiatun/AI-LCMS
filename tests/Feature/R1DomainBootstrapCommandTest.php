<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningConcept;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class R1DomainBootstrapCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_change_domain_data(): void
    {
        $course = $this->course();

        $this->artisan('research:r1-bootstrap', ['course' => (string) $course->id])
            ->assertSuccessful();

        $this->assertDatabaseCount('learning_objectives', 0);
        $this->assertDatabaseCount('learning_concepts', 0);
        $this->assertDatabaseCount('concept_prerequisites', 0);
    }

    public function test_apply_creates_six_concepts_three_objectives_and_prerequisites_idempotently(): void
    {
        $course = $this->course();

        $this->artisan('research:r1-bootstrap', [
            'course' => (string) $course->id,
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame(3, $course->learningObjectives()->count());
        $this->assertSame(6, $course->learningConcepts()->count());
        $this->assertDatabaseHas('learning_concepts', [
            'course_id' => $course->id,
            'code' => 'C06',
            'name' => 'Program Sederhana',
        ]);

        $c05 = LearningConcept::where('course_id', $course->id)->where('code', 'C05')->firstOrFail();
        $this->assertSame(2, $c05->prerequisites()->count());

        $this->artisan('research:r1-bootstrap', [
            'course' => (string) $course->id,
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame(3, $course->learningObjectives()->count());
        $this->assertSame(6, $course->learningConcepts()->count());
        $this->assertSame(2, $c05->prerequisites()->count());
    }

    public function test_map_content_maps_clear_unmapped_items_and_preserves_manual_mapping(): void
    {
        $course = $this->course();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'title' => 'Dasar Perulangan For dan While',
            'description' => 'Latihan loop dan iterasi.',
        ]);
        $manualMaterial = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'title' => 'Materi khusus manual',
        ]);
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $course->instructor_id,
            'course_id' => $course->id,
        ]);
        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Apa hasil dari operator perbandingan berikut?',
            'topic' => 'Operator dan ekspresi',
            'difficulty' => Question::DIFFICULTY_MEDIUM,
        ]);

        $this->artisan('research:r1-bootstrap', [
            'course' => (string) $course->id,
            '--apply' => true,
        ])->assertSuccessful();

        $c01 = LearningConcept::where('course_id', $course->id)->where('code', 'C01')->firstOrFail();
        $manualMaterial->learningConcepts()->attach($c01->id, ['relevance_weight' => 1.00]);

        $this->artisan('research:r1-bootstrap', [
            'course' => (string) $course->id,
            '--apply' => true,
            '--map-content' => true,
        ])->assertSuccessful();

        $this->assertSame('C04', $material->fresh()->learningConcepts()->firstOrFail()->code);
        $this->assertSame('C01', $manualMaterial->fresh()->learningConcepts()->firstOrFail()->code);
        $this->assertSame('C02', $question->fresh()->learningConcepts()->firstOrFail()->code);
    }

    private function course(): Course
    {
        $instructor = User::factory()->create();

        return Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Pemrograman Dasar',
            'slug' => 'pemrograman-dasar',
        ]);
    }
}
