<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmComparisonService;
use Illuminate\Console\Command;

class CompareAdaptiveAlgorithms extends Command
{
    protected $signature = 'research:r3-compare-algorithms {courseId} {studentId}';

    protected $description = 'Compare rule-based-v1 and fuzzy-v2 decisions for one student across an active course domain.';

    public function handle(AdaptiveAlgorithmComparisonService $comparison): int
    {
        $course = Course::query()->findOrFail((int) $this->argument('courseId'));
        $student = User::query()->findOrFail((int) $this->argument('studentId'));

        $result = $comparison->compareCourse($student, $course);
        $rows = $result['rows'];
        $summary = $result['summary'];

        $conceptCodes = LearningConcept::query()
            ->where('course_id', $course->id)
            ->pluck('code', 'id');

        $this->info('R3.5 Rule-Based vs Fuzzy Comparison');
        $this->line("Course: {$course->title} | Student: {$student->name} (#{$student->id})");
        $this->newLine();

        $this->table(
            ['Concept', 'Rule Action', 'Rule Target', 'Fuzzy Action', 'Fuzzy Target', 'Crisp', 'Action', 'Target'],
            $rows->map(function (array $row) use ($conceptCodes): array {
                return [
                    $row['concept']->code,
                    $row['rule_action'],
                    $row['rule_target_concept_id'] ? ($conceptCodes[$row['rule_target_concept_id']] ?? '#'.$row['rule_target_concept_id']) : '—',
                    $row['fuzzy_action'],
                    $row['fuzzy_target_concept_id'] ? ($conceptCodes[$row['fuzzy_target_concept_id']] ?? '#'.$row['fuzzy_target_concept_id']) : '—',
                    $row['fuzzy_crisp_output'] !== null ? number_format((float) $row['fuzzy_crisp_output'], 2) : '—',
                    $row['action_agreement'] ? 'SAME' : 'DIFF',
                    $row['target_agreement'] ? 'SAME' : 'DIFF',
                ];
            })->all()
        );

        $this->newLine();
        $this->line('Action agreement : '.$summary['action_agreements'].'/'.$summary['total_concepts'].' ('.number_format((float) $summary['action_agreement_rate'], 2).'%)');
        $this->line('Target agreement : '.$summary['target_agreements'].'/'.$summary['total_concepts'].' ('.number_format((float) $summary['target_agreement_rate'], 2).'%)');
        $this->line('Full agreement   : '.$summary['full_agreements'].'/'.$summary['total_concepts'].' ('.number_format((float) $summary['full_agreement_rate'], 2).'%)');
        $this->line('Divergences      : '.$summary['full_divergences']);
        $this->newLine();
        $this->warn('Comparison only: fuzzy-v2 remains experimental; agreement is descriptive evidence, not proof that one algorithm is superior.');

        return self::SUCCESS;
    }
}
