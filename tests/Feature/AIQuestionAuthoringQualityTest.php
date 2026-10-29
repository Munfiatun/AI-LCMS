<?php

namespace Tests\Feature;

use App\Services\AI\AIQuestionAuthoringService;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class AIQuestionAuthoringQualityTest extends TestCase
{
    public function test_incomplete_reference_to_program_is_rejected(): void
    {
        $service = app(AIQuestionAuthoringService::class);
        $method = new ReflectionMethod($service, 'normalizeGeneratedQuestions');

        $questions = [[
            'question_text' => 'Apa hasil program berikut jika nilai variabel a = 5?',
            'topic' => 'Percabangan Java',
            'difficulty' => 'easy',
            'explanation' => 'Karena a lebih besar dari 3, kondisi pertama terpenuhi.',
            'points' => 10,
            'options' => [
                ['order' => 1, 'option_text' => 'a > 3', 'is_correct' => true],
                ['order' => 2, 'option_text' => 'a < 3', 'is_correct' => false],
                ['order' => 3, 'option_text' => 'a == 5', 'is_correct' => false],
                ['order' => 4, 'option_text' => 'a >= 5', 'is_correct' => false],
            ],
        ]];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tanpa menyertakan objek tersebut');

        $method->invoke($service, $questions, 1, 'Percabangan Java');
    }

    public function test_multiline_code_is_preserved_when_question_contains_program_reference(): void
    {
        $service = app(AIQuestionAuthoringService::class);
        $method = new ReflectionMethod($service, 'normalizeGeneratedQuestions');

        $questions = [[
            'question_text' => "Perhatikan program berikut:\n\n```java\nint a = 5;\nif (a > 3) {\n    System.out.println(\"Lebih besar\");\n}\n```\n\nApa output program tersebut?",
            'topic' => 'Percabangan Java',
            'difficulty' => 'easy',
            'explanation' => 'Nilai a adalah 5 sehingga kondisi a > 3 bernilai benar dan teks Lebih besar dicetak.',
            'points' => 10,
            'options' => [
                ['order' => 1, 'option_text' => 'Lebih besar', 'is_correct' => true],
                ['order' => 2, 'option_text' => 'Lebih kecil', 'is_correct' => false],
                ['order' => 3, 'option_text' => 'Tidak ada output', 'is_correct' => false],
                ['order' => 4, 'option_text' => 'Error kompilasi', 'is_correct' => false],
            ],
        ]];

        $result = $method->invoke($service, $questions, 1, 'Percabangan Java');

        $this->assertStringContainsString("int a = 5;\nif (a > 3)", $result[0]['question_text']);
        $this->assertStringContainsString('Apa output program tersebut?', $result[0]['question_text']);
        $this->assertSame('Lebih besar', $result[0]['options'][0]);
    }
}
