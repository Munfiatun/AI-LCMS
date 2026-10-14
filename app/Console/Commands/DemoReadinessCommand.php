<?php

namespace App\Console\Commands;

use App\Services\Demo\DemoReadinessService;
use Illuminate\Console\Command;

class DemoReadinessCommand extends Command
{
    protected $signature = 'demo:check {--json : Tampilkan hasil sebagai JSON}';

    protected $description = 'Periksa apakah golden demo LMS-WEB siap dipresentasikan.';

    public function handle(DemoReadinessService $service): int
    {
        $report = $service->report();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $report['ready'] ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();
        $this->info('R9 — Demo & Academic Readiness');
        $this->table(
            ['Status', 'Pemeriksaan', 'Detail'],
            collect($report['checks'])->map(fn (array $check): array => [
                $check['passed'] ? 'PASS' : 'FAIL',
                $check['label'],
                $check['detail'],
            ])->all()
        );

        $this->line("Readiness: {$report['passed']}/{$report['total']} checks passed.");

        if (! $report['ready']) {
            $this->error('Golden demo belum siap. Perbaiki pemeriksaan FAIL sebelum presentasi.');

            return self::FAILURE;
        }

        $this->info('Golden demo siap digunakan.');

        return self::SUCCESS;
    }
}
