<?php

namespace Tests\Unit;

use App\Services\AI\AIContentService;
use App\Services\AI\AIQuestionAuthoringService;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class AIQuestionCodeFormattingTest extends TestCase
{
    public function test_inline_java_program_is_normalized_into_multiline_question_text(): void
    {
        $service = new AIQuestionAuthoringService(new AIContentService);
        $method = new ReflectionMethod($service, 'formatInlineCodeQuestion');
        $method->setAccessible(true);

        $input = 'Perhatikan potongan kode program Java berikut: int nilai = 75; if (nilai >= 80) { System.out.println("A"); } else { System.out.println("B"); } Apakah output dari eksekusi kode tersebut?';
        $formatted = $method->invoke($service, $input);

        $this->assertStringContainsString("berikut:\n\nint nilai = 75;", $formatted);
        $this->assertStringContainsString("if (nilai >= 80) {\n", $formatted);
        $this->assertStringContainsString("System.out.println(\"A\");\n", $formatted);
        $this->assertStringContainsString("else {\n", $formatted);
        $this->assertStringContainsString("\n\nApakah output dari eksekusi kode tersebut?", $formatted);
    }

    public function test_quality_gate_rejects_program_reference_when_code_remains_single_line(): void
    {
        $service = new AIQuestionAuthoringService(new AIContentService);
        $method = new ReflectionMethod($service, 'assertSelfContainedQuestion');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('kode satu baris');

        $method->invoke(
            $service,
            'Perhatikan kode berikut: int a = 5; if (a > 3) { System.out.println("A"); } Apa outputnya?',
            0,
        );
    }

    public function test_quality_gate_accepts_multiline_program_block(): void
    {
        $service = new AIQuestionAuthoringService(new AIContentService);
        $method = new ReflectionMethod($service, 'assertSelfContainedQuestion');
        $method->setAccessible(true);

        $method->invoke(
            $service,
            "Perhatikan kode berikut:\n\nint a = 5;\nif (a > 3) {\n    System.out.println(\"A\");\n}\n\nApa outputnya?",
            0,
        );

        $this->assertTrue(true);
    }
}
