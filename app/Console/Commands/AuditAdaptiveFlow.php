<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveFlowAcceptanceAuditService;
use Illuminate\Console\Command;

class AuditAdaptiveFlow extends Command
{
    protected $signature = 'research:r4-acceptance-audit {course} {student}';

    protected $description = 'Audit R4 adaptive learning flow acceptance gates for a learner and course.';

    public function handle(AdaptiveFlowAcceptanceAuditService $service): int
    {
        $course = Course::query()->findOrFail((int) $this->argument('course'));
        $student = User::query()->findOrFail((int) $this->argument('student'));
        $result = $service->audit($student, $course);

        $this->newLine();
        $this->info('R4.6 Adaptive Learning Flow Acceptance Audit');
        $this->line("Course: {$course->title} | Student: {$student->name} (#{$student->id})");
        $this->newLine();

        foreach ($result['gates'] as $index => $gate) {
            $status = $gate['passed'] ? 'PASS' : 'FAIL';
            $this->line(($index + 1).". [{$status}] {$gate['title']}");
            $this->line('   '.$gate['detail']);
        }

        $this->newLine();
        $this->table(
            ['Concept', 'Mastery', 'Evidence', 'Active Action', 'Target', 'Trigger'],
            $result['rows']->map(fn (array $row): array => [
                $row['concept']->code,
                is_numeric($row['mastery_score']) ? number_format((float) $row['mastery_score'], 2).'%' : '—',
                $row['evidence_count'],
                $row['recommendation_action'] ?? '—',
                $row['target_code'] ?? '—',
                $row['adaptation_trigger'] ?? 'initial/manual',
            ])->all(),
        );

        if ($result['current_focus']) {
            $focus = $result['current_focus'];
            $this->line('Current focus: '.($focus['concept']?->code ?? '—').' — '.($focus['concept']?->name ?? '—'));
            $this->line('Current action: '.$focus['action']);
        }

        if ($result['last_intervention']) {
            $intervention = $result['last_intervention'];
            $this->line('Last tested intervention: '.($intervention['concept']?->code ?? '—')
                .' | material '.($intervention['material']?->title ?? '—')
                .' | formative '.($intervention['formative_attempt']?->quiz?->title ?? '—'));
        }

        $this->newLine();
        $summary = $result['summary'];

        if ($summary['passed']) {
            $this->info("PASS — Adaptive Learning Flow M4 siap ({$summary['passed_gates']}/{$summary['total_gates']} gate)");

            return self::SUCCESS;
        }

        $this->warn("BELUM PASS — {$summary['passed_gates']}/{$summary['total_gates']} gate terpenuhi");
        $this->line('Lengkapi material mapping, formative reassessment, atau jalankan satu siklus formative nyata lalu audit ulang.');

        return self::FAILURE;
    }
}
