<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoCourseSeeder;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\WebLearningDummySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_does_not_create_demo_users_or_courses_in_production(): void
    {
        config(['auth.demo_user_password' => 'should-never-be-used-in-production']);
        $this->app['env'] = 'production';

        try {
            $this->runSeederDirectly(DatabaseSeeder::class);
        } finally {
            $this->app['env'] = 'testing';
        }

        $this->assertDatabaseCount('roles', 3);
        $this->assertGreaterThan(0, Category::count());
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_explicit_demo_seeders_are_no_ops_in_production(): void
    {
        $role = Role::create(['name' => Role::ROLE_INSTRUCTOR, 'label' => 'Instructor']);
        $instructor = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'instructor@example.com',
            'is_active' => true,
        ]);
        Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Pemograman Web',
            'slug' => 'pemograman-web',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $baselineCategoryCount = Category::count();

        $this->app['env'] = 'production';

        try {
            $this->runSeederDirectly(CourseSeeder::class);
            $this->runSeederDirectly(DemoCourseSeeder::class);
            $this->runSeederDirectly(WebLearningDummySeeder::class);
            $this->runSeederDirectly(LmsDemoSeeder::class);
        } finally {
            $this->app['env'] = 'testing';
        }

        $this->assertDatabaseCount('courses', 1);
        $this->assertSame($baselineCategoryCount, Category::count());
        $this->assertDatabaseCount('learning_materials', 0);
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('slidebooks', 0);
        $this->assertSame('Pemograman Web', Course::sole()->title);
    }

    /**
     * Run a seeder through its class instead of the db:seed console command.
     *
     * The console command intentionally asks for confirmation in production.
     * These tests are verifying the seeders' own production guards, so invoking
     * the class directly avoids testing Symfony's interactive confirmation.
     *
     * @param  class-string<Seeder>  $seederClass
     */
    private function runSeederDirectly(string $seederClass): void
    {
        /** @var Seeder $seeder */
        $seeder = $this->app->make($seederClass);
        $seeder->setContainer($this->app);
        $seeder->run();
    }
}
