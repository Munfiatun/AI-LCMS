<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningConcept;
use App\Models\LearningMaterial;
use App\Models\LearningObjective;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningConceptDomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_concept_belongs_to_course_and_optional_learning_objective(): void
    {
        [$course, $objective] = $this->courseAndObjective();

        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'learning_objective_id' => $objective->id,
            'code' => 'C01',
            'name' => 'Variabel dan Tipe Data',
            'order' => 1,
        ]);

        $this->assertTrue($concept->course->is($course));
        $this->assertTrue($concept->learningObjective->is($objective));
        $this->assertTrue($course->learningConcepts->contains($concept));
        $this->assertTrue($objective->learningConcepts->contains($concept));
    }

    public function test_learning_objective_deletion_preserves_concept_and_clears_objective_reference(): void
    {
        [$course, $objective] = $this->courseAndObjective();

        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'learning_objective_id' => $objective->id,
            'code' => 'C01',
        ]);

        $objective->delete();

        $this->assertDatabaseHas('learning_concepts', [
            'id' => $concept->id,
            'course_id' => $course->id,
            'learning_objective_id' => null,
        ]);
    }

    public function test_concept_can_store_ordered_prerequisites_with_weight(): void
    {
        [$course, $objective] = $this->courseAndObjective();

        $variables = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'learning_objective_id' => $objective->id,
            'code' => 'C01',
            'name' => 'Variabel',
            'order' => 1,
        ]);
        $operators = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'learning_objective_id' => $objective->id,
            'code' => 'C02',
            'name' => 'Operator',
            'order' => 2,
        ]);
        $branching = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'learning_objective_id' => $objective->id,
            'code' => 'C03',
            'name' => 'Percabangan',
            'order' => 3,
        ]);

        $branching->prerequisites()->attach([
            $variables->id => ['weight' => 1.00, 'order' => 1],
            $operators->id => ['weight' => 0.80, 'order' => 2],
        ]);

        $branching->load('prerequisites');

        $this->assertCount(2, $branching->prerequisites);
        $this->assertSame('C01', $branching->prerequisites[0]->code);
        $this->assertSame('C02', $branching->prerequisites[1]->code);
        $this->assertSame(0.80, (float) $branching->prerequisites[1]->pivot->weight);
        $this->assertTrue($variables->requiredByConcepts()->whereKey($branching->id)->exists());
    }

    public function test_material_can_be_mapped_to_concept_with_relevance_weight(): void
    {
        [$course, $objective] = $this->courseAndObjective();
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'learning_objective_id' => $objective->id,
            'code' => 'C03',
        ]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id]);

        $material->learningConcepts()->attach($concept->id, ['relevance_weight' => 0.90]);
        $material->load('learningConcepts');

        $this->assertTrue($material->learningConcepts->first()->is($concept));
        $this->assertSame(0.90, (float) $material->learningConcepts->first()->pivot->relevance_weight);
        $this->assertTrue($concept->materials()->whereKey($material->id)->exists());
    }

    public function test_question_can_be_mapped_to_concept_with_weight_and_difficulty(): void
    {
        [$course, $objective, $instructor] = $this->courseAndObjective();
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'learning_objective_id' => $objective->id,
            'code' => 'C04',
        ]);
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id]);

        $question->learningConcepts()->attach($concept->id, [
            'weight' => 1.25,
            'difficulty_level' => Question::DIFFICULTY_MEDIUM,
        ]);
        $question->load('learningConcepts');

        $mappedConcept = $question->learningConcepts->first();
        $this->assertTrue($mappedConcept->is($concept));
        $this->assertSame(1.25, (float) $mappedConcept->pivot->weight);
        $this->assertSame(Question::DIFFICULTY_MEDIUM, $mappedConcept->pivot->difficulty_level);
        $this->assertTrue($concept->questions()->whereKey($question->id)->exists());
    }

    /** @return array{Course, LearningObjective, User} */
    private function courseAndObjective(): array
    {
        $instructor = User::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $objective = LearningObjective::create([
            'course_id' => $course->id,
            'code' => 'LO-01',
            'title' => 'Menerapkan struktur kontrol pemrograman',
            'order' => 1,
            'is_active' => true,
        ]);

        return [$course, $objective, $instructor];
    }
}
