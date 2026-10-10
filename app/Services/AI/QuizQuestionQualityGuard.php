<?php

namespace App\Services\AI;

use Illuminate\Validation\ValidationException;

class QuizQuestionQualityGuard
{
    /**
     * Student-facing questions must stand on their own. Slide/document references
     * belong to provenance metadata, never to the question stem or answer options.
     */
    public function assertAcceptable(string $questionText, array $options, string $type): void
    {
        $stem = trim(preg_replace('/\s+/u', ' ', $questionText) ?? $questionText);

        if (mb_strlen($stem) < 12 || mb_strlen($stem) > 500) {
            $this->fail();
        }

        if ($this->containsSourceFraming($stem)) {
            $this->fail();
        }

        if (substr_count($stem, '?') > 1) {
            $this->fail();
        }

        foreach ($options as $option) {
            $option = trim((string) $option);
            if ($option === '' || mb_strlen($option) > 255 || $this->containsSourceFraming($option)) {
                $this->fail();
            }
        }

        if ($type !== 'true_false') {
            foreach ($options as $option) {
                $normalized = mb_strtolower(trim((string) $option));
                if (preg_match('/\b(semua (?:jawaban|pilihan)(?: di atas)?|semua di atas|tidak ada (?:jawaban|pilihan)(?: di atas)?|all of the above|none of the above)\b/u', $normalized)) {
                    $this->fail();
                }
            }
        }
    }

    public function containsSourceFraming(string $text): bool
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? $text));

        $patterns = [
            '/\b(?:menurut|berdasarkan|merujuk(?: pada)?|sesuai)\s+(?:slide|materi|teks|dokumen)\b/u',
            '/\b(?:pada|di)\s+slide(?:\s+(?:ke[- ]?)?\d+)?\b/u',
            '/\bslide\s*(?:ke[- ]?)?\d+\b/u',
            '/\b(?:materi|teks|dokumen)\s+(?:di atas|sebelumnya|tersebut)\b/u',
            '/\b(?:according to|based on|as stated in|as shown in)\s+(?:the\s+)?(?:slide|material|text|document)\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    private function fail(): never
    {
        throw ValidationException::withMessages([
            'quiz' => 'Soal AI belum memenuhi standar kualitas penulisan soal.',
        ]);
    }
}
