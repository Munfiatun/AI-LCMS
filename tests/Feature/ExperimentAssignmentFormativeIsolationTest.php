<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\ExperimentAssignment;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\ResearchEvent;
use App\Models\Role;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperimentAssignmentFormativeIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_formative_submission_after_switch_to_control_updates_mastery_and_events_without_generating_recommendations(): void
    {
        $this->freezeTime();
        [$instructor, $student, $course, $bank, $concept] = $this->context();
        $assignment = $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'pilot-v1');
        [$diagnosticQuestionOne, , $diagnosticWrongOne] = $this->question($bank, $concept, 'Diagnosis pertama');
        [$diagnosticQuestionTwo, , $diagnosticWrongTwo] = $this->question($bank, $concept, 'Diagnosis kedua');
        $diagnostic = $this->quiz($course, [$diagnosticQuestionOne, $diagnosticQuestionTwo], Quiz::ASSESSMENT_DIAGNOSTIC);
        $diagnosticAttempt = $this->startAttempt($student, $diagnostic);

        $this->actingAs($student)
            ->post(route('student.quizzes.submit', [$diagnostic, $diagnosticAttempt]), [
                'answers' => [
                    $diagnosticQuestionOne->id => $diagnosticWrongOne->id,
                    $diagnosticQuestionTwo->id => $diagnosticWrongTwo->id,
                ],
            ])
            ->assertRedirect(route('student.quizzes.result', [$diagnostic, $diagnosticAttempt]))
            ->assertSessionHasNoErrors();

        $mastery = LearnerMastery::query()->where('student_id', $student->id)->where('concept_id', $concept->id)->sole();
        $this->assertSame(0.0, $mastery->mastery_score);
        $this->assertSame(2, $mastery->evidence_count);
        $recommendation = app(AdaptiveRecommendationService::class)->generateForCourse($student, $course)->sole();
        $this->assertSame('remediation', $recommendation->action);

        [$formativeQuestionOne, $formativeCorrectOne] = $this->question($bank, $concept, 'Formatif pertama');
        [$formativeQuestionTwo, $formativeCorrectTwo] = $this->question($bank, $concept, 'Formatif kedua');
        $formative = $this->quiz($course, [$formativeQuestionOne, $formativeQuestionTwo], Quiz::ASSESSMENT_FORMATIVE);
        $formativeAttempt = $this->startAttempt($student, $formative);
        $startedEvent = ResearchEvent::query()
            ->where('event_type', ResearchEvent::ASSESSMENT_STARTED)
            ->where('source_id', $formativeAttempt->id)
            ->sole();
        $startedSnapshot = $startedEvent->getRawOriginal();

        $this->travel(1)->minute();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'pilot-v2');
        $recommendationSnapshot = $recommendation->refresh()->getRawOriginal();

        $this->actingAs($student)
            ->post(route('student.quizzes.submit', [$formative, $formativeAttempt]), [
                'answers' => [
                    $formativeQuestionOne->id => $formativeCorrectOne->id,
                    $formativeQuestionTwo->id => $formativeCorrectTwo->id,
                ],
            ])
            ->assertRedirect(route('student.quizzes.result', [$formative, $formativeAttempt]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $formativeAttempt->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'percentage' => 100,
            'correct_count' => 2,
            'wrong_count' => 0,
        ]);
        $mastery->refresh();
        $this->assertSame(50.0, $mastery->mastery_score);
        $this->assertSame(4, $mastery->evidence_count);
        $this->assertDatabaseHas('mastery_history', [
            'student_id' => $student->id,
            'concept_id' => $concept->id,
            'evidence_type' => 'formative_quiz_attempt',
            'evidence_id' => $formativeAttempt->id,
            'before_score' => 0,
            'after_score' => 50,
        ]);
        $this->assertDatabaseHas('research_events', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'experiment_assignment_id' => $assignment->id,
            'event_type' => ResearchEvent::ASSESSMENT_SUBMITTED,
            'source_type' => 'QuizAttempt',
            'source_id' => $formativeAttempt->id,
            'treatment_condition' => ExperimentAssignment::CONDITION_NON_ADAPTIVE,
            'assignment_version' => 'pilot-v2',
        ]);
        $this->assertSame($startedSnapshot, $startedEvent->refresh()->getRawOriginal());
        $this->assertDatabaseHas('research_events', [
            'id' => $startedEvent->id,
            'treatment_condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
            'assignment_version' => 'pilot-v1',
        ]);
        $this->assertDatabaseCount('research_events', 4);
        $this->assertDatabaseCount('adaptive_recommendations', 1);
        $this->assertSame($recommendationSnapshot, $recommendation->refresh()->getRawOriginal());
        $this->assertDatabaseMissing('adaptive_recommendations', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => AdaptiveRecommendation::STATUS_ACTIVE,
        ]);

        $this->actingAs($student)
            ->get(route('student.adaptive-recommendations.index'))
            ->assertOk()
            ->assertViewHas('rows', fn ($rows): bool => $rows->isEmpty());
    }

    /** @return array{User, User, Course, QuestionBank, LearningConcept} */
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
        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'is_active' => true,
        ]);

        return [$instructor, $student, $course, $bank, $concept];
    }

    /** @return array{Question, QuestionOption, QuestionOption} */
    private function question(QuestionBank $bank, LearningConcept $concept, string $text): array
    {
        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => $text,
            'difficulty' => Question::DIFFICULTY_MEDIUM,
            'points' => 10,
            'status' => Question::STATUS_APPROVED,
            'answer_source' => Question::SOURCE_EXPLICIT,
            'needs_review' => false,
        ]);
        $question->learningConcepts()->attach($concept->id, [
            'weight' => 1.0,
            'difficulty_level' => Question::DIFFICULTY_MEDIUM,
        ]);
        $correct = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Benar',
            'is_correct' => true,
            'order' => 1,
        ]);
        $wrong = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Salah',
            'is_correct' => false,
            'order' => 2,
        ]);

        return [$question, $correct, $wrong];
    }

    /** @param list<Question> $questions */
    private function quiz(Course $course, array $questions, string $assessmentType): Quiz
    {
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'assessment_type' => $assessmentType,
            'status' => 'published',
            'published_at' => now(),
            'total_questions' => count($questions),
            'max_attempts' => 3,
            'randomize_questions' => false,
            'randomize_options' => false,
        ]);

        foreach ($questions as $index => $question) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_id' => $question->id,
                'order' => $index + 1,
                'points' => 10,
            ]);
        }

        return $quiz;
    }

    private function startAttempt(User $student, Quiz $quiz): QuizAttempt
    {
        $response = $this->actingAs($student)
            ->post(route('student.quizzes.start', $quiz))
            ->assertSessionHasNoErrors();
        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->where('student_id', $student->id)->sole();
        $response->assertRedirect(route('student.quizzes.take', [$quiz, $attempt]));

        return $attempt;
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
        ]);

        return ExperimentAssignment::query()->where('course_id', $course->id)->where('student_id', $student->id)->sole();
    }
}
