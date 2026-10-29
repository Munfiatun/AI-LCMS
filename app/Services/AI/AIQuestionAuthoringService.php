<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AIQuestionAuthoringService
{
    public const MIN_QUESTIONS = 2;

    public const MAX_QUESTIONS = 20;

    public function __construct(private AIContentService $aiContentService) {}

    /**
     * Generate a prompt-based multiple-choice question draft without persisting questions.
     *
     * @param  array{topic:string,total_questions:int,difficulty:string,instructions?:string|null}  $settings
     * @return array{questions:list<array<string,mixed>>,provider:string,model:string}
     */
    public function generateDraft(QuestionBank $bank, array $settings, bool $forceRegenerate = false): array
    {
        $settings = Validator::make($settings, [
            'topic' => ['required', 'string', 'max:255'],
            'total_questions' => ['required', 'integer', 'min:'.self::MIN_QUESTIONS, 'max:'.self::MAX_QUESTIONS],
            'difficulty' => ['required', 'in:easy,medium,hard,mixed'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $questionCount = (int) $settings['total_questions'];
        $difficultyRule = match ($settings['difficulty']) {
            'easy' => 'Semua soal tingkat mudah: fokus pada mengenali, mengingat, dan memahami konsep dasar.',
            'medium' => 'Semua soal tingkat sedang: fokus pada pemahaman hubungan konsep dan penerapan sederhana.',
            'hard' => 'Semua soal tingkat sulit: fokus pada analisis, penerapan, perbandingan, dan pemecahan masalah.',
            default => 'Gunakan campuran tingkat mudah, sedang, dan sulit secara wajar.',
        };

        $courseContext = $bank->course?->title
            ? "Konteks kursus: {$bank->course->title}."
            : 'Bank soal bersifat global dan tidak terikat pada satu kursus.';

        $systemPrompt = <<<PROMPT
Anda adalah AI Authoring Assistant untuk guru SMK/SMA. Buat draft soal pilihan ganda yang siap direview guru.

Aturan wajib:
1. Buat tepat {$questionCount} soal berdasarkan topik dan instruksi guru.
2. Setiap soal WAJIB berdiri sendiri dan dapat dipahami siswa tanpa melihat teks, slide, dokumen, kode, tabel, gambar, atau konteks lain yang tidak tertulis di dalam question_text.
3. DILARANG menulis rujukan menggantung seperti "program berikut", "kode berikut", "potongan kode berikut", "tabel berikut", "diagram berikut", "kasus berikut", "berdasarkan kode di atas", "menurut materi", "berdasarkan slide", atau "dokumen di atas" jika objek yang dirujuk tidak langsung disertakan di dalam question_text.
4. Untuk soal pemrograman yang menanyakan output, error, kondisi, atau alur program: sertakan potongan kode yang LENGKAP dan relevan langsung di question_text. Pertanyaan harus tetap jelas bila dibaca sendirian.
5. Jika tidak perlu potongan kode, tulis ulang soal menjadi kalimat mandiri. Contoh: jangan menulis "Apa hasil program berikut jika a = 5?" tanpa kode. Lebih baik tulis "Jika a = 5 dan program mengevaluasi kondisi if (a > 3), kondisi tersebut bernilai apa?" atau sertakan kode lengkapnya.
6. Jika question_text berisi kode, pertahankan line break agar struktur kode mudah dibaca siswa. Jangan menghilangkan bagian kode yang diperlukan untuk menentukan jawaban.
7. Setiap soal memiliki tepat 4 opsi unik dan hanya 1 jawaban benar.
8. Opsi harus benar-benar menjawab pertanyaan. Jika soal menanyakan OUTPUT program, opsi harus berupa kemungkinan output, bukan potongan kondisi yang tidak menjawab pertanyaan.
9. Distraktor harus masuk akal, homogen, dan tidak memberi petunjuk ke jawaban benar.
10. Hindari "semua jawaban di atas" dan "tidak ada jawaban di atas".
11. Hindari pertanyaan ambigu atau lebih dari satu jawaban yang dapat dibenarkan.
12. explanation maksimal 2 kalimat dan menjelaskan alasan jawaban benar berdasarkan informasi yang tersedia pada soal.
13. points gunakan 10 untuk setiap soal.
14. topic harus ringkas, maksimal sekitar 100 karakter.
15. Sebelum mengembalikan JSON, periksa setiap soal dengan pertanyaan: "Apakah siswa dapat menjawab soal ini hanya dari question_text dan opsi yang tersedia?" Jika tidak, lengkapi atau tulis ulang soal tersebut.
16. Draft ini akan ditinjau guru sebelum disimpan. Gunakan pengetahuan model secara hati-hati dan jangan mengarang referensi, kutipan, sumber, atau standar yang tidak diberikan guru.

{$difficultyRule}
{$courseContext}
PROMPT;

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
        $schema = [
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

        $userContent = json_encode([
            'bank_soal' => $bank->title,
            'topik' => trim($settings['topic']),
            'jumlah_soal' => $questionCount,
            'kesulitan' => $settings['difficulty'],
            'instruksi_guru' => trim((string) ($settings['instructions'] ?? '')),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $output = $this->aiContentService->process(
            processType: AIProcessingLog::PROCESS_QUESTION_AUTHORING,
            sourceModel: $bank,
            systemPrompt: $systemPrompt,
            userContent: $userContent,
            schemaDefinition: $schema,
            maxOutputTokens: max(4096, 2048 + ($questionCount * 350)),
            forceRegenerate: $forceRegenerate,
        );

        $questions = $this->normalizeGeneratedQuestions(
            $output['parsed_data']['questions'] ?? [],
            $questionCount,
            trim($settings['topic'])
        );

        return [
            'questions' => $questions,
            'provider' => (string) $output['log']->provider,
            'model' => (string) $output['log']->model,
        ];
    }

    /**
     * Persist a teacher-reviewed AI draft as verified questions.
     *
     * @param  array<int, mixed>  $questions
     * @return Collection<int, Question>
     */
    public function persistReviewedDraft(QuestionBank $bank, array $questions): Collection
    {
        $questions = $this->validateReviewedQuestions($questions);

        return DB::transaction(function () use ($bank, $questions): Collection {
            $created = collect();
            $order = (int) $bank->questions()->max('order');

            foreach ($questions as $data) {
                $order++;
                $question = Question::create([
                    'question_bank_id' => $bank->id,
                    'question_text' => trim($data['question_text']),
                    'type' => Question::TYPE_MULTIPLE_CHOICE,
                    'topic' => mb_substr(trim($data['topic'] ?: 'Umum'), 0, 100),
                    'difficulty' => $data['difficulty'],
                    'explanation' => trim((string) ($data['explanation'] ?? '')),
                    'points' => (int) $data['points'],
                    'order' => $order,
                    'needs_review' => false,
                    'answer_source' => Question::SOURCE_INFERRED,
                    'status' => Question::STATUS_APPROVED,
                ]);

                foreach ($data['options'] as $index => $optionText) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => trim($optionText),
                        'is_correct' => $index === (int) $data['correct_option'],
                        'order' => $index + 1,
                    ]);
                }

                $created->push($question);
            }

            return $created;
        });
    }

    /**
     * @param  array<int, mixed>  $questions
     * @return list<array<string,mixed>>
     */
    private function normalizeGeneratedQuestions(array $questions, int $expectedCount, string $fallbackTopic): array
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
        $seenQuestions = [];

        foreach ($questions as $index => $question) {
            $stem = $this->normalizeQuestionText((string) $question['question_text']);
            $stemKey = mb_strtolower(preg_replace('/\s+/u', ' ', $stem) ?? $stem);
            if ($stem === '' || isset($seenQuestions[$stemKey])) {
                throw new RuntimeException('AI menghasilkan pertanyaan kosong atau duplikat.');
            }
            $seenQuestions[$stemKey] = true;

            $this->assertSelfContainedQuestion($stem, $index);

            $optionTexts = collect($question['options'])
                ->map(fn (array $option): string => trim((string) $option['option_text']))
                ->values();
            if ($optionTexts->contains('') || $optionTexts->map(fn (string $option): string => mb_strtolower($option))->unique()->count() !== 4) {
                throw new RuntimeException('AI menghasilkan pilihan jawaban yang tidak valid.');
            }

            $correctIndexes = collect($question['options'])
                ->values()
                ->filter(fn (array $option): bool => $option['is_correct'] === true)
                ->keys();
            if ($correctIndexes->count() !== 1) {
                throw new RuntimeException('AI menghasilkan kunci jawaban yang tidak valid.');
            }

            $normalized[] = [
                'question_text' => $stem,
                'topic' => mb_substr(trim((string) ($question['topic'] ?? $fallbackTopic)), 0, 100),
                'difficulty' => $question['difficulty'],
                'explanation' => trim((string) $question['explanation']),
                'points' => (int) $question['points'],
                'options' => $optionTexts->all(),
                'correct_option' => (int) $correctIndexes->first(),
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

    private function assertSelfContainedQuestion(string $stem, int $index): void
    {
        $referencesExternalArtifact = preg_match(
            '/\b(program|kode|potongan\s+kode|script|algoritma|tabel|diagram|gambar|kasus|pernyataan)\s+berikut\b/ui',
            $stem
        ) === 1;

        if (! $referencesExternalArtifact) {
            return;
        }

        $hasMultilineArtifact = str_contains($stem, "\n");
        $hasCodeFence = str_contains($stem, '```');
        $hasProgrammingSyntax = preg_match(
            '/(;|\{|\}|\bif\s*\(|\belse\b|\bswitch\s*\(|\bfor\s*\(|\bwhile\s*\(|System\.out|print\s*\(|printf\s*\()/ui',
            $stem
        ) === 1;

        if (! $hasMultilineArtifact && ! $hasCodeFence && ! $hasProgrammingSyntax) {
            throw new RuntimeException(
                'AI menghasilkan Soal '.($index + 1).' yang merujuk pada program/kode/tabel/objek "berikut" tanpa menyertakan objek tersebut. Silakan generate ulang.'
            );
        }
    }

    /**
     * @param  array<int, mixed>  $questions
     * @return list<array<string,mixed>>
     */
    private function validateReviewedQuestions(array $questions): array
    {
        Validator::make(['questions' => $questions], [
            'questions' => ['required', 'array', 'list', 'min:1', 'max:'.self::MAX_QUESTIONS],
            'questions.*.question_text' => ['required', 'string', 'max:1200'],
            'questions.*.topic' => ['nullable', 'string', 'max:100'],
            'questions.*.difficulty' => ['required', 'in:easy,medium,hard'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['required', 'array', 'list', 'size:4'],
            'questions.*.options.*' => ['required', 'string', 'max:255'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0', 'max:3'],
        ])->validate();

        $seenQuestions = [];
        foreach ($questions as $index => $question) {
            $questionText = $this->normalizeQuestionText((string) $question['question_text']);
            $questionKey = mb_strtolower(preg_replace('/\s+/u', ' ', $questionText) ?? $questionText);
            if ($questionKey === '' || isset($seenQuestions[$questionKey])) {
                throw ValidationException::withMessages([
                    "questions.{$index}.question_text" => 'Pertanyaan tidak boleh kosong atau duplikat.',
                ]);
            }
            $seenQuestions[$questionKey] = true;

            try {
                $this->assertSelfContainedQuestion($questionText, $index);
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([
                    "questions.{$index}.question_text" => $exception->getMessage(),
                ]);
            }

            $questions[$index]['question_text'] = $questionText;

            $normalizedOptions = collect($question['options'])
                ->map(fn (string $option): string => mb_strtolower(trim($option)));
            if ($normalizedOptions->contains('') || $normalizedOptions->unique()->count() !== 4) {
                throw ValidationException::withMessages([
                    "questions.{$index}.options" => 'Setiap soal harus memiliki empat pilihan jawaban unik.',
                ]);
            }
        }

        return array_values($questions);
    }
}
