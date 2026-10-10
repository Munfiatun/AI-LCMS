<?php

namespace App\Services\AI;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Slidebook;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AIQuizService
{
    public const MIN_QUESTIONS = 2;

    public const MAX_QUESTIONS = 20;

    public const QUESTION_COUNT_MESSAGE = 'Jumlah soal harus antara 2 sampai 20.';

    /** Estimated output tokens per generated question (question, options, index, short explanation). */
    private const TOKENS_PER_QUESTION = 250;

    /** Reserve for the model's reasoning step and JSON overhead. */
    private const TOKEN_RESERVE = 3072;

    private QuizQuestionQualityGuard $qualityGuard;

    public function __construct(
        private AIContentService $aiContentService,
        ?QuizQuestionQualityGuard $qualityGuard = null,
    ) {
        $this->qualityGuard = $qualityGuard ?? new QuizQuestionQualityGuard;
    }

    /** @param array{total_questions: int, difficulty: string, type: string, custom_instructions?: string} $settings */
    public function generate(Slidebook $slidebook, User $teacher, array $settings): Quiz
    {
        Validator::make($settings, [
            'total_questions' => ['required', 'integer', 'min:'.self::MIN_QUESTIONS, 'max:'.self::MAX_QUESTIONS],
        ], ['total_questions.*' => self::QUESTION_COUNT_MESSAGE])->validate();
        $questionCount = (int) $settings['total_questions'];

        $slides = $slidebook->slides()->get()->map(fn ($slide): array => [
            'number' => $slide->order,
            'text' => $this->plainText([$slide->title, $slide->subtitle, $slide->content, $slide->summary]),
        ])->filter(fn (array $slide): bool => $slide['text'] !== '')->values()->all();

        if ($slides === []) {
            throw ValidationException::withMessages(['slidebook_id' => 'Materi Slidebook belum cukup untuk membuat Quiz.']);
        }

        if (config('ai.provider') === 'mock') {
            throw ValidationException::withMessages(['quiz' => 'Generator Quiz memerlukan provider AI (Groq, OpenAI, atau Gemini) yang telah dikonfigurasi.']);
        }

        $generationId = (string) Str::uuid();
        $teacherPrompt = trim((string) ($settings['custom_instructions'] ?? ''));
        $configExtra = trim((string) Config::get('ai.quiz_prompt_extra', ''));
        $optionCount = $settings['type'] === 'true_false' ? 2 : 4;
        $optionRule = $settings['type'] === 'true_false'
            ? 'Tipe benar/salah: options berisi tepat 2 pilihan ["Benar", "Salah"] dalam bahasa materi.'
            : 'Tipe pilihan ganda: options berisi tepat 4 pilihan unik, homogen, masuk akal, dan hanya satu yang benar.';

        $audience = $slidebook->design_settings['audience'] ?? 'sma-smk';
        $audienceRule = match ($audience) {
            'smp' => 'Target siswa SMP: gunakan kalimat konkret dan sederhana. Pertahankan istilah penting dari materi, tetapi jangan membuat kesulitan berasal dari bahasa yang rumit.',
            'general' => 'Target umum: gunakan bahasa baku, ringkas, dan dapat dipahami tanpa konteks tambahan.',
            default => 'Target siswa SMA/SMK: gunakan bahasa baku dan jelas, pertahankan istilah teknis yang relevan, dan ukur pemahaman konsep bukan kemampuan menebak redaksi.',
        };

        $difficultyRule = match ($settings['difficulty']) {
            'easy' => 'Kesulitan mudah: fokus pada mengenali, mengingat, atau mengidentifikasi satu konsep/fakta yang dinyatakan jelas dalam sumber.',
            'medium' => 'Kesulitan sedang: fokus pada hubungan, fungsi, klasifikasi, atau penerapan sederhana yang dapat dijawab langsung dari konsep dalam sumber.',
            'hard' => 'Kesulitan sulit: fokus pada analisis, perbandingan, sebab-akibat, atau inferensi yang sepenuhnya dapat diturunkan dari fakta dalam sumber tanpa menambah fakta baru.',
            default => 'Kesulitan campuran: variasikan easy, medium, dan hard secara wajar. Bedakan tingkat kognitifnya, bukan hanya panjang kalimat.',
        };

        $prompt = <<<SYSTEM
Anda adalah penulis butir soal pendidikan yang mengikuti kaidah assessment yang baik.
Buat quiz hanya berdasarkan materi Slidebook yang diberikan. Jangan menambah fakta di luar materi.
Ikuti instruksi guru hanya jika tidak bertentangan dengan source fidelity dan aturan kualitas soal berikut.

ATURAN WAJIB PENULISAN SOAL:
1. Stem harus dapat dipahami dan dijawab secara mandiri tanpa melihat nomor slide, dokumen, atau lokasi sumber.
2. DILARANG menulis frasa seperti "menurut slide...", "berdasarkan slide...", "pada slide...", "menurut materi...", "materi/teks di atas", atau padanan bahasa Inggrisnya. Nomor slide hanya boleh disimpan pada source_slide_number sebagai metadata provenance untuk guru.
3. Tanyakan satu masalah utama secara jelas. Hindari stem yang kabur, terlalu umum, atau bergantung pada informasi yang tidak disebutkan dalam soal.
4. Pastikan tepat satu pilihan dapat dipertahankan sebagai jawaban benar berdasarkan sumber. Jika dua atau lebih pilihan dapat dianggap benar, ubah stem atau pilihan jawaban.
5. Hindari bentuk negatif seperti "tidak", "bukan", atau "kecuali" jika konsep dapat diuji dengan kalimat positif. Jika bentuk negatif benar-benar diperlukan, pastikan tidak menimbulkan lebih dari satu jawaban masuk akal.
6. Untuk pilihan ganda, buat distraktor yang masuk akal, berada dalam kategori konsep yang sama, memiliki struktur gramatikal sejajar, dan tidak memberi petunjuk ke jawaban benar karena panjang atau gaya bahasa.
7. DILARANG menggunakan "semua jawaban di atas", "tidak ada jawaban di atas", all of the above, atau none of the above.
8. Jangan membuat soal trivia tentang posisi materi, nomor slide, urutan tampilan, judul halaman, atau redaksi sumber. Uji konsep pembelajaran.
9. Jangan membuat soal duplikat atau dua soal yang hanya memparafrase konsep dan jawaban yang sama.
10. explanation maksimal 2 kalimat dan menjelaskan alasan akademik jawaban benar. Jangan menyebut nomor slide di explanation.

{$audienceRule}
{$difficultyRule}
{$optionRule}
correct_option adalah indeks (mulai 0) dari jawaban benar di options. Tepat satu jawaban benar.
source_slide_number adalah nomor slide internal yang paling langsung mendukung jawaban dan TIDAK boleh disebut dalam question_text/options/explanation.
Generate exactly {$questionCount} quiz questions. Jumlah ini wajib diikuti meskipun instruksi guru menyebut jumlah lain.
SYSTEM;
        $prompt .= ($configExtra !== '' ? "\n".$configExtra : '')
            ."\n\nINSTRUKSI GURU:\n".$teacherPrompt
            ."\n\nTingkat kesulitan: ".$settings['difficulty'];

        $source = [
            'generation_id' => $generationId,
            'settings' => [
                'total_questions' => $questionCount,
                'difficulty' => $settings['difficulty'],
                'type' => $settings['type'],
                'audience' => $audience,
            ],
            'title' => $slidebook->title,
            'slides' => $slides,
        ];

        $schema = $this->schema($questionCount, $optionCount);
        $outputBudget = max(4096, $questionCount * self::TOKENS_PER_QUESTION + self::TOKEN_RESERVE);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $requestSource = ['attempt' => $attempt, ...$source];
            if ($attempt > 1) {
                $requestSource['retry_instruction'] = 'Buat ulang SELURUH soal. Periksa khusus agar stem berdiri sendiri, tidak menyebut slide/materi sumber, hanya memiliki satu jawaban benar yang tidak ambigu, dan semua distraktor masuk akal.';
            }

            $output = $this->aiContentService->process(
                processType: 'quiz_generation',
                sourceModel: $slidebook,
                systemPrompt: $prompt,
                userContent: json_encode($requestSource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                schemaDefinition: $schema,
                maxOutputTokens: $outputBudget,
            );

            try {
                $questions = $this->validateOutput($output['parsed_data'], $settings, $optionCount, $slides);
                break;
            } catch (ValidationException $exception) {
                if ($attempt === 2) {
                    throw ValidationException::withMessages(['quiz' => 'Output AI tidak valid atau belum memenuhi standar kualitas soal. Silakan coba lagi.']);
                }
            }
        }

        return DB::transaction(function () use ($slidebook, $teacher, $settings, $questions, $teacherPrompt, $slides): Quiz {
            $course = $slidebook->material->section->course;
            $bank = QuestionBank::create([
                'instructor_id' => $teacher->id,
                'course_id' => $course->id,
                'title' => mb_substr('Quiz AI: '.$slidebook->title, 0, 255),
                'description' => "Sumber Slidebook #{$slidebook->id}: {$slidebook->title}",
                'status' => QuestionBank::STATUS_ACTIVE,
            ]);
            $quiz = $course->quizzes()->create([
                'section_id' => $slidebook->material->section_id,
                'title' => mb_substr('Quiz: '.$slidebook->title, 0, 255),
                'description' => mb_substr('AI Instruksi: '.$teacherPrompt, 0, 65535),
                'total_questions' => $settings['total_questions'],
                'status' => 'draft',
                'published_at' => null,
            ]);
            foreach ($questions as $index => $data) {
                $sourceSlideNumber = (int) $data['source_slide_number'];
                $question = $bank->questions()->create([
                    'question_text' => trim($data['question_text']),
                    'type' => $settings['type'],
                    'difficulty' => $data['difficulty'],
                    'topic' => 'Source: Slide '.$sourceSlideNumber,
                    'source_slide_number' => $sourceSlideNumber,
                    'source_excerpt' => $this->sourceExcerpt($slides, $sourceSlideNumber),
                    'explanation' => trim($data['explanation']),
                    'points' => 10,
                    'order' => $index + 1,
                    'needs_review' => true,
                    'answer_source' => Question::SOURCE_INFERRED,
                    'status' => Question::STATUS_REVIEW,
                ]);
                foreach ($data['options'] as $optionIndex => $optionText) {
                    $question->options()->create([
                        'option_text' => trim($optionText),
                        'is_correct' => $optionIndex === $data['correct_option'],
                        'order' => $optionIndex + 1,
                    ]);
                }
                $quiz->quizQuestions()->create(['question_id' => $question->id, 'points' => 10, 'order' => $index + 1]);
            }

            return $quiz;
        });
    }

    /**
     * Strip markup so only learning text is sent to the AI.
     *
     * @param  list<string|null>  $parts
     */
    private function plainText(array $parts): string
    {
        $text = html_entity_decode(strip_tags(implode("\n", array_filter($parts, fn (?string $part): bool => filled($part)))), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace(["/[ \t]+/u", "/\n{3,}/u"], [' ', "\n\n"], $text) ?? $text);
    }

    /**
     * Keep an immutable, teacher-visible snapshot of the slide text that AI cited.
     * This is derived by the application rather than trusted from model output.
     *
     * @param  list<array{number: int, text: string}>  $slides
     */
    private function sourceExcerpt(array $slides, int $slideNumber): string
    {
        foreach ($slides as $slide) {
            if ((int) $slide['number'] === $slideNumber) {
                return Str::limit(trim($slide['text']), 320, '…');
            }
        }

        throw ValidationException::withMessages(['quiz' => 'Referensi sumber AI tidak ditemukan pada Slidebook.']);
    }

    /**
     * @param  array<string, mixed>  $output
     * @param  array{total_questions: int, difficulty: string, type: string}  $settings
     * @param  list<array{number: int, text: string}>  $slides
     * @return list<array{question_text: string, options: list<string>, correct_option: int, difficulty: string, explanation: string, source_slide_number: int}>
     */
    private function validateOutput(array $output, array $settings, int $optionCount, array $slides): array
    {
        Validator::make($output, [
            'questions' => ['required', 'array', 'list', 'size:'.$settings['total_questions']],
            'questions.*' => ['required', 'array'],
            'questions.*.question_text' => ['required', 'string', 'max:500'],
            'questions.*.options' => ['required', 'array', 'list', 'size:'.$optionCount],
            'questions.*.options.*' => ['required', 'string', 'max:255'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0', 'max:'.($optionCount - 1)],
            'questions.*.difficulty' => ['required', 'in:'.($settings['difficulty'] === 'mixed' ? 'easy,medium,hard' : $settings['difficulty'])],
            'questions.*.explanation' => ['required', 'string', 'max:1000'],
            'questions.*.source_slide_number' => ['required', 'integer', 'in:'.implode(',', array_column($slides, 'number'))],
        ])->validate();

        $seen = [];
        foreach ($output['questions'] as $question) {
            $text = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $question['question_text'])));
            $options = collect($question['options'])->map(fn (string $option): string => mb_strtolower(trim($option)));
            if (isset($seen[$text]) || ! is_int($question['correct_option']) || ! is_int($question['source_slide_number'])
                || $options->unique()->count() !== $optionCount) {
                throw ValidationException::withMessages(['quiz' => 'Output AI tidak valid.']);
            }

            $this->qualityGuard->assertAcceptable($question['question_text'], $question['options'], $settings['type']);
            if ($this->qualityGuard->containsSourceFraming($question['explanation'])) {
                throw ValidationException::withMessages(['quiz' => 'Pembahasan AI tidak boleh bergantung pada referensi slide.']);
            }

            $seen[$text] = true;
        }

        return $output['questions'];
    }

    /** @return array<string, mixed> */
    private function schema(int $questionCount, int $optionCount): array
    {
        $properties = [
            'question_text' => ['type' => 'string'],
            'options' => ['type' => 'array', 'minItems' => $optionCount, 'maxItems' => $optionCount, 'items' => ['type' => 'string']],
            'correct_option' => ['type' => 'integer'],
            'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
            'explanation' => ['type' => 'string'],
            'source_slide_number' => ['type' => 'integer'],
        ];

        return ['title' => 'quiz_generation', 'type' => 'object', 'additionalProperties' => false,
            'properties' => ['questions' => ['type' => 'array', 'minItems' => $questionCount, 'maxItems' => $questionCount, 'items' => [
                'type' => 'object', 'additionalProperties' => false, 'properties' => $properties, 'required' => array_keys($properties),
            ]]], 'required' => ['questions']];
    }
}
