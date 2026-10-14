<?php

namespace Tests\Feature;

use App\Services\Demo\DemoReadinessService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoAcademicReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_readiness_fails_before_golden_demo_is_seeded(): void
    {
        config(['auth.demo_user_password' => 'demo-test-password']);

        $report = app(DemoReadinessService::class)->report();

        $this->assertFalse($report['ready']);
        $this->assertLessThan($report['total'], $report['passed']);
    }

    public function test_seeded_golden_demo_passes_all_readiness_checks(): void
    {
        $this->seedGoldenDemo();

        $report = app(DemoReadinessService::class)->report();

        $this->assertTrue($report['ready']);
        $this->assertSame($report['total'], $report['passed']);
        $this->assertGreaterThanOrEqual(10, $report['total']);
        $this->assertNotContains(false, array_column($report['checks'], 'passed'));
    }

    public function test_demo_check_command_returns_success_and_json_when_ready(): void
    {
        $this->seedGoldenDemo();

        $exit = Artisan::call('demo:check', ['--json' => true]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('"ready": true', $output);
        $this->assertStringContainsString('"slidebook_published"', $output);
        $this->assertStringContainsString('"quiz"', $output);
    }

    public function test_demo_check_command_returns_failure_when_demo_is_not_ready(): void
    {
        config(['auth.demo_user_password' => null]);

        $exit = Artisan::call('demo:check');

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Golden demo belum siap', Artisan::output());
    }

    private function seedGoldenDemo(): void
    {
        config(['auth.demo_user_password' => 'demo-test-password']);
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(LmsDemoSeeder::class);
    }
}
