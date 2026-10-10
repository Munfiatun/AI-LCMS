<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\CourseAnalyticsService;
use App\Services\LearningAnalyticsInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(
        private CourseAnalyticsService $analytics,
        private LearningAnalyticsInsightService $insights,
    ) {}

    public function show(Request $request, Course $course): View|JsonResponse
    {
        // Analytics is instructor-private data; published visibility must not grant access.
        Gate::authorize('update', $course);

        $analytics = $this->analytics->summarize($course);
        $insights = $this->insights->summarize($course, $analytics);

        if ($request->boolean('insights')) {
            return response()->json($insights);
        }

        $enrollments = $course->enrollments()
            ->with('student')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('instructor.analytics.show', [
            'course' => $course,
            'enrollments' => $enrollments,
            ...$analytics,
            ...$insights,
        ]);
    }

    public function student(Course $course, User $student): View
    {
        Gate::authorize('update', $course);

        return view('instructor.analytics.student', [
            'course' => $course,
            ...$this->analytics->studentDetail($course, $student),
        ]);
    }
}
