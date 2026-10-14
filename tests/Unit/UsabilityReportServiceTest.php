<?php

namespace Tests\Unit;

use App\Services\Usability\UsabilityReportService;
use InvalidArgumentException;
use Tests\TestCase;

class UsabilityReportServiceTest extends TestCase
{
    public function test_task_report_calculates_success_time_error_and_confusion_metrics(): void
    {
        $path = $this->csv(<<<CSV
participant_id,role,task_id,completed,time_seconds,errors,confusion_score,notes
G01,instructor,T1,1,120,0,1,
G02,instructor,T1,0,180,2,4,kesulitan menemukan tombol
S01,student,S1,1,60,0,1,
S02,student,S1,1,90,1,2,
CSV);

        $report = (new UsabilityReportService)->analyzeTasks($path);

        $this->assertSame(4, $report['participants']);
        $this->assertSame(4, $report['task_rows']);
        $this->assertSame(75.0, $report['success_rate']);
        $this->assertSame(105.0, $report['median_time_seconds']);
        $this->assertSame(0.75, $report['average_errors']);
        $this->assertSame(2.0, $report['average_confusion']);
        $this->assertSame(50.0, $report['by_task']['T1']['success_rate']);
        $this->assertSame(100.0, $report['by_task']['S1']['success_rate']);
    }

    public function test_sus_report_uses_standard_ten_item_scoring(): void
    {
        $path = $this->csv(<<<CSV
participant_id,role,q1,q2,q3,q4,q5,q6,q7,q8,q9,q10
G01,instructor,5,1,5,1,5,1,5,1,5,1
S01,student,4,2,4,2,4,2,4,2,4,2
CSV);

        $report = (new UsabilityReportService)->analyzeSus($path);

        $this->assertSame(2, $report['participants']);
        $this->assertSame(87.5, $report['average_sus']);
        $this->assertSame(87.5, $report['median_sus']);
        $this->assertSame(100.0, $report['participant_scores']['G01']);
        $this->assertSame(75.0, $report['participant_scores']['S01']);
    }

    public function test_invalid_sus_response_is_rejected(): void
    {
        $path = $this->csv(<<<CSV
participant_id,role,q1,q2,q3,q4,q5,q6,q7,q8,q9,q10
G01,instructor,6,1,5,1,5,1,5,1,5,1
CSV);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('q1 harus berada pada rentang');

        (new UsabilityReportService)->analyzeSus($path);
    }

    public function test_missing_task_header_is_rejected(): void
    {
        $path = $this->csv(<<<CSV
participant_id,role,task_id,completed,time_seconds,errors
G01,instructor,T1,1,120,0
CSV);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header CSV kurang: confusion_score');

        (new UsabilityReportService)->analyzeTasks($path);
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'usability-');
        file_put_contents($path, $content);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return $path;
    }
}
