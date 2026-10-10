<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UIDesignSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_button_primitive_renders_consistent_primary_and_secondary_actions(): void
    {
        $primary = Blade::render('<x-ui.button href="/courses">Buka Kursus</x-ui.button>');
        $secondary = Blade::render('<x-ui.button variant="secondary">Batal</x-ui.button>');

        $this->assertStringContainsString('rounded-xl', $primary);
        $this->assertStringContainsString('min-h-11', $primary);
        $this->assertStringContainsString('bg-indigo-600', $primary);
        $this->assertStringContainsString('href="/courses"', $primary);
        $this->assertStringContainsString('bg-slate-800', $secondary);
        $this->assertStringContainsString('Batal', $secondary);
    }

    public function test_status_badge_translates_internal_status_to_teacher_facing_indonesian(): void
    {
        $published = Blade::render('<x-ui.badge status="published" />');
        $archived = Blade::render('<x-ui.badge status="archived" />');
        $review = Blade::render('<x-ui.badge status="review" />');

        $this->assertStringContainsString('Terbit', $published);
        $this->assertStringNotContainsString('Published', $published);
        $this->assertStringContainsString('Diarsipkan', $archived);
        $this->assertStringContainsString('Perlu Dicek Guru', $review);
    }

    public function test_alert_primitive_keeps_status_semantics_and_accessible_structure(): void
    {
        $alert = Blade::render('<x-ui.alert tone="warning" title="Perlu perhatian">Periksa materi.</x-ui.alert>');

        $this->assertStringContainsString('role="status"', $alert);
        $this->assertStringContainsString('Perlu perhatian', $alert);
        $this->assertStringContainsString('Periksa materi.', $alert);
        $this->assertStringContainsString('border-amber-800/40', $alert);
    }

    public function test_shared_css_exposes_form_and_typography_primitives(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.ui-label', $css);
        $this->assertStringContainsString('.ui-field', $css);
        $this->assertStringContainsString('.ui-help', $css);
        $this->assertStringContainsString('.ui-error', $css);
        $this->assertStringContainsString('.ui-section-title', $css);
        $this->assertStringContainsString('.ui-text-link', $css);
    }

    public function test_course_collection_uses_consistent_indonesian_status_and_design_primitives(): void
    {
        $teacher = $this->teacher();
        Course::factory()->create([
            'instructor_id' => $teacher->id,
            'title' => 'Kursus Terbit',
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get(route('instructor.courses.index'))
            ->assertOk()
            ->assertSee('Kursus Terbit')
            ->assertSee('Terbit')
            ->assertDontSee('Published');
    }

    public function test_course_create_reference_screen_uses_shared_form_and_action_patterns(): void
    {
        $teacher = $this->teacher();

        $response = $this->actingAs($teacher)->get(route('instructor.courses.create'));

        $response->assertOk()
            ->assertSee('ui-label', false)
            ->assertSee('ui-field', false)
            ->assertSee('Simpan &amp; Lanjutkan ke Silabus', false);
    }

    private function teacher(): User
    {
        $role = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);

        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
