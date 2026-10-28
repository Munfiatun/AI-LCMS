<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use Database\Seeders\WebLearningDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebLearningDummySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_five_materials_with_twenty_relevant_questions_each_accessible_to_teacher(): void
    {
        [$teacher, $course] = $this->existingCourse();

        $this->seed(WebLearningDummySeeder::class);

        $materials = LearningMaterial::with('section')->get();
        $this->assertCount(5, $materials);
        $this->assertDatabaseCount('questions', 100);
        $this->assertDatabaseCount('question_options', 400);
        $this->assertDatabaseCount('quizzes', 5);
        $this->assertDatabaseCount('slidebooks', 5);
        $this->assertDatabaseCount('slides', 25);
        foreach ($materials as $material) {
            $this->assertSame($course->id, $material->section->course_id);
            $this->assertSame('published', $material->status);
            $quiz = Quiz::with('questions.options', 'questions.questionBank')->where('section_id', $material->section_id)->sole();
            $this->assertSame('draft', $quiz->status);
            $this->assertCount(20, $quiz->questions);
            $this->assertCount(20, $quiz->questions->pluck('question_text')->unique());
            foreach ($quiz->questions as $question) {
                $this->assertSame($teacher->id, $question->questionBank->instructor_id);
                $this->assertSame($material->title, $question->topic);
                $this->assertCount(4, $question->options);
                $this->assertCount(4, $question->options->pluck('option_text')->unique());
                $this->assertCount(1, $question->options->where('is_correct', true));
                $this->assertStringContainsString(e($question->explanation), $material->content);
            }
            $this->actingAs($teacher)->get(route('instructor.materials.edit', $material))->assertOk()->assertSee($material->title);
            $this->get(route('instructor.quizzes.show', $quiz))->assertOk()->assertSee($quiz->title);
        }
        $this->get(route('instructor.quizzes.index'))
            ->assertOk()
            ->assertSee('Buat Kuis dengan Bantuan AI')
            ->assertSee('name="total_questions"', false)
            ->assertSee('min="2"', false)
            ->assertSee('max="20"', false);
    }

    public function test_rerunning_seeder_does_not_duplicate_records_or_overwrite_existing_work(): void
    {
        [$teacher, $course] = $this->existingCourse();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $original = LearningMaterial::factory()->create(['section_id' => $section->id]);
        $before = $original->fresh()->toArray();
        $this->seed(WebLearningDummySeeder::class);
        $question = Question::firstOrFail();
        $question->update(['question_text' => 'Koreksi guru yang harus dipertahankan']);
        $quiz = Quiz::firstOrFail();
        $quiz->update(['status' => 'published', 'published_at' => now()]);
        $ids = QuestionOption::pluck('id')->all();

        $this->seed(WebLearningDummySeeder::class);

        $this->assertSame($before, $original->fresh()->toArray());
        $this->assertSame('Koreksi guru yang harus dipertahankan', $question->fresh()->question_text);
        $this->assertSame('published', $quiz->fresh()->status);
        $this->assertSame($ids, QuestionOption::pluck('id')->all());
        $this->assertDatabaseCount('learning_materials', 6);
        $this->assertDatabaseCount('course_sections', 6);
        $this->assertDatabaseCount('question_banks', 5);
        $this->assertDatabaseCount('questions', 100);
        $this->assertDatabaseCount('quiz_questions', 100);
        $this->assertDatabaseCount('quizzes', 5);
        $this->assertSame(5, Slidebook::count());
    }

    /** @return array{User, Course} */
    private function existingCourse(): array
    {
        $role = Role::create(['name' => 'instructor', 'label' => 'Instructor']);
        $teacher = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id, 'title' => 'Pemograman Web', 'slug' => 'pemograman-web', 'status' => 'published']);

        return [$teacher, $course];
    }
}
