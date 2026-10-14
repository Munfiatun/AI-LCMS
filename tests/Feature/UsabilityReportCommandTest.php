<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UsabilityReportCommandTest extends TestCase
{
    public function test_command_generates_json_report_from_task_and_sus_files(): void
    {
        $tasks = $this->tempCsv(<<<CSV
participant_id,role,task_id,completed,time_seconds,errors,confusion_score,notes
G01,instructor,I1,1,90,0,1,
S01,student,S1,1,45,0,1,
CSV);
        $sus = $this->tempCsv(<<<CSV
participant_id,role,q1,q2,q3,q4,q5,q6,q7,q8,q9,q10
G01,instructor,5,1,5,1,5,1,5,1,5,1
S01,student,4,2,4,2,4,2,4,2,4,2
CSV);

        $exit = Artisan::call('usability:report', [
            'tasks' => $tasks,
            '--sus' => $sus,
            '--json' => true,
        ]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('"success_rate": 100', $output);
        $this->assertStringContainsString('"average_sus": 87.5', $output);
    }

    public function test_command_fails_safely_for_invalid_file(): void
    {
        $exit = Artisan::call('usability:report', [
            'tasks' => '/tmp/does-not-exist-usability.csv',
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('File tidak dapat dibaca', Artisan::output());
    }

    private function tempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'usability-command-');
        file_put_contents($path, $content);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return $path;
    }
}
