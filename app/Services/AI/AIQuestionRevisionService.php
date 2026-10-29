<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\QuestionBank;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AIQuestionRevisionService
{
    public function __construct(private AIContentService $aiContentService) {}

    /**
     * @param  array<int,mixed>  $questions
     * @return array{questions:list<array<string,mixed>>,provider:string,model:string}
     */
    public function reviseDraft(QuestionBank $bank, array $questions, string $instruction): array
    {
        $instruction = trim($instruction);
        Validator::make([
            'questions' => $questions,
            'revision_instruction' => $instruction,
        ], [
            'questions' => ['required', 'array', 'list', 'min:1', 'max:'.AIQuestionAuthoringService::MAX_QUESTIONS],
            'questions.*.question_text' => ['required', 'string', 'max:1200'],
            'questions.*.topic' => ['nullable', 'string', 'max:100'],
            'questions.*.difficulty' => ['required', 'in:easy,medium,hard'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['required', 'array', 'list', 'size:4'],
            'questions.*.options.*' => ['required', 'string', 'max:255'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0', 'max:3'],
            'revision_instruction' => ['required', 'string', 'min:3', 'max:1500'],
        ])->validate();

        $questionCount = count($questions);
        $systemPrompt = <<<PROMPT
Anda adalah AI Revision Assistant untuk guru SMK/SMA. Revisi draft soal pilihan ganda berdasarkan instruksi guru.

Aturan wajib:
1. Pertahankan tepat {$questionCount} soal dan urutan soal, kecuali instruksi guru secara eksplisit meminta perubahan redaksi pada soal tertentu.
2. Gunakan DRAFT SAAT INI sebagai batas fakta. Jangan menambahkan fakta, konsep, referensi, standar, atau contoh baru yang tidak dapat diturunkan dari draft yang diberikan.
3. Terapkan hanya perubahan yang diminta guru. Bagian yang tidak diminta sebaiknya dipertahankan semaksimal mungkin.
4. Setiap soal harus berdiri sendiri dan dapat dipahami tanpa konteks eksternal.
5. Jika soal menyebut program/kode/tabel/kasus "berikut", objek tersebut harus benar-benar disertakan dalam question_text.
6. Untuk soal output program, opsi jawaban harus berupa kemungkinan output yang benar-benar menjawab pertanyaan.
7. Setiap soal memiliki tepat 4 opsi unik dan tepat 1 jawaban benar.
8. Kunci jawaban dan explanation harus tetap konsisten dengan redaksi setelah revisi.
9. Hindari semua/tidak ada jawaban di atas dan hindari ambiguitas.
10. Jangan menyimpan atau mempublikasikan apa pun. Hasil ini tetap draft dan akan direview guru.
PROMPT;

        $schema = $this->questionSchema($questionCount);
        $userContent = json_encode([
            'bank_soal' => $bank->title,
            'instruksi_revisi' => $instruction,
            'draft_saat_ini' => $questions,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $output = $this->aiContentService->process(
            processType: AIProcessingLog::PROCESS_QUESTION_REVISION,
            sourceModel: $bank,
            systemPrompt: $systemPrompt,
            userContent: $userContent,
            schemaDefinition: $schema,
            maxOutputTokens: max(4096, 2048 + ($questionCount * 350)),
            forceRegenerate: true,
        );

        return [
            'questions' => $this->normalizeQuestions($output['parsed_data']['questions'] ?? [], $questionCount),
            'provider' => (string) $output['log']->provider,
            'model' => (string) $output['log']->model,
        ];
    }

    /** @return array<string,mixed> */
    private function questionSchema(int $questionCount): array
    {
        $optionProperties = [
            'order' => ['type' => 'integer'],
            'option_text' => ['type' => 'string'],
            'is_correct' => ['type' => 'boolean'],
        ];
        $questionProperties = [
            'order' => ['type' => 'integer'],
            'question_text' => ['type' => 'string'],
            'topic' => ['type' => 'string'],
            'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
            'explanation' => ['type' => 'string'],
            'points' => ['type' => 'integer'],
            'options' => [
                'type' => 'array',
                'minItems' => 4,
                'maxItems' => 4,
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => $optionProperties,
                    'required' => array_keys($optionProperties),
                ],
            ],
        ];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'questions' => [
                    'type' => 'array',
                    'minItems' => $questionCount,
                    'maxItems' => $questionCount,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => $questionProperties,
                        'required' => array_keys($questionProperties),
                    ],
                ],
            ],
            'required' => ['questions'],
        ];
    }

    /**
     * @param  array<int,mixed>  $questions
     * @return list<array<string,mixed>>
     */
    private function normalizeQuestions(array $questions, int $expectedCount): array
    {
        Validator::make(['questions' => $questions], [
            'questions' => ['required', 'array', 'list', 'size:'.$expectedCount],
            'questions.*.question_text' => ['required', 'string', 'max:1200'],
            'questions.*.topic' => ['required', 'string', 'max:150'],
            'questions.*.difficulty' => ['required', 'in:easy,medium,hard'],
            'questions.*.explanation' => ['required', 'string', 'max:1000'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['required', 'array', 'list', 'size:4'],
            'questions.*.options.*.option_text' => ['required', 'string', 'max:255'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
        ])->validate();

        $normalized = [];
        $seen = [];
        foreach ($questions as $index => $question) {
            $stem = $this->normalizeQuestionText((string) $question['question_text']);
            $key = mb_strtolower(preg_replace('/\s+/u', ' ', $stem) ?? $stem);
            if ($stem === '' || isset($seen[$key])) {
                throw new RuntimeException('AI menghasilkan pertanyaan kosong atau duplikat saat revisi.');
            }
            $seen[$key] = true;
            $this->assertSelfContained($stem, $index);

            $options = collect($question['options'])
                ->map(fn (array $option): string => trim((string) $option['option_text']))
                ->values();
            if ($options->contains('') || $options->map(fn (string $option): string => mb_strtolower($option))->unique()->count() !== 4) {
                throw new RuntimeException('AI menghasilkan pilihan jawaban yang tidak valid saat revisi.');
            }

            $correct = collect($question['options'])
                ->values()
                ->filter(fn (array $option): bool => $option['is_correct'] === true)
                ->keys();
            if ($correct->count() !== 1) {
                throw new RuntimeException('AI menghasilkan kunci jawaban yang tidak valid saat revisi.');
            }

            $normalized[] = [
                'question_text' => $stem,
                'topic' => mb_substr(trim((string) $question['topic']), 0, 100),
                'difficulty' => $question['difficulty'],
                'explanation' => trim((string) $question['explanation']),
                'points' => (int) $question['points'],
                'options' => $options->all(),
                'correct_option' => (int) $correct->first(),
            ];
        }

        return $normalized;
    }

    private function normalizeQuestionText(string $questionText): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($questionText));
        $normalized = preg_replace('/[ \t]+$/mu', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\n{3,}/u', "\n\n", $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function assertSelfContained(string $stem, int $index): void
    {
        $references = preg_match(
            '/\b(program|kode|potongan\s+kode|script|algoritma|tabel|diagram|gambar|kasus|pernyataan)\s+berikut\b/ui',
            $stem
        ) === 1;
        if (! $references) {
            return;
        }

        $hasArtifact = str_contains($stem, "\n")
            || str_contains($stem, '```')
            || preg_match('/(;|\{|\}|\bif\s*\(|\belse\b|\bswitch\s*\(|\bfor\s*\(|\bwhile\s*\(|System\.out|print\s*\(|printf\s*\()/ui', $stem) === 1;

        if (! $hasArtifact) {
            throw new RuntimeException('AI menghasilkan Soal '.($index + 1).' yang masih merujuk pada objek "berikut" tanpa menyertakan objek tersebut.');
        }
    }
}
