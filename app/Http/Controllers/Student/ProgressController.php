<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\ResearchEvent;
use App\Services\EnrollmentService;
use App\Services\ResearchEventRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProgressController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService,
        private ResearchEventRecorder $researchEventRecorder
    ) {}

    public function complete(Request $request, LearningMaterial $material): RedirectResponse
    {
        $material->load(['section.course', 'learningObjectives:id']);
        $course = $material->section->course;
        $course->enrollments()->where('student_id', $request->user()->id)->firstOrFail();
        Gate::authorize('view', $material);

        $this->enrollmentService->markMaterialCompleted($material, $request->user());

        $objectiveIds = $material->learningObjectives->pluck('id')->values()->all();

        $this->researchEventRecorder->recordOnce(
            $request->user(),
            $course,
            ResearchEvent::MATERIAL_COMPLETED,
            $material,
            ['learning_objective_ids' => $objectiveIds],
            count($objectiveIds) === 1 ? $objectiveIds[0] : null
        );

        return back()->with('success', 'Materi ditandai selesai.');
    }
}
