<?php

namespace App\Console\Commands;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\MaterialProgress;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\Learning\Adaptive\AdaptiveFlowAcceptanceAuditService;
use App\Services\Learning\Adaptive\AdaptiveLearningPathService;
use App\Services\Learning\Adaptive\AdaptiveLearningResourceResolver;
use App\Services\Quiz\QuizAttemptService;
use App\Services\Quiz\QuizService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use RuntimeException;

class CompleteAdaptiveFlowFixture extends Command
{
    protected $signature = 'research:r4-complete-fixture {course} {student}';

    protected $description = 'Complete one synthetic local R4 formative cycle so the acceptance audit can be exercised end-to-end.';

    public function handle(
        AdaptiveAlgorithmRegistry $registry,
        AdaptiveLearningPathService $learningPath,
        AdaptiveLearningResourceResolver $resources,
        QuizService $quizService,
        QuizAttemptService $quizAttemptService,
        AdaptiveFlowAcceptanceAuditService $auditService,
    ): int {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Command ini hanya boleh dijalankan pada environment local/testing.');

            return self::FAILURE;
        }

        $course = Course::query()->findOrFail((int) $this->argument('course'));
        $student = User::query()->findOrFail((int) $this->argument('student'));

        $before = $auditService->audit($student, $course);
        if ($before['summary']['passed']) {
            $this->info('R4 sudah PASS 6/6 untuk learner ini. Tidak ada fixture baru yang dibuat.');

            return self::SUCCESS;
        }

        $baseline = $registry->baseline();
        $recommendations = AdaptiveRecommendation::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('algorithm_version_id', $baseline->id)
            ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
            ->with(['course', 'sourceConcept', 'targetConcept', 'algorithmVersion'])
            ->orderBy('source_concept_id')
            ->get();

        if ($recommendations->isEmpty()) {
            $this->error('Tidak ada active baseline recommendation. Jalankan generator recommendation R3 terlebih dahulu.');

            return self::FAILURE;
        }

        $paths = $learningPath->build($recommendations->map(fn (AdaptiveRecommendation $recommendation): array => [
            'recommendation' => $recommendation,
        ]));
        $currentStep = $paths->first()['current_step'] ?? null;
        /** @var AdaptiveRecommendation|null $focusRecommendation */
        $focusRecommendation = $currentStep['representative_row']['recommendation'] ?? null;

        if (! $focusRecommendation || ! $focusRecommendation->targetConcept) {
            $this->error('Current adaptive focus tidak memiliki target concept.');

            return self::FAILURE;
        }

        $concept = $focusRecommendation->targetConcept;
        $material = $resources->materialFor($focusRecommendation);

        if (! $material) {
            $this->error("Tidak ada material published yang terpetakan ke {$concept->code}. Mapping material perlu tersedia sebelum fixture dapat dijalankan.");

            return self::FAILURE;
        }

        MaterialProgress::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'learning_material_id' => $material->id,
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
            ],
        );

        $bank = QuestionBank::query()
            ->where('course_id', $course->id)
            ->where('status', QuestionBank::STATUS_ACTIVE)
            ->whereHas('questions', fn ($query) => $query
                ->where('status', 'approved')
                ->where('needs_review', false))
            ->orderByRaw("CASE WHEN title LIKE '%R4.6%' THEN 0 ELSE 1 END")
            ->latest('id')
            ->first();

        if (! $bank) {
            $this->error('Tidak ada bank soal course ini yang memiliki soal approved/terverifikasi.');

            return self::FAILURE;
        }

        $questions = $bank->questions()
            ->where('status', 'approved')
            ->where('needs_review', false)
            ->with('options')
            ->orderBy('order')
            ->orderBy('id')
            ->take(5)
            ->get()
            ->filter(fn ($question): bool => $question->options->count() >= 2
                && $question->options->where('is_correct', true)->count() === 1)
            ->values();

        if ($questions->isEmpty()) {
            $this->error('Soal terverifikasi ditemukan, tetapi belum memiliki opsi/kunci jawaban yang valid.');

            return self::FAILURE;
        }

        foreach ($questions as $question) {
            $question->learningConcepts()->syncWithoutDetaching([
                $concept->id => [
                    'weight' => 1.00,
                    'difficulty_level' => $question->difficulty,
                ],
            ]);
        }

        $quiz = $quizService->createQuiz($course, [
            'title' => '[DEV FIXTURE] Formative '.$concept->code.' — '.$concept->name,
            'description' => 'Synthetic local fixture untuk verifikasi R4.6. Bukan data penelitian empiris.',
            'passing_score' => 70,
            'duration_minutes' => 10,
            'total_questions' => $questions->count(),
            'randomize_questions' => false,
            'randomize_options' => false,
            'max_attempts' => 1,
            'assessment_type' => Quiz::ASSESSMENT_FORMATATIVE ?? Quiz::ASSESSMENT_FORMATIVE,
        ]);

        $quizService->syncQuestions(
            $quiz,
            $questions->values()->map(fn ($question, int $index): array => [
                'id' => $question->id,
                'points' => max(1, (int) ($question->points ?? 10)),
                'order' => $index + 1,
            ])->all(),
        );
        $quizService->publishQuiz($quiz->fresh());

        $attempt = $quizAttemptService->startAttempt($quiz->fresh(), $student);
        $attempt->load('attemptQuestions.question.options');

        $answers = [];
        foreach ($attempt->attemptQuestions as $attemptQuestion) {
            $correct = $attemptQuestion->question->options->firstWhere('is_correct', true);
            if (! $correct) {
                throw new RuntimeException('Fixture question tidak memiliki satu kunci jawaban benar.');
            }

            $answers[$attemptQuestion->question_id] = $correct->id;
        }

        $submitted = $quizAttemptService->submitAttempt($attempt, $answers);
        $after = $auditService->audit($student, $course);

        $this->newLine();
        $this->info('Synthetic R4 fixture selesai.');
        $this->line("Course: {$course->title}");
        $this->line("Student: {$student->name} (#{$student->id})");
        $this->line("Focus: {$concept->code} — {$concept->name}");
        $this->line("Material: {$material->title} [completed]");
        $this->line("Quiz: {$quiz->title}");
        $this->line('Attempt: #'.$submitted->id.' | score '.number_format((float) $submitted->percentage, 2).'%');
        $this->newLine();

        foreach ($after['gates'] as $index => $gate) {
            $this->line(($index + 1).'. ['.($gate['passed'] ? 'PASS' : 'FAIL').'] '.$gate['title']);
        }

        $summary = $after['summary'];
        $this->newLine();

        if ($summary['passed']) {
            $this->info("PASS — Adaptive Learning Flow M4 siap ({$summary['passed_gates']}/{$summary['total_gates']} gate)");
            $this->warn('Catatan: siklus ini adalah synthetic/dev fixture, bukan data penelitian empiris.');

            return self::SUCCESS;
        }

        $this->warn("Fixture selesai tetapi audit masih {$summary['passed_gates']}/{$summary['total_gates']}.");

        return self::FAILURE;
    }
}
