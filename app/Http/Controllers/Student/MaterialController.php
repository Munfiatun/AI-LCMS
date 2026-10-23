<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\ResearchEvent;
use App\Services\ResearchEventRecorder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function show(Course $course, LearningMaterial $material, ResearchEventRecorder $events): View
    {
        $material->load('section.course');
        abort_unless($material->section->course_id === $course->id, 404);
        Gate::authorize('view', $course);

        // Pastikan student terdaftar
        $enrollment = $course->enrollments()->where('student_id', auth()->id())->firstOrFail();
        Gate::authorize('view', $material);

        // Eager load relasi yang dibutuhkan view untuk mencegah LazyLoadingViolationException
        $course->load(['sections.materials' => fn ($query) => $query->published(), 'quizzes' => fn ($query) => $query->available()]);
        $material->load(['publishedSlidebook', 'documents', 'learningObjectives:id']);

        // Update last accessed
        $enrollment->last_accessed_material_id = $material->id;
        $enrollment->last_accessed_quiz_id = null;
        $enrollment->save();

        // Ambil semua progress material yang completed untuk user ini agar tidak N+1 di Blade
        $completedMaterialIds = auth()->user()
            ->materialProgress()
            ->where('status', 'completed')
            ->whereHas('learningMaterial.section', fn ($query) => $query->where('course_id', $course->id))
            ->pluck('learning_material_id')
            ->toArray();

        $isCompleted = in_array($material->id, $completedMaterialIds);
        $objectiveIds = $material->learningObjectives->pluck('id')->values()->all();

        $events->record(
            auth()->user(),
            $course,
            ResearchEvent::MATERIAL_OPENED,
            $material,
            [
                'learning_objective_ids' => $objectiveIds,
                'already_completed' => $isCompleted,
            ],
            count($objectiveIds) === 1 ? $objectiveIds[0] : null
        );

        return view('student.materials.show', compact('course', 'material', 'isCompleted', 'completedMaterialIds'));
    }
}
