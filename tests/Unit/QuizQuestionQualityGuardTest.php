<?php

namespace Tests\Unit;

use App\Services\AI\QuizQuestionQualityGuard;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QuizQuestionQualityGuardTest extends TestCase
{
    private QuizQuestionQualityGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new QuizQuestionQualityGuard;
    }

    public function test_accepts_clear_standalone_multiple_choice_item(): void
    {
        $this->guard->assertAcceptable(
            'Komponen aplikasi web manakah yang menangani logika bisnis dan pemrosesan data?',
            ['Frontend', 'Backend', 'Browser', 'Antarmuka pengguna'],
            'multiple_choice'
        );

        $this->assertTrue(true);
    }

    #[DataProvider('sourceFramedStems')]
    public function test_rejects_student_question_that_depends_on_slide_or_source_reference(string $stem): void
    {
        $this->expectException(ValidationException::class);

        $this->guard->assertAcceptable(
            $stem,
            ['Frontend', 'Backend', 'Database', 'Server'],
            'multiple_choice'
        );
    }

    /** @return array<string, array{string}> */
    public static function sourceFramedStems(): array
    {
        return [
            'menurut slide' => ['Menurut slide 4, komponen apa yang mengelola logika bisnis?'],
            'berdasarkan slide' => ['Berdasarkan slide 2, komponen manakah yang menyimpan data?'],
            'pada slide' => ['Pada slide 3, apa fungsi utama backend?'],
            'slide number' => ['Slide 5 menjelaskan komponen apa dalam aplikasi web?'],
            'materi di atas' => ['Berdasarkan materi di atas, apa fungsi database?'],
            'english framing' => ['According to the slide, which component stores structured data?'],
        ];
    }

    public function test_rejects_all_of_the_above_style_options(): void
    {
        $this->expectException(ValidationException::class);

        $this->guard->assertAcceptable(
            'Komponen manakah yang termasuk bagian aplikasi web?',
            ['Frontend', 'Backend', 'Database', 'Semua jawaban di atas'],
            'multiple_choice'
        );
    }

    public function test_source_slide_metadata_language_can_be_detected_without_rejecting_normal_concept_text(): void
    {
        $this->assertTrue($this->guard->containsSourceFraming('Menurut slide 4, server memproses request.'));
        $this->assertFalse($this->guard->containsSourceFraming('Server memproses request dan mengirim response ke browser.'));
    }
}
