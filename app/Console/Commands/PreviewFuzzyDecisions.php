<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\FuzzyAdaptiveEngine;
use Illuminate\Console\Command;

class PreviewFuzzyDecisions extends Command
{
    protected $signature = 'research:r3-preview-fuzzy {course_id} {student_id}';

    protected $description = 'Preview R3.4 Mamdani Fuzzy adaptive decisions without persisting recommendations';

    public function handle(FuzzyAdaptiveEngine $engine): int
    {
        $course = Course::query()->findOrFail((int) $this->argument('course_id'));
        $student = User::query()->findOrFail((int) $this->argument('student_id'));

        $concepts = LearningConcept::query()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        if ($concepts->isEmpty()) {
            $this->warn('Course belum memiliki concept aktif.');

            return self::FAILURE;
        }

        $this->info('R3.4 Mamdani Fuzzy Adaptive Decision Preview');
        $this->line("Course: {$course->title} | Student: {$student->name} (#{$student->id})");
        $this->newLine();

        $rows = $concepts->map(function (LearningConcept $concept) use ($student, $engine): array {
            $mastery = LearnerMastery::query()
                ->where('student_id', $student->id)
                ->where('concept_id', $concept->id)
                ->first();
            $decision = $engine->decide($student, $concept);
            $firedRules = collect($decision->metadata['fired_rules'] ?? [])
                ->map(fn (array $rule): string => '#'.$rule['rule'].'='.number_format((float) $rule['strength'], 2))
                ->implode(', ');

            return [
                $concept->code,
                $mastery ? number_format((float) $mastery->mastery_score, 2).'%' : '—',
                $mastery?->evidence_count ?? 0,
                number_format((float) $decision->inputs['prerequisite_mastery'], 2).'%',
                $decision->action,
                $decision->metadata['target_concept_code'] ?? '—',
                number_format((float) $decision->metadata['crisp_output'], 2),
                $firedRules !== '' ? $firedRules : '—',
                $decision->algorithmCode.'@'.$decision->algorithmVersion,
            ];
        });

        $this->table(
            ['Concept', 'Mastery', 'Evidence', 'Prereq', 'Action', 'Target', 'Crisp', 'Fired Rules', 'Algorithm'],
            $rows->all(),
        );

        $this->newLine();
        $this->comment('Preview only: fuzzy-v2 masih experimental dan keputusan belum disimpan sebagai recommendation.');

        return self::SUCCESS;
    }
}
