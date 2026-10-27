<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticAssessmentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $this->instructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'is_active' => true,
        ]);
    }

    public function test_instructor_only_sees_own_courses_in_diagnostic_workspace(): void
    {
        $ownCourse = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Pemrograman Dasar',
        ]);
        Quiz::factory()->create([
            'course_id' => $ownCourse->id,
            'title' => 'Diagnostik Awal',
            'assessment_type' => Quiz::ASSESSMENT_FORMATIVE,
        ]);

        $otherInstructor = User::factory()->create();
        $otherCourse = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'title' => 'Course Milik Orang Lain',
        ]);
        Quiz::factory()->create([
            'course_id' => $otherCourse->id,
            'title' => 'Quiz Rahasia',
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.diagnostics.index', ['course_id' => $ownCourse->id]))
            ->assertOk()
            ->assertSee('Diagnostic Assessment Workspace')
            ->assertSee('Diagnostik Awal')
            ->assertDontSee('Quiz Rahasia');
    }

    public function test_draft_quiz_can_be_marked_as_diagnostic_from_workspace(): void
    {
        $course = $this->courseForInstructor();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'status' => 'draft',
            'assessment_type' => Quiz::ASSESSMENT_FORMATIVE,
        ]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.diagnostics.type.update', $quiz), [
                'assessment_type' => Quiz::ASSESSMENT_DIAGNOSTIC,
            ])
            ->assertRedirect(route('instructor.diagnostics.index', ['course_id' => $course->id]));

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'assessment_type' => Quiz::ASSESSMENT_DIAGNOSTIC,
        ]);
    }

    public function test_published_quiz_assessment_type_is_locked(): void
    {
        $course = $this->courseForInstructor();
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'status' => 'published',
            'assessment_type' => Quiz::ASSESSMENT_FORMATIVE,
        ]);

        $this->actingAs($this->instructor)
            ->from(route('instructor.diagnostics.index', ['course_id' => $course->id]))
            ->put(route('instructor.diagnostics.type.update', $quiz), [
                'assessment_type' => Quiz::ASSESSMENT_DIAGNOSTIC,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('quiz');

        $this->assertSame(Quiz::ASSESSMENT_FORMATIVE, $quiz->fresh()->assessment_type);
    }

    public function test_diagnostic_quiz_publish_requires_concept_mapping_for_every_question(): void
    {
        $course = $this->courseForInstructor();
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $this->instructor->id,
            'course_id' => $course->id,
        ]);
        $question = $this->approvedQuestionWithOptions($bank);
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'status' => 'draft',
            'assessment_type' => Quiz::ASSESSMENT_DIAGNOSTIC,
            'total_questions' => 1,
            'max_attempts' => 1,
            'passing_score' => 70,
        ]);
        QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_id' => $question->id,
            'order' => 1,
            'points' => 10,
        ]);

        $this->actingAs($this->instructor)
            ->from(route('instructor.quizzes.show', $quiz))
            ->post(route('instructor.quizzes.publish', $quiz))
            ->assertRedirect()
            ->assertSessionHasErrors('quiz');

        $this->assertSame('draft', $quiz->fresh()->status);

        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C01',
        ]);
        $question->learningConcepts()->attach($concept->id, [
            'weight' => 1.00,
            'difficulty_level' => Question::DIFFICULTY_MEDIUM,
        ]);

        $this->actingAs($this->instructor)
            ->post(route('instructor.quizzes.publish', $quiz))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('published', $quiz->fresh()->status);
    }

    private function courseForInstructor(): Course
    {
        return Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Pemrograman Dasar',
            'status' => 'published',
        ]);
    }

    private function approvedQuestionWithOptions(QuestionBank $bank): Question
    {
        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Manakah deklarasi variabel integer yang benar?',
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'difficulty' => Question::DIFFICULTY_MEDIUM,
            'status' => Question::STATUS_APPROVED,
            'answer_source' => Question::SOURCE_EXPLICIT,
            'needs_review' => false,
            'points' => 10,
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'int umur = 17;',
            'is_correct' => true,
            'order' => 1,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'float umur = "17";',
            'is_correct' => false,
            'order' => 2,
        ]);

        return $question;
    }
}
