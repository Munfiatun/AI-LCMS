<?php

namespace App\Services;

use App\Models\Course;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LearningAnalyticsInsightService
{
    private const MIN_ITEM_RESPONSES = 3;

    /**
     * Build teacher-facing learning signals from real submitted assessment data.
     * Only the latest submitted attempt for each student/Quiz pair is selected
     * by the database, so historical retries do not inflate PHP memory usage.
     *
     * @param  array<string, mixed>  $baseAnalytics
     * @return array<string, mixed>
     */
    public function summarize(Course $course, array $baseAnalytics): array
    {
        $latestAttempts = $this->latestSubmittedAttempts($course);
        $answers = $latestAttempts
            ->flatMap(fn (QuizAttempt $attempt) => $attempt->answers)
            ->filter(fn ($answer): bool => $answer->question !== null)
            ->values();

        $questionInsights = $answers
            ->groupBy('question_id')
            ->map(function (Collection $questionAnswers): array {
                $first = $questionAnswers->first();
                $question = $first->question;
                $responses = $questionAnswers->count();
                $correct = $questionAnswers->where('is_correct', true)->count();
                $correctRate = $this->percentage($correct, $responses);
                $sampleSufficient = $responses >= self::MIN_ITEM_RESPONSES;

                $signal = ! $sampleSufficient
                    ? 'insufficient'
                    : ($correctRate < 50 ? 'review' : ($correctRate < 70 ? 'monitor' : 'healthy'));

                return [
                    'question_id' => (int) $question->id,
                    'question_text' => Str::limit(trim((string) $question->question_text), 180, '…'),
                    'difficulty' => (string) ($question->difficulty ?: 'unknown'),
                    'topic' => trim((string) ($question->topic ?: '')),
                    'responses' => $responses,
                    'correct_count' => $correct,
                    'incorrect_count' => $responses - $correct,
                    'correct_rate' => $correctRate,
                    'sample_sufficient' => $sampleSufficient,
                    'signal' => $signal,
                ];
            })
            ->sortBy(fn (array $row): string => sprintf(
                '%d-%06.2f-%010d',
                $row['sample_sufficient'] ? 0 : 1,
                (float) $row['correct_rate'],
                (int) $row['question_id']
            ))
            ->values();

        $priorityQuestions = $questionInsights
            ->filter(fn (array $row): bool => $row['sample_sufficient'] && in_array($row['signal'], ['review', 'monitor'], true))
            ->take(5)
            ->values();

        $latestPassed = $latestAttempts->filter(fn (QuizAttempt $attempt): bool => $attempt->percentage !== null
            && $attempt->quiz !== null
            && $attempt->percentage >= (float) $attempt->quiz->passing_score);

        $evidence = $this->evidenceSummary(
            (int) ($baseAnalytics['totalStudents'] ?? 0),
            (int) ($baseAnalytics['quizParticipants'] ?? 0),
            $latestAttempts->count(),
            $answers->count(),
            $this->percentage($latestPassed->count(), $latestAttempts->count())
        );

        return [
            'evidence' => $evidence,
            'learning_signals' => $this->learningSignals($baseAnalytics, $evidence, $priorityQuestions),
            'question_insights' => $questionInsights->take(10)->values(),
            'priority_questions' => $priorityQuestions,
        ];
    }

    /** @return Collection<int, QuizAttempt> */
    private function latestSubmittedAttempts(Course $course): Collection
    {
        return QuizAttempt::query()
            ->whereHas('quiz', fn ($query) => $query->where('course_id', $course->id))
            ->where('quiz_attempts.status', 'submitted')
            ->where('quiz_attempts.id', '=', function ($query): void {
                $query->select('latest_attempt.id')
                    ->from('quiz_attempts as latest_attempt')
                    ->whereColumn('latest_attempt.quiz_id', 'quiz_attempts.quiz_id')
                    ->whereColumn('latest_attempt.student_id', 'quiz_attempts.student_id')
                    ->where('latest_attempt.status', 'submitted')
                    ->orderByDesc('latest_attempt.submitted_at')
                    ->orderByDesc('latest_attempt.id')
                    ->limit(1);
            })
            ->with([
                'quiz:id,course_id,title,passing_score',
                'answers.question:id,question_text,difficulty,topic',
            ])
            ->get();
    }

    /** @return array<string, int|float|string> */
    private function evidenceSummary(
        int $totalStudents,
        int $quizParticipants,
        int $representativeAttempts,
        int $questionResponses,
        float $latestPassRate
    ): array {
        $coverage = $this->percentage($quizParticipants, $totalStudents);

        [$level, $label] = match (true) {
            $quizParticipants < 3 => ['limited', 'Data masih terbatas'],
            $coverage < 70 => ['developing', 'Data mulai terbentuk'],
            default => ['adequate', 'Data cukup untuk tindak lanjut awal'],
        };

        return [
            'level' => $level,
            'label' => $label,
            'participant_coverage_percent' => $coverage,
            'representative_attempts' => $representativeAttempts,
            'question_responses' => $questionResponses,
            'latest_attempt_pass_rate' => $latestPassRate,
            'minimum_item_responses' => self::MIN_ITEM_RESPONSES,
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, int|float|string>  $evidence
     * @param  Collection<int, array<string,mixed>>  $priorityQuestions
     * @return list<array{severity:string,title:string,message:string}>
     */
    private function learningSignals(array $base, array $evidence, Collection $priorityQuestions): array
    {
        $signals = [];
        $totalStudents = (int) ($base['totalStudents'] ?? 0);
        $totalQuizzes = (int) ($base['totalQuizzes'] ?? 0);
        $atRiskStudents = (int) ($base['atRiskStudents'] ?? 0);
        $averageProgress = (float) ($base['averageProgress'] ?? 0);
        $coverage = (float) $evidence['participant_coverage_percent'];
        $representativeAttempts = (int) $evidence['representative_attempts'];
        $latestPassRate = (float) $evidence['latest_attempt_pass_rate'];

        if ($totalStudents === 0) {
            return [[
                'severity' => 'info',
                'title' => 'Belum ada data siswa',
                'message' => 'Learning Signals akan tersedia setelah siswa bergabung dan mulai mengikuti pembelajaran.',
            ]];
        }

        if ($atRiskStudents > 0) {
            $signals[] = [
                'severity' => 'high',
                'title' => $atRiskStudents.' siswa memerlukan prioritas tindak lanjut',
                'message' => 'Buka detail siswa prioritas untuk melihat progres materi dan hasil assessment sebelum menentukan intervensi.',
            ];
        }

        if ($totalQuizzes === 0) {
            $signals[] = [
                'severity' => 'info',
                'title' => 'Belum ada bukti assessment',
                'message' => 'Tambahkan Quiz terverifikasi jika guru perlu mengukur penguasaan materi secara terstruktur.',
            ];
        } elseif ($coverage < 50) {
            $signals[] = [
                'severity' => 'medium',
                'title' => 'Partisipasi Quiz masih rendah',
                'message' => 'Baru '.number_format($coverage, 1).'% siswa yang memiliki attempt submitted. Jangan menarik kesimpulan kelas sebelum cakupan data meningkat.',
            ];
        }

        if ($representativeAttempts >= 3 && $latestPassRate < 60) {
            $signals[] = [
                'severity' => 'high',
                'title' => 'Ketuntasan assessment terbaru rendah',
                'message' => 'Pass rate dari attempt terbaru per siswa/Quiz adalah '.number_format($latestPassRate, 1).'%. Tinjau materi dan butir yang paling sulit sebelum memberi assessment lanjutan.',
            ];
        }

        if ($priorityQuestions->isNotEmpty()) {
            $signals[] = [
                'severity' => 'medium',
                'title' => $priorityQuestions->count().' butir perlu ditinjau',
                'message' => 'Butir ini memiliki minimal '.self::MIN_ITEM_RESPONSES.' respons dan tingkat jawaban benar di bawah 70%. Periksa kesesuaian materi, kejelasan stem, dan distraktor.',
            ];
        }

        if ($totalStudents >= 3 && $averageProgress < 50) {
            $signals[] = [
                'severity' => 'medium',
                'title' => 'Progres kelas masih rendah',
                'message' => 'Rata-rata progres kursus '.number_format($averageProgress, 1).'%. Periksa materi yang tertunda sebelum menambah aktivitas baru.',
            ];
        }

        if ($signals === []) {
            $signals[] = $evidence['level'] === 'limited'
                ? [
                    'severity' => 'info',
                    'title' => 'Data belum cukup untuk pola kelas',
                    'message' => 'Terus kumpulkan data belajar. Analisis butir baru diberi label tindakan setelah minimal '.self::MIN_ITEM_RESPONSES.' respons.',
                ]
                : [
                    'severity' => 'positive',
                    'title' => 'Tidak ada sinyal prioritas yang menonjol',
                    'message' => 'Data saat ini tidak menunjukkan kondisi yang melewati ambang tindak lanjut. Tetap gunakan penilaian guru dan konteks kelas.',
                ];
        }

        return array_slice($signals, 0, 5);
    }

    private function percentage(int $numerator, int $denominator): float
    {
        return $denominator === 0 ? 0.0 : round(($numerator / $denominator) * 100, 1);
    }
}
