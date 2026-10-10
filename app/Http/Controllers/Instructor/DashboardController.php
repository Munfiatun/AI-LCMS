<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Slidebook;
use App\Services\Course\CourseWorkflowReadinessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CourseWorkflowReadinessService $workflowReadiness): View
    {
        $user = $request->user();

        $courses = Course::where('instructor_id', $user->id)
            ->with([
                'sections.materials.publishedSlidebook',
                'quizzes:id,course_id,title,status',
            ])
            ->withCount([
                'enrollments as active_students_count' => fn ($query) => $query->whereIn('status', ['active', 'completed']),
                'quizAttempts as submitted_quiz_attempts_count' => fn ($query) => $query->where('quiz_attempts.status', 'submitted'),
            ])
            ->latest()
            ->get();
        $courseIds = $courses->pluck('id');

        $stats = [
            'total_courses' => $courses->count(),
            'total_materials' => \App\Models\LearningMaterial::whereHas('section', fn ($query) => $query->whereIn('course_id', $courseIds))->count(),
            'total_slidebooks' => Slidebook::whereHas('material.section', fn ($query) => $query->whereIn('course_id', $courseIds))->count(),
            'total_question_banks' => QuestionBank::where('instructor_id', $user->id)->count(),
            'total_quizzes' => Quiz::whereIn('course_id', $courseIds)->count(),
            'total_students' => \App\Models\CourseEnrollment::whereIn('course_id', $courseIds)
                ->whereIn('status', ['active', 'completed'])
                ->distinct('student_id')
                ->count('student_id'),
        ];

        $courseReadiness = $courses
            ->map(fn (Course $course): array => $workflowReadiness->summarize($course))
            ->values();

        $journey = [
            ['number' => 1, 'label' => 'Buat Kursus', 'description' => 'Siapkan ruang belajar dan struktur bab.'],
            ['number' => 2, 'label' => 'Tambahkan Materi', 'description' => 'Tulis materi atau unggah PDF/DOCX sebagai sumber belajar.'],
            ['number' => 3, 'label' => 'Buat Slidebook', 'description' => 'Ubah materi menjadi presentasi, periksa, lalu terbitkan.'],
            ['number' => 4, 'label' => 'Buat Quiz', 'description' => 'Buat soal, verifikasi jawaban, lalu terbitkan Quiz.'],
            ['number' => 5, 'label' => 'Publikasikan Kursus', 'description' => 'Buka kursus setelah materi utama siap.'],
            ['number' => 6, 'label' => 'Ajak Siswa', 'description' => 'Bagikan token kelas agar siswa dapat bergabung.'],
            ['number' => 7, 'label' => 'Pantau Hasil Belajar', 'description' => 'Gunakan progress dan analytics untuk melihat perkembangan siswa.'],
        ];

        return view('instructor.dashboard', compact('user', 'stats', 'courseReadiness', 'journey'));
    }
}
