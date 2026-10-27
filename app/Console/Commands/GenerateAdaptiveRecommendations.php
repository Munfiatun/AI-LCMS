<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use Illuminate\Console\Command;

class GenerateAdaptiveRecommendations extends Command
{
    protected $signature = 'research:r3-generate-recommendations {course} {student}';

    protected $description = 'Generate and persist baseline adaptive recommendations for a student in a course.';

    public function handle(AdaptiveRecommendationService $service): int
    {
        $course = Course::query()->find($this->argument('course'));
        $student = User::query()->find($this->argument('student'));

        if (! $course) {
            $this->error('Course not found.');

            return self::FAILURE;
        }

        if (! $student) {
            $this->error('Student not found.');

            return self::FAILURE;
        }

        $recommendations = $service->generateForCourse($student, $course)
            ->load(['sourceConcept:id,code,name', 'targetConcept:id,code,name', 'algorithmVersion:id,code,version']);

        $this->info('R3.3 Adaptive Recommendations Persisted');
        $this->line("Course: {$course->title} | Student: {$student->name} (#{$student->id})");
        $this->newLine();

        $this->table(
            ['Source', 'Action', 'Target', 'Reason', 'Algorithm', 'Status'],
            $recommendations->map(fn ($recommendation): array => [
                $recommendation->sourceConcept?->code ?? '#'.$recommendation->source_concept_id,
                $recommendation->action,
                $recommendation->targetConcept?->code ?? '—',
                $recommendation->reason_code,
                ($recommendation->algorithmVersion?->code ?? '—').'@'.($recommendation->algorithmVersion?->version ?? '—'),
                $recommendation->status,
            ])->all()
        );

        $this->newLine();
        $this->info($recommendations->count().' active recommendations synchronized.');

        return self::SUCCESS;
    }
}
