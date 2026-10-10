<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\Slidebook;
use Illuminate\Support\Collection;

class CourseWorkflowReadinessService
{
    /**
     * Build a teacher-facing snapshot of how far a course has progressed
     * through the learning creation journey.
     *
     * @return array<string, mixed>
     */
    public function summarize(Course $course): array
    {
        $course->loadMissing([
            'sections.materials.publishedSlidebook',
            'quizzes.attempts',
            'enrollments',
        ]);

        /** @var Collection<int, LearningMaterial> $materials */
        $materials = $course->sections
            ->flatMap(fn ($section) => $section->materials)
            ->values();

        /** @var Collection<int, Quiz> $quizzes */
        $quizzes = $course->quizzes->values();

        $publishedMaterials = $materials->where('status', LearningMaterial::STATUS_PUBLISHED)->count();
        $publishedSlidebooks = $materials->filter(fn (LearningMaterial $material): bool => $material->publishedSlidebook !== null)->count();
        $publishedQuizzes = $quizzes->where('status', 'published')->count();
        $submittedAttempts = $quizzes->flatMap(fn (Quiz $quiz) => $quiz->attempts)
            ->where('status', 'submitted')
            ->count();
        $studentCount = $course->enrollments
            ->whereIn('status', ['active', 'completed'])
            ->pluck('student_id')
            ->unique()
            ->count();

        $counts = [
            'sections' => $course->sections->count(),
            'materials' => $materials->count(),
            'published_materials' => $publishedMaterials,
            'published_slidebooks' => $publishedSlidebooks,
            'missing_slidebooks' => max(0, $materials->count() - $publishedSlidebooks),
            'quizzes' => $quizzes->count(),
            'published_quizzes' => $publishedQuizzes,
            'unpublished_quizzes' => max(0, $quizzes->count() - $publishedQuizzes),
            'students' => $studentCount,
            'submitted_attempts' => $submittedAttempts,
        ];

        $steps = [
            ['key' => 'course', 'label' => 'Kursus', 'complete' => true],
            ['key' => 'material', 'label' => 'Materi', 'complete' => $counts['materials'] > 0],
            ['key' => 'slidebook', 'label' => 'Slidebook', 'complete' => $counts['published_slidebooks'] > 0],
            ['key' => 'quiz', 'label' => 'Quiz', 'complete' => $counts['published_quizzes'] > 0],
            ['key' => 'publish', 'label' => 'Publish', 'complete' => $course->status === Course::STATUS_PUBLISHED],
            ['key' => 'students', 'label' => 'Siswa', 'complete' => $counts['students'] > 0],
            ['key' => 'analytics', 'label' => 'Analytics', 'complete' => $counts['submitted_attempts'] > 0],
        ];

        $completedSteps = collect($steps)->where('complete', true)->count();

        return [
            'course' => $course,
            'counts' => $counts,
            'steps' => $steps,
            'completed_steps' => $completedSteps,
            'total_steps' => count($steps),
            'progress_percent' => (int) round(($completedSteps / count($steps)) * 100),
            'warnings' => $this->warnings($course, $counts),
            'next_action' => $this->nextAction($course, $materials, $quizzes, $counts),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<string>
     */
    private function warnings(Course $course, array $counts): array
    {
        $warnings = [];

        if ($counts['materials'] === 0) {
            $warnings[] = 'Belum ada materi pembelajaran.';
        } elseif ($counts['missing_slidebooks'] > 0) {
            $warnings[] = $counts['missing_slidebooks'].' materi belum memiliki Slidebook terbit.';
        }

        if ($counts['quizzes'] === 0) {
            $warnings[] = 'Belum ada Quiz untuk mengukur pemahaman siswa.';
        } elseif ($counts['unpublished_quizzes'] > 0) {
            $warnings[] = $counts['unpublished_quizzes'].' Quiz masih draft atau belum terbit.';
        }

        if ($course->status !== Course::STATUS_PUBLISHED) {
            $warnings[] = 'Kursus belum dipublikasikan ke siswa.';
        } elseif ($counts['students'] === 0) {
            $warnings[] = 'Belum ada siswa yang terdaftar pada kursus ini.';
        }

        return $warnings;
    }

    /**
     * @param  Collection<int, LearningMaterial>  $materials
     * @param  Collection<int, Quiz>  $quizzes
     * @param  array<string, int>  $counts
     * @return array{label: string, description: string, url: string}
     */
    private function nextAction(Course $course, Collection $materials, Collection $quizzes, array $counts): array
    {
        if ($counts['sections'] === 0 || $counts['materials'] === 0) {
            return [
                'label' => 'Tambahkan Materi Pembelajaran',
                'description' => 'Buat bab dan materi pertama agar proses pembuatan pembelajaran dapat dilanjutkan.',
                'url' => route('instructor.courses.edit', $course),
            ];
        }

        if ($counts['published_slidebooks'] === 0) {
            $material = $materials->first();

            return [
                'label' => 'Buat Slidebook dari Materi',
                'description' => 'Gunakan materi yang sudah dibuat sebagai sumber Slidebook, lalu tinjau sebelum diterbitkan.',
                'url' => $material ? route('instructor.materials.edit', $material) : route('instructor.courses.edit', $course),
            ];
        }

        if ($counts['quizzes'] === 0) {
            $slidebook = $materials->pluck('publishedSlidebook')->filter()->first();

            return [
                'label' => 'Buat Quiz Pembelajaran',
                'description' => 'Buat Quiz dari Slidebook terbit untuk mengukur pemahaman siswa.',
                'url' => route('instructor.quizzes.index', $slidebook instanceof Slidebook ? ['slidebook_id' => $slidebook->id] : []),
            ];
        }

        if ($counts['published_quizzes'] === 0) {
            $quiz = $quizzes->first();

            return [
                'label' => 'Tinjau dan Terbitkan Quiz',
                'description' => 'Pastikan jawaban sudah diverifikasi guru sebelum Quiz diterbitkan.',
                'url' => $quiz ? route('instructor.quizzes.show', $quiz) : route('instructor.quizzes.index'),
            ];
        }

        if ($course->status !== Course::STATUS_PUBLISHED) {
            return [
                'label' => 'Periksa Kesiapan & Publikasikan',
                'description' => 'Materi dan Quiz sudah tersedia. Periksa kesiapan kursus sebelum dibuka untuk siswa.',
                'url' => route('instructor.courses.edit', $course),
            ];
        }

        if ($counts['students'] === 0) {
            return [
                'label' => 'Bagikan Token Kelas',
                'description' => 'Kursus sudah terbit. Bagikan token kelas agar siswa dapat bergabung.',
                'url' => route('instructor.courses.edit', $course),
            ];
        }

        return [
            'label' => $counts['submitted_attempts'] > 0 ? 'Lihat Perkembangan Siswa' : 'Pantau Aktivitas Siswa',
            'description' => $counts['submitted_attempts'] > 0
                ? 'Data hasil belajar sudah tersedia untuk dianalisis.'
                : 'Siswa sudah bergabung. Analytics akan semakin lengkap setelah mereka mengerjakan Quiz.',
            'url' => route('instructor.courses.analytics', $course),
        ];
    }
}
