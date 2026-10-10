<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;

class MockAIProvider implements AIProviderInterface
{
    protected string $model = 'mock-educational-v1';

    public function generateStructuredData(string $systemPrompt, string $userContent, array $schemaDefinition, ?int $maxOutputTokens = null): array
    {
        // Detect whether the request is for Question Extraction or Slidebook
        $isQuestionRequest = isset($schemaDefinition['properties']['questions'])
            || str_contains(strtolower($systemPrompt), 'soal')
            || str_contains(strtolower($systemPrompt), 'question');

        if ($isQuestionRequest) {
            return $this->generateQuestions($userContent);
        }

        return $this->generateSlides($userContent);
    }

    /**
     * Generate structured questions with Answer Key Safety compliance.
     *
     * @return array{
     *     raw_response: string,
     *     parsed_data: array<string, mixed>,
     *     tokens_used: int
     * }
     */
    protected function generateQuestions(string $userContent): array
    {
        $rawLines = explode("\n", trim($userContent));
        $paragraphs = array_values(array_filter(array_map('trim', $rawLines), fn ($line) => strlen($line) > 10));

        $topic = ! empty($paragraphs[0]) ? mb_substr($paragraphs[0], 0, 50) : 'Umum';

        $questions = [];

        // Question 1: Explicit answer key
        $questions[] = [
            'order' => 1,
            'question_text' => 'Berdasarkan materi yang dipelajari, manakah pernyataan berikut yang paling tepat mengenai konsep dasar '.$topic.'?',
            'type' => 'multiple_choice',
            'topic' => $topic,
            'difficulty' => 'easy',
            'explanation' => 'Pernyataan ini secara eksplisit diterangkan pada paragraf pembuka materi referensi.',
            'points' => 10,
            'needs_review' => false,
            'answer_source' => 'explicit',
            'options' => [
                ['order' => 1, 'option_text' => 'Prinsip pemisahan tanggung jawab dan abstraksi modular.', 'is_correct' => true],
                ['order' => 2, 'option_text' => 'Penyatuan semua komponen logic ke dalam satu controller tunggal.', 'is_correct' => false],
                ['order' => 3, 'option_text' => 'Menghindari penggunaan database dalam aplikasi enterprise.', 'is_correct' => false],
                ['order' => 4, 'option_text' => 'Mengabaikan aspek skalabilitas demi kecepatan prototyping.', 'is_correct' => false],
            ],
        ];

        // Question 2: Explicit answer key
        $questions[] = [
            'order' => 2,
            'question_text' => 'Apa tujuan utama penerapan arsitektur berlapis (layered architecture) pada sistem aplikasi modern?',
            'type' => 'multiple_choice',
            'topic' => $topic,
            'difficulty' => 'medium',
            'explanation' => 'Arsitektur berlapis memisahkan presentation, application/domain, dan infrastructure layer agar mudah diuji dan dirawat.',
            'points' => 10,
            'needs_review' => false,
            'answer_source' => 'explicit',
            'options' => [
                ['order' => 1, 'option_text' => 'Membuat ukuran kode lebih besar dan kompleks.', 'is_correct' => false],
                ['order' => 2, 'option_text' => 'Meningkatkan testability, modularitas, dan pemeliharaan jangka panjang.', 'is_correct' => true],
                ['order' => 3, 'option_text' => 'Mengharuskan penggunaan frontend terpisah tanpa SSR.', 'is_correct' => false],
                ['order' => 4, 'option_text' => 'Membatasi hak akses pengguna secara fisik.', 'is_correct' => false],
            ],
        ];

        // Question 3: Answer Key Safety Flag (Inferred answer - needs review)
        $questions[] = [
            'order' => 3,
            'question_text' => 'Bagaimana dampak pengacakan urutan soal dan opsi jawaban terhadap integritas asesmen siswa?',
            'type' => 'multiple_choice',
            'topic' => 'Asesmen Adaptif',
            'difficulty' => 'hard',
            'explanation' => 'Kunci jawaban disimpulkan oleh AI dari konteks umum namun perlu validasi akhir oleh guru.',
            'points' => 10,
            'needs_review' => true, // Flagged for teacher review
            'answer_source' => 'inferred', // Inferred source
            'options' => [
                ['order' => 1, 'option_text' => 'Mencegah potensi kecurangan dan pembagian urutan jawaban antar-peserta.', 'is_correct' => true],
                ['order' => 2, 'option_text' => 'Menurunkan standar kelulusan passing grade siswa secara otomatis.', 'is_correct' => false],
                ['order' => 3, 'option_text' => 'Menghapus catatan riwayat pengerjaan attempt di server.', 'is_correct' => false],
                ['order' => 4, 'option_text' => 'Membuat durasi pengerjaan kuis menjadi tidak terbatas.', 'is_correct' => false],
            ],
        ];

        $parsedData = [
            'title' => 'Ekstraksi Soal: '.$topic,
            'questions' => $questions,
        ];

        $rawResponse = (string) json_encode($parsedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return [
            'raw_response' => $rawResponse,
            'parsed_data' => $parsedData,
            'tokens_used' => 320,
        ];
    }

    /**
     * Generate structured slides.
     *
     * @return array{
     *     raw_response: string,
     *     parsed_data: array<string, mixed>,
     *     tokens_used: int
     * }
     */
    protected function generateSlides(string $userContent): array
    {
        $rawLines = explode("\n", trim($userContent));
        $paragraphs = array_values(array_filter(array_map('trim', $rawLines), fn ($line) => strlen($line) > 10));

        $mainTitle = ! empty($paragraphs[0]) ? mb_substr($paragraphs[0], 0, 80) : 'Materi Pembelajaran';
        if (str_contains($mainTitle, '.')) {
            $mainTitle = explode('.', $mainTitle)[0];
        }

        $overview = count($paragraphs) > 1 ? $paragraphs[1] : 'Pengenalan materi dan konsep dasar.';

        $slides = [];

        // 1. Cover Slide
        $slides[] = [
            'order' => 1,
            'title' => $mainTitle,
            'subtitle' => 'Ringkasan Eksekutif & Struktur Pembelajaran',
            'content' => $overview,
            'summary' => 'Slide pengantar materi utama kursus.',
            'layout' => 'cover',
            'source_reference' => ['paragraph' => 1],
            'needs_review' => false,
        ];

        // 2. Learning Objectives Slide
        $objectives = [
            'Memahami konsep fundamental materi pembelajaran.',
            'Mampu menganalisis contoh kasus dan penerapannya.',
            'Menguasai evaluasi dan kesimpulan inti materi.',
        ];
        $slides[] = [
            'order' => 2,
            'title' => 'Tujuan Pembelajaran',
            'subtitle' => 'Kompetensi yang Diharapkan',
            'content' => implode("\n", array_map(fn ($obj, $i) => ($i + 1).'. '.$obj, $objectives, array_keys($objectives))),
            'summary' => 'Daftar sasaran capaian pembelajaran siswa.',
            'layout' => 'key-points',
            'source_reference' => ['inferred' => true],
            'needs_review' => false,
        ];

        // 3. Content Slides extracted from paragraphs
        $contentParagraphs = array_slice($paragraphs, 2, 4);
        if (empty($contentParagraphs)) {
            $contentParagraphs = [
                'Pembahasan materi inti menguraikan prinsip-prinsip utama yang dijelaskan di dalam dokumen referensi.',
                'Penerapan praktis dari materi ini memerlukan pemahaman mendalam tentang setiap komponen yang telah dipelajari.',
            ];
        }

        $order = 3;
        foreach ($contentParagraphs as $idx => $para) {
            $subTitle = 'Konsep Inti Bagian '.($idx + 1);
            $paraSummary = mb_substr($para, 0, 120).(strlen($para) > 120 ? '...' : '');
            $layout = $idx % 2 === 0 ? 'concept' : 'reading';

            $slides[] = [
                'order' => $order++,
                'title' => $subTitle,
                'subtitle' => 'Pembahasan Detail',
                'content' => $para,
                'summary' => $paraSummary,
                'layout' => $layout,
                'source_reference' => ['paragraph' => $idx + 3],
                'needs_review' => false,
            ];
        }

        // 4. Summary Slide
        $slides[] = [
            'order' => $order,
            'title' => 'Rangkuman & Intisari',
            'subtitle' => 'Kesimpulan Akhir Materi',
            'content' => 'Seluruh konsep pokok dalam materi ini telah dibahas secara terstruktur. Siswa diharapkan meninjau kembali poin-poin utama sebelum melanjutkan ke evaluasi asesmen.',
            'summary' => 'Rangkuman intisari dokumen.',
            'layout' => 'summary',
            'source_reference' => ['type' => 'summary'],
            'needs_review' => false,
        ];

        $parsedData = [
            'title' => $mainTitle,
            'subtitle' => 'Slidebook Terstruktur Pembelajaran',
            'description' => $overview,
            'version' => 1,
            'slides' => $slides,
        ];

        $rawResponse = (string) json_encode($parsedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return [
            'raw_response' => $rawResponse,
            'parsed_data' => $parsedData,
            'tokens_used' => 150 + (count($slides) * 45),
        ];
    }

    public function getProviderName(): string
    {
        return 'mock';
    }

    public function getModelName(): string
    {
        return $this->model;
    }
}
