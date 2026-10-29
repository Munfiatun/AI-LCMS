<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Services\MaterialService;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AIMaterialAuthoringService
{
    public function __construct(
        private AIContentService $aiContentService,
        private MaterialService $materialService,
    ) {}

    /**
     * @param array{topic:string,audience:string,depth:string,instructions?:string|null} $settings
     * @return array{draft:array<string,mixed>,provider:string,model:string}
     */
    public function generateDraft(CourseSection $section, array $settings, bool $forceRegenerate = false): array
    {
        $settings = Validator::make($settings, [
            'topic' => ['required', 'string', 'max:255'],
            'audience' => ['required', 'string', 'max:255'],
            'depth' => ['required', 'in:brief,standard,deep'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $depthRule = match ($settings['depth']) {
            'brief' => 'Buat materi ringkas untuk sekitar 10-20 menit belajar, dengan 2-3 bagian inti.',
            'deep' => 'Buat materi mendalam untuk sekitar 45-90 menit belajar, dengan 5-8 bagian inti dan contoh yang cukup.',
            default => 'Buat materi sedang untuk sekitar 25-45 menit belajar, dengan 3-5 bagian inti.',
        };

        $courseTitle = $section->course?->title ?? 'Kursus';
        $systemPrompt = <<<PROMPT
Anda adalah AI Authoring Assistant untuk guru SMK/SMA. Susun draft materi pembelajaran yang jelas, akurat, terstruktur, dan siap direview guru.

Aturan wajib:
1. Gunakan Bahasa Indonesia yang jelas dan sesuai tingkat peserta didik.
2. Materi harus berdiri sendiri dan tidak bergantung pada konteks yang tidak ditampilkan.
3. Jangan mengarang sumber, kutipan, data, standar, atau referensi yang tidak diberikan guru.
4. Jika memberi contoh kode/program, sertakan kode lengkap minimum yang diperlukan agar contoh dapat dipahami.
5. Jangan menulis HTML. Kembalikan teks terstruktur sesuai schema.
6. Learning objectives harus terukur dan relevan dengan topik.
7. Setiap bagian wajib memiliki heading dan content yang substantif. Field example boleh kosong jika memang tidak diperlukan.
8. Summary harus merangkum konsep inti, bukan mengulang kalimat mentah.
9. Practice berisi pertanyaan latihan singkat tanpa kunci jawaban agar dapat dipakai guru sebagai latihan kelas.
10. Draft ini belum dipublikasikan dan wajib ditinjau guru.

{$depthRule}
Konteks kursus: {$courseTitle}.
Bagian kursus: {$section->title}.
Target peserta: {$settings['audience']}.
PROMPT;

        $sectionProperties = [
            'heading' => ['type' => 'string'],
            'content' => ['type' => 'string'],
            'example' => ['type' => 'string'],
        ];

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'duration_minutes' => ['type' => 'integer'],
                'learning_objectives' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'maxItems' => 8,
                    'items' => ['type' => 'string'],
                ],
                'sections' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'maxItems' => 8,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => $sectionProperties,
                        'required' => array_keys($sectionProperties),
                    ],
                ],
                'summary' => ['type' => 'string'],
                'practice' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'maxItems' => 6,
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['title', 'description', 'duration_minutes', 'learning_objectives', 'sections', 'summary', 'practice'],
        ];

        $userContent = json_encode([
            'topic' => trim($settings['topic']),
            'audience' => trim($settings['audience']),
            'depth' => $settings['depth'],
            'instructions' => trim((string) ($settings['instructions'] ?? '')),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $output = $this->aiContentService->process(
            processType: AIProcessingLog::PROCESS_MATERIAL_AUTHORING,
            sourceModel: $section,
            systemPrompt: $systemPrompt,
            userContent: $userContent,
            schemaDefinition: $schema,
            maxOutputTokens: $settings['depth'] === 'deep' ? 10000 : 7000,
            forceRegenerate: $forceRegenerate,
        );

        return [
            'draft' => $this->normalizeDraft($output['parsed_data']),
            'provider' => (string) $output['log']->provider,
            'model' => (string) $output['log']->model,
        ];
    }

    /** @param array<string,mixed> $draft */
    public function persistReviewedDraft(CourseSection $section, array $draft): LearningMaterial
    {
        $draft = $this->validateReviewedDraft($draft);

        return $this->materialService->createMaterial($section, [
            'title' => trim($draft['title']),
            'description' => trim((string) $draft['description']),
            'content' => $this->renderSafeHtml($draft),
            'duration_minutes' => (int) $draft['duration_minutes'],
        ]);
    }

    /** @param array<string,mixed> $draft */
    private function normalizeDraft(array $draft): array
    {
        $validated = Validator::make($draft, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:180'],
            'learning_objectives' => ['required', 'array', 'list', 'min:2', 'max:8'],
            'learning_objectives.*' => ['required', 'string', 'max:300'],
            'sections' => ['required', 'array', 'list', 'min:2', 'max:8'],
            'sections.*.heading' => ['required', 'string', 'max:200'],
            'sections.*.content' => ['required', 'string', 'max:6000'],
            'sections.*.example' => ['nullable', 'string', 'max:4000'],
            'summary' => ['required', 'string', 'max:3000'],
            'practice' => ['required', 'array', 'list', 'min:2', 'max:6'],
            'practice.*' => ['required', 'string', 'max:500'],
        ])->validate();

        foreach ($validated['sections'] as $index => $section) {
            if (trim(strip_tags($section['content'])) === '') {
                throw new RuntimeException('AI menghasilkan bagian materi kosong pada bagian '.($index + 1).'.');
            }
        }

        return $validated;
    }

    /** @param array<string,mixed> $draft */
    private function validateReviewedDraft(array $draft): array
    {
        return Validator::make($draft, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'learning_objectives' => ['required', 'array', 'list', 'min:1', 'max:8'],
            'learning_objectives.*' => ['required', 'string', 'max:300'],
            'sections' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'sections.*.heading' => ['required', 'string', 'max:200'],
            'sections.*.content' => ['required', 'string', 'max:8000'],
            'sections.*.example' => ['nullable', 'string', 'max:5000'],
            'summary' => ['required', 'string', 'max:4000'],
            'practice' => ['nullable', 'array', 'list', 'max:8'],
            'practice.*' => ['required', 'string', 'max:700'],
        ])->validate();
    }

    /** @param array<string,mixed> $draft */
    private function renderSafeHtml(array $draft): string
    {
        $html = '<section><h2>Tujuan Pembelajaran</h2><ol>';
        foreach ($draft['learning_objectives'] as $objective) {
            $html .= '<li>'.e($objective).'</li>';
        }
        $html .= '</ol></section>';

        foreach ($draft['sections'] as $section) {
            $html .= '<section><h2>'.e($section['heading']).'</h2>';
            $html .= '<p>'.nl2br(e($section['content'])).'</p>';
            if (trim((string) ($section['example'] ?? '')) !== '') {
                $html .= '<h3>Contoh</h3><pre><code>'.e($section['example']).'</code></pre>';
            }
            $html .= '</section>';
        }

        $html .= '<section><h2>Rangkuman</h2><p>'.nl2br(e($draft['summary'])).'</p></section>';

        $practice = array_values(array_filter($draft['practice'] ?? [], fn ($item) => trim((string) $item) !== ''));
        if ($practice !== []) {
            $html .= '<section><h2>Latihan</h2><ol>';
            foreach ($practice as $item) {
                $html .= '<li>'.e($item).'</li>';
            }
            $html .= '</ol></section>';
        }

        return $html;
    }
}
