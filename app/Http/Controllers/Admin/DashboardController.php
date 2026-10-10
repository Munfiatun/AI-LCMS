<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AIProcessingLog;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $adminRole = Role::where('name', Role::ROLE_ADMIN)->first();
        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->first();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->first();

        $pendingInstructors = $instructorRole
            ? User::with('role')
                ->where('role_id', $instructorRole->id)
                ->where('is_active', false)
                ->oldest()
                ->get()
            : collect();

        $stats = [
            'total_users' => User::count(),
            'total_students' => $studentRole ? User::where('role_id', $studentRole->id)->count() : 0,
            'total_instructors' => $instructorRole ? User::where('role_id', $instructorRole->id)->count() : 0,
            'pending_instructors' => $pendingInstructors->count(),
            'total_admins' => $adminRole ? User::where('role_id', $adminRole->id)->count() : 0,
            'total_courses' => Course::count(),
            'total_materials' => LearningMaterial::count(),
            'total_quizzes' => Quiz::count(),
            'ai_logs_count' => AIProcessingLog::count(),
        ];

        $recentUsers = User::with('role')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'pendingInstructors'));
    }
}
