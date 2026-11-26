<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\ExperimentAssignment;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Creates two distinct, explicitly synthetic R5 evaluation journeys.
 * This seeder never runs in production and never alters existing users'
 * credentials, authentic research assignments or completed assessments.
 */
class R5PilotDemoSeeder extends Seeder
{
    public const ADAPTIVE_EMAIL = 'adaptive-demo@example.com';

    public const CONTROL_EMAIL = 'control-demo@example.com';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || ! filled(config('auth.demo_user_password'))) {
            $this->command?->warn('R5 pilot fixtures require local/testing and DEMO_USER_PASSWORD.');

            return;
        }

        $course = Course::query()
            ->where('slug', 'lms-feature-showcase')
            ->where('status', Course::STATUS_PUBLISHED)
            ->first();

        if (! $course || ! $course->instructor?->isInstructor()) {
            throw new RuntimeException('Seed the local golden LMS demo first (DatabaseSeeder / LmsDemoSeeder).');
        }

        $material = LearningMaterial::query()
            ->whereHas('section', fn ($query) => $query->where('course_id', $course->id))
            ->where('slug', 'web-request-lifecycle')
            ->where('status', LearningMaterial::STATUS_PUBLISHED)
            ->firstOrFail();

        $quiz = Quiz::query()
            ->where('course_id', $course->id)
            ->where('title', 'Web Fundamentals Quiz')
            ->where('status', 'published')
            ->firstOrFail();

        $studentRole = Role::query()->where('name', Role::ROLE_STUDENT)->firstOrFail();

        DB::transaction(function () use ($course, $material, $quiz, $studentRole): void {
            $adaptive = $this->student(self::ADAPTIVE_EMAIL, 'Siswa Demo Adaptif', $studentRole);
            $control = $this->student(self::CONTROL_EMAIL, 'Siswa Demo Kontrol', $studentRole);

            $concept = LearningConcept::query()->firstOrCreate(
                ['course_id' => $course->id, 'code' => 'R5-DEMO-HTTP-01'],
                [
                    'name' => 'Alur HTTP Request dan Response (Demo)',
                    'description' => 'Konsep sintetis untuk mendemonstrasikan rekomendasi adaptif.',
                    'order' => 1,
                    'is_active' => true,
                ],
            );

            $material->learningConcepts()->syncWithoutDetaching([
                $concept->id => ['relevance_weight' => 1.0],
            ]);

            foreach ($quiz->questions as $question) {
                $question->learningConcepts()->syncWithoutDetaching([
                    $concept->id => ['weight' => 1.0, 'difficulty_level' => $question->difficulty],
                ]);
            }

            foreach ([
                [$adaptive, ExperimentAssignment::CONDITION_ADAPTIVE],
                [$control, ExperimentAssignment::CONDITION_NON_ADAPTIVE],
            ] as [$student, $condition]) {
                CourseEnrollment::query()->firstOrCreate(
                    ['course_id' => $course->id, 'student_id' => $student->id],
                    ['status' => 'active', 'progress_percentage' => 0],
                );

                $assignment = ExperimentAssignment::query()->firstOrCreate(
                    ['course_id' => $course->id, 'student_id' => $student->id],
                    [
                        'condition' => $condition,
                        'class_cluster' => 'SYNTHETIC-R5-DEMO',
                        'assignment_version' => 'r5-demo-only-v1',
                        'assigned_by' => $course->instructor_id,
                        'assigned_at' => now(),
                    ],
                );

                if ($assignment->condition !== $condition) {
                    throw new RuntimeException('The synthetic demo assignment changed. Never overwrite existing research allocation.');
                }

                // An illustrative seeded learner profile, NOT measured student evidence.
                LearnerMastery::query()->firstOrCreate(
                    ['student_id' => $student->id, 'concept_id' => $concept->id],
                    [
                        'mastery_score' => 25.0,
                        'evidence_count' => 3,
                        'status' => 'observed',
                        'last_evidence_at' => now(),
                    ],
                );
            }

            // Generate an actual rule-engine decision for the demo adaptive learner.
            // The control learner must have no active intervention.
            app(AdaptiveRecommendationService::class)->generateForCourse($adaptive, $course);
        });

        $this->command?->info('R5 pilot demo ready: synthetic adaptive and control accounts, shared lesson and quiz.');
    }

    private function student(string $email, string $name, Role $role): User
    {
        $student = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'role_id' => $role->id,
                'name' => $name,
                'is_active' => true,
                'password' => Hash::make((string) config('auth.demo_user_password')),
            ],
        );

        if (! $student->isStudent() || ! $student->is_active) {
            throw new RuntimeException("The reserved demo email {$email} belongs to a non-student or inactive account.");
        }

        return $student;
    }
}
