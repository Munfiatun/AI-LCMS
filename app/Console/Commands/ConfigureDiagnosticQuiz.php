<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use Illuminate\Console\Command;

class ConfigureDiagnosticQuiz extends Command
{
    protected $signature = 'research:diagnostic-quiz
        {quiz : Quiz ID}
        {--apply : Mark the quiz as diagnostic after validation}';

    protected $description = 'Validate and mark one quiz as a diagnostic assessment for R2 Learner Model.';

    public function handle(): int
    {
        $quiz = Quiz::query()
            ->with(['course.learningConcepts', 'questions.learningConcepts'])
            ->find($this->argument('quiz'));

        if (! $quiz) {
            $this->error('Quiz tidak ditemukan. Gunakan ID quiz yang valid.');

            return self::FAILURE;
        }

        $this->info("Diagnostic target: {$quiz->title} (#{$quiz->id})");
        $this->line("Course: {$quiz->course?->title} (#{$quiz->course_id})");
        $this->line('Jumlah soal: '.$quiz->questions->count());

        if ($quiz->questions->isEmpty()) {
            $this->error('Quiz belum memiliki soal. Tambahkan soal terlebih dahulu.');

            return self::FAILURE;
        }

        $unmapped = $quiz->questions
            ->filter(fn ($question): bool => $question->learningConcepts->isEmpty())
            ->values();

        if ($unmapped->isNotEmpty()) {
            $this->error($unmapped->count().' soal belum memiliki concept mapping.');
            foreach ($unmapped as $question) {
                $this->line('  - #'.$question->id.' '.str($question->question_text)->stripTags()->limit(80));
            }

            return self::FAILURE;
        }

        $coveredConcepts = $quiz->questions
            ->flatMap(fn ($question) => $question->learningConcepts)
            ->unique('id')
            ->sortBy('order')
            ->values();

        $this->table(
            ['Concept', 'Nama', 'Jumlah Evidence'],
            $coveredConcepts->map(function ($concept) use ($quiz): array {
                $count = $quiz->questions
                    ->filter(fn ($question): bool => $question->learningConcepts->contains('id', $concept->id))
                    ->count();

                return [$concept->code, $concept->name, $count];
            })->all()
        );

        if (! $this->option('apply')) {
            $this->warn('PREVIEW ONLY — assessment_type belum diubah.');
            $this->line('Jalankan ulang dengan --apply jika coverage concept sudah sesuai.');

            return self::SUCCESS;
        }

        if ($quiz->attempts()->exists()) {
            $this->error('Quiz yang sudah memiliki attempt tidak boleh diubah menjadi diagnostic karena akan mengubah interpretasi evidence historis.');

            return self::FAILURE;
        }

        $quiz->update(['assessment_type' => Quiz::ASSESSMENT_DIAGNOSTIC]);

        $this->info('Quiz berhasil ditandai sebagai DIAGNOSTIC.');
        $this->line('Mulai saat siswa menyelesaikan quiz ini, mastery per concept akan diperbarui otomatis.');

        return self::SUCCESS;
    }
}
