<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\RuleBasedAdaptiveEngine;
use Illuminate\Console\Command;

class PreviewAdaptiveDecisions extends Command
{
    protected $signature = 'research:r3-preview-decisions {course : Course ID} {student : Student user ID}';

    protected $description = 'Preview deterministic R3 rule-based adaptive decisions for a student without persisting recommendations';

    public function handle(RuleBasedAdaptiveEngine $engine): int
    {
        $course = Course::query()->find((int) $this->argument('course'));
        $student = User::query()->find((int) $this->argument('student'));

        if (! $course) {
            $this->error('Course tidak ditemukan.');

            return self::FAILURE;
        }

        if (! $student) {
            $this->error('Student tidak ditemukan.');

            return self::FAILURE;
        }

        $concepts = LearningConcept::query()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('code')
            ->get();

        if ($concepts->isEmpty()) {
            $this->warn('Course belum memiliki concept aktif.');

            return self::SUCCESS;
        }

        $rows = $concepts->map(function (LearningConcept $concept) use ($engine, $student): array {
            $mastery = LearnerMastery::query()
                ->where('student_id', $student->id)
                ->where('concept_id', $concept->id)
                ->first();
            $decision = $engine->decide($student, $concept);
            $target = $decision->targetConceptId
                ? LearningConcept::query()->find($decision->targetConceptId)
                : null;

            return [
                $concept->code,
                $mastery ? number_format($mastery->mastery_score, 2).'%' : '—',
                $mastery?->evidence_count ?? 0,
                $decision->action,
                $target?->code ?? '—',
                $decision->reasonCode,
                $decision->algorithmCode.'@'.$decision->algorithmVersion,
            ];
        });

        $this->info('R3.2 Rule-Based Adaptive Decision Preview');
        $this->line('Course: '.$course->title.' | Student: '.$student->name.' (#'.$student->id.')');
        $this->newLine();
        $this->table(
            ['Concept', 'Mastery', 'Evidence', 'Action', 'Target', 'Reason', 'Algorithm'],
            $rows->all()
        );
        $this->newLine();
        $this->comment('Preview only: keputusan belum disimpan sebagai recommendation pada R3.2.');

        return self::SUCCESS;
    }
}
