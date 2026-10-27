<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveEngineAcceptanceAuditService;
use Illuminate\Console\Command;

class AuditAdaptiveEngine extends Command
{
    protected $signature = 'research:r3-acceptance-audit {course} {student}';

    protected $description = 'Audit R3 adaptive engine acceptance gates for a learner and course.';

    public function handle(AdaptiveEngineAcceptanceAuditService $service): int
    {
        $course = Course::query()->findOrFail((int) $this->argument('course'));
        $student = User::query()->findOrFail((int) $this->argument('student'));
        $result = $service->audit($student, $course);

        $this->newLine();
        $this->info('R3.6 Adaptive Engine Acceptance Audit');
        $this->line("Course: {$course->title} | Student: {$student->name} (#{$student->id})");
        $this->newLine();

        foreach ($result['gates'] as $index => $gate) {
            $status = $gate['passed'] ? 'PASS' : 'FAIL';
            $this->line(($index + 1).". [{$status}] {$gate['title']}");
            $this->line('   '.$gate['detail']);
        }

        $this->newLine();
        $this->table(
            ['Concept', 'Rule Action', 'Fuzzy Action', 'Crisp', 'Recommendation', 'Rule Det.', 'Fuzzy Trace'],
            $result['rows']->map(fn (array $row): array => [
                $row['concept']->code,
                $row['rule_action'],
                $row['fuzzy_action'],
                is_numeric($row['fuzzy_crisp_output']) ? number_format((float) $row['fuzzy_crisp_output'], 2) : '—',
                $row['recommendation_traceable'] ? 'TRACEABLE' : 'MISSING/STALE',
                $row['rule_deterministic'] ? 'YES' : 'NO',
                $row['fuzzy_traceable'] ? 'YES' : 'NO',
            ])->all(),
        );

        $summary = $result['summary'];
        $comparison = $result['comparison'];

        $this->line('Comparison agreement: '.number_format((float) $comparison['full_agreement_rate'], 2).'%');
        $this->line('Comparison divergences: '.$comparison['full_divergences']);
        $this->newLine();

        if ($summary['passed']) {
            $this->info("PASS — Adaptive Engine M3 siap ({$summary['passed_gates']}/{$summary['total_gates']} gate)");

            return self::SUCCESS;
        }

        $this->warn("BELUM PASS — {$summary['passed_gates']}/{$summary['total_gates']} gate terpenuhi");
        $this->line('Jika gate persistence gagal, jalankan research:r3-generate-recommendations untuk learner/course ini lalu audit ulang.');

        return self::FAILURE;
    }
}
