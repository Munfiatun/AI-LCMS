<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UXWorkflowLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_language_guideline_keeps_teacher_facing_terms_action_oriented(): void
    {
        $guide = file_get_contents(base_path('docs/product/ux-workflow-refinement.md'));

        $this->assertIsString($guide);
        $this->assertStringContainsString('Create Revision` / `Revision` → `Buat Versi Baru', $guide);
        $this->assertStringContainsString('Source Reference` → `Sumber Materi', $guide);
        $this->assertStringContainsString('needs_review` → `Perlu Dicek Guru', $guide);
        $this->assertStringContainsString('Layout` → `Tampilan Slide', $guide);
    }
}
