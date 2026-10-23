<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ResearchEvent;
use App\Models\Slidebook;
use App\Services\ResearchEventRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SlidebookViewerController extends Controller
{
    /**
     * Display the presentation viewer for an approved & published slidebook.
     */
    public function show(Request $request, Slidebook $slidebook, ResearchEventRecorder $events): View
    {
        Gate::authorize('view', $slidebook);

        $slidebook->load([
            'slides' => fn ($q) => $q->orderBy('order'),
            'material.learningObjectives:id',
            'material.section.course.instructor',
        ]);

        $material = $slidebook->material;
        $course = $material->section->course;
        $objectiveIds = $material->learningObjectives->pluck('id')->values()->all();

        $events->record(
            $request->user(),
            $course,
            ResearchEvent::SLIDEBOOK_OPENED,
            $slidebook,
            [
                'material_id' => $material->id,
                'learning_objective_ids' => $objectiveIds,
                'slide_count' => $slidebook->slides->count(),
            ],
            count($objectiveIds) === 1 ? $objectiveIds[0] : null
        );

        return view('student.slidebooks.show', [
            'slidebook' => $slidebook,
            'material' => $material,
            'course' => $course,
            'slides' => $slidebook->slides,
        ]);
    }
}
