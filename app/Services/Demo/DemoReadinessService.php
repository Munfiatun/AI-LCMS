<?php

namespace App\Services\Demo;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Slidebook;
use App\Models\User;

class DemoReadinessService
{
    /**
     * @return array{
     *     ready:bool,
     *     passed:int,
     *     total:int,
     *     checks:list<array{id:string,label:string,passed:bool,detail:string}>
     * }
     */
    public function report(): array
    {
        $checks = [];

        $this->add($checks, 'environment', 'Lingkungan demo aman', app()->environment(['local', 'testing']),
            app()->environment()." (demo hanya untuk local/testing)");

        $demoPasswordConfigured = filled(config('auth.demo_user_password'));
        $this->add($checks, 'demo_password', 'Password demo dikonfigurasi', $demoPasswordConfigured,
            $demoPasswordConfigured ? 'DEMO_USER_PASSWORD tersedia.' : 'DEMO_USER_PASSWORD belum dikonfigurasi.');

        $instructor = User::where('email', 'instructor@example.com')->first();
        $student = User::where('email', 'student@example.com')->first();

        $this->add($checks, 'instructor', 'Akun demo Instructor aktif',
            (bool) ($instructor?->is_active && $instructor?->isInstructor()),
            $instructor ? 'instructor@example.com ditemukan.' : 'Akun instructor@example.com belum tersedia.');
        $this->add($checks, 'student', 'Akun demo Student aktif',
            (bool) ($student?->is_active && $student?->isStudent()),
            $student ? 'student@example.com ditemukan.' : 'Akun student@example.com belum tersedia.');

        $course = Course::where('slug', 'lms-feature-showcase')->first();
        $coursePublished = $course?->status === Course::STATUS_PUBLISHED;
        $this->add($checks, 'course', 'Golden demo course terbit', $coursePublished,
            $course ? "{$course->title} ({$course->status})" : 'Course lms-feature-showcase belum tersedia.');

        $activeEnrollment = $course && $student
            ? CourseEnrollment::where('course_id', $course->id)
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->first()
            : null;
        $this->add($checks, 'enrollment', 'Student terdaftar pada golden course', (bool) $activeEnrollment,
            $activeEnrollment ? 'Enrollment aktif tersedia.' : 'Enrollment aktif belum tersedia.');

        $publishedMaterialCount = $course
            ? LearningMaterial::whereHas('section', fn ($query) => $query->where('course_id', $course->id))
                ->where('status', LearningMaterial::STATUS_PUBLISHED)
                ->count()
            : 0;
        $this->add($checks, 'materials', 'Materi terbit tersedia', $publishedMaterialCount >= 2,
            "{$publishedMaterialCount} materi terbit ditemukan.");

        $slidebookMaterial = $course
            ? LearningMaterial::whereHas('section', fn ($query) => $query->where('course_id', $course->id))
                ->where('slug', 'slidebook-layout-gallery')
                ->first()
            : null;
        $publishedSlidebook = $slidebookMaterial?->slidebooks()
            ->where('status', Slidebook::STATUS_PUBLISHED)
            ->orderByDesc('version')
            ->first();
        $workingRevision = $slidebookMaterial?->slidebooks()
            ->whereIn('status', [Slidebook::STATUS_DRAFT, Slidebook::STATUS_REVIEW])
            ->orderByDesc('version')
            ->first();
        $publishedSlides = $publishedSlidebook?->slides()->count() ?? 0;

        $this->add($checks, 'slidebook_published', 'Slidebook terbit siap dipresentasikan',
            (bool) ($publishedSlidebook && $publishedSlides >= 10),
            $publishedSlidebook ? "v{$publishedSlidebook->version}, {$publishedSlides} slide aktif." : 'Belum ada Slidebook terbit.');
        $this->add($checks, 'slidebook_revision', 'Contoh revision workflow tersedia',
            (bool) ($workingRevision && $publishedSlidebook && $workingRevision->version > $publishedSlidebook->version),
            $workingRevision ? "Revision v{$workingRevision->version} berstatus {$workingRevision->status}." : 'Revision editable belum tersedia.');

        $quiz = $course
            ? Quiz::where('course_id', $course->id)
                ->where('title', 'Web Fundamentals Quiz')
                ->first()
            : null;
        $questionCount = $quiz?->questions()->count() ?? 0;
        $quizReady = $quiz?->status === 'published' && $questionCount > 0 && (int) $quiz->total_questions === $questionCount;
        $this->add($checks, 'quiz', 'Quiz terbit dan konsisten', $quizReady,
            $quiz ? "{$questionCount} soal, status {$quiz->status}." : 'Quiz demo belum tersedia.');

        $attempt = $quiz && $student
            ? QuizAttempt::where('quiz_id', $quiz->id)
                ->where('student_id', $student->id)
                ->where('status', 'submitted')
                ->latest('submitted_at')
                ->first()
            : null;
        $this->add($checks, 'attempt', 'Riwayat hasil belajar tersedia', (bool) ($attempt && $attempt->isPassed()),
            $attempt ? 'Attempt submitted dengan '.number_format((float) $attempt->percentage, 0).'%.' : 'Attempt submitted belum tersedia.');

        $progress = $activeEnrollment ? (float) $activeEnrollment->progress_percentage : 0.0;
        $this->add($checks, 'progress', 'Progress siswa dapat didemonstrasikan', (bool) ($activeEnrollment && $progress > 0 && $progress <= 100),
            $activeEnrollment ? number_format($progress, 0).'% progress.' : 'Progress belum tersedia.');

        $passed = collect($checks)->where('passed', true)->count();

        return [
            'ready' => $passed === count($checks),
            'passed' => $passed,
            'total' => count($checks),
            'checks' => $checks,
        ];
    }

    /** @param list<array{id:string,label:string,passed:bool,detail:string}> $checks */
    private function add(array &$checks, string $id, string $label, bool $passed, string $detail): void
    {
        $checks[] = compact('id', 'label', 'passed', 'detail');
    }
}
