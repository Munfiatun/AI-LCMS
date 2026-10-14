<?php

namespace App\Console\Commands;

use App\Services\Usability\UsabilityReportService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class UsabilityReportCommand extends Command
{
    protected $signature = 'usability:report
        {tasks : Path CSV hasil task usability}
        {--sus= : Path CSV respons SUS 10 item}
        {--json : Tampilkan hasil sebagai JSON}';

    protected $description = 'Ringkas hasil task usability dan System Usability Scale (SUS) secara reproducible.';

    public function handle(UsabilityReportService $service): int
    {
        try {
            $taskReport = $service->analyzeTasks((string) $this->argument('tasks'));
            $susPath = trim((string) $this->option('sus'));
            $susReport = $susPath !== '' ? $service->analyzeSus($susPath) : null;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $report = ['tasks' => $taskReport, 'sus' => $susReport];

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('R8 — Usability Validation Report');
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Partisipan', $taskReport['participants']],
                ['Observasi task', $taskReport['task_rows']],
                ['Task success', number_format($taskReport['success_rate'], 2).'%'],
                ['Median waktu', number_format($taskReport['median_time_seconds'], 2).' detik'],
                ['Rata-rata error', number_format($taskReport['average_errors'], 2)],
                ['Rata-rata kebingungan', number_format($taskReport['average_confusion'], 2).'/5'],
            ]
        );

        $taskRows = collect($taskReport['by_task'])->map(fn (array $row): array => [
            $row['task_id'],
            $row['role'],
            $row['attempts'],
            number_format((float) $row['success_rate'], 2).'%',
            number_format((float) $row['median_time_seconds'], 2).'s',
            number_format((float) $row['average_errors'], 2),
            number_format((float) $row['average_confusion'], 2).'/5',
        ])->values()->all();

        $this->table(
            ['Task', 'Role', 'N', 'Success', 'Median waktu', 'Error', 'Kebingungan'],
            $taskRows
        );

        if ($susReport !== null) {
            $this->info('System Usability Scale (SUS)');
            $this->table(
                ['Metrik', 'Nilai'],
                [
                    ['Partisipan SUS', $susReport['participants']],
                    ['Rata-rata SUS', number_format($susReport['average_sus'], 2).'/100'],
                    ['Median SUS', number_format($susReport['median_sus'], 2).'/100'],
                ]
            );
        } else {
            $this->warn('CSV SUS belum diberikan. Tambahkan --sus=path/to/sus.csv untuk menghitung SUS.');
        }

        return self::SUCCESS;
    }
}
