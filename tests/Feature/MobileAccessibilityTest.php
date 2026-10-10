<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_styles_preserve_visible_focus_reduced_motion_and_touch_targets(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString(':focus-visible', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString('.a11y-skip-link', $css);
        $this->assertStringContainsString('min-height: 44px', $css);
        $this->assertStringContainsString('100dvh', $css);
        $this->assertStringContainsString('safe-area-inset-bottom', $css);
        $this->assertStringContainsString('forced-colors: active', $css);
    }

    public function test_accessibility_runtime_enhances_landmarks_sidebar_quiz_and_slidebook_navigation(): void
    {
        $script = file_get_contents(resource_path('js/accessibility.js'));
        $app = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($script);
        $this->assertIsString($app);
        $this->assertStringContainsString("main.id = 'main-content'", $script);
        $this->assertStringContainsString('Lewati ke konten utama', $script);
        $this->assertStringContainsString("aria-current', 'page'", $script);
        $this->assertStringContainsString('Buka menu navigasi', $script);
        $this->assertStringContainsString("event.key !== 'Escape'", $script);
        $this->assertStringContainsString("role', 'radiogroup'", $script);
        $this->assertStringContainsString("role', 'timer'", $script);
        $this->assertStringContainsString('Buka soal', $script);
        $this->assertStringContainsString("aria-current', 'step'", $script);
        $this->assertStringContainsString("import { enhanceAccessibility } from './accessibility'", $app);
        $this->assertStringContainsString('enhanceAccessibility();', $app);
    }

    public function test_slidebook_keeps_keyboard_navigation_focus_management_and_screen_reader_status(): void
    {
        $view = file_get_contents(resource_path('views/student/slidebooks/show.blade.php'));
        $script = file_get_contents(resource_path('views/student/slidebooks/presentation-script.blade.php'));

        $this->assertIsString($view);
        $this->assertIsString($script);
        $this->assertStringContainsString('role="progressbar"', $view);
        $this->assertStringContainsString('role="status"', $view);
        $this->assertStringContainsString('aria-labelledby="slide-title-', $view);
        $this->assertStringContainsString("event.key === 'ArrowLeft'", $script);
        $this->assertStringContainsString("event.key === 'ArrowRight'", $script);
        $this->assertStringContainsString("event.key === 'Home'", $script);
        $this->assertStringContainsString("event.key === 'End'", $script);
        $this->assertStringContainsString('title?.focus({ preventScroll: true })', $script);
    }

    public function test_quiz_retains_native_radio_controls_and_mobile_first_structure(): void
    {
        $view = file_get_contents(resource_path('views/student/quizzes/take.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('id="examForm"', $view);
        $this->assertStringContainsString('type="radio"', $view);
        $this->assertStringContainsString('x-model="answers[', $view);
        $this->assertStringContainsString('grid-cols-1', $view);
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_320px]', $view);
        $this->assertStringContainsString('sm:flex-row', $view);
    }
}
