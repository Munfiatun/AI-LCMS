<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\CourseSection;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AIMaterialRevisionService
{
    public function __construct(private AIContentService $aiContentService) {}

    /**
     * @param  array<string,mixed>  $material
     * @return array{material:array<string,mixed>,provider:string,model:string}
     */
    public function reviseDraft(CourseSection $section, array $material, string $instruction): array
    {
        $instruction = trim($instruction);
        $material = $this->validateDraft($material);

        Validator::make(['revision_instruction' => $instruction], [
            'revision_instruction' => ['required', 'string', 'min:3', 'max:1500'],
        ])->validate();

        $courseTitle = $section->course?->title ?? 'Kursus';
        $systemPrompt = <<<PROMPT
Anda adalah AI Revision Assistant untuk guru SMK/SMA. Revisi draft materi pembelajaran berdasarkan instruksi guru.

Aturan wajib:
1. Gunakan DRAFT SAAT INI sebagai batas fakta. Jangan menambahkan sumber, referensi, data, standar, konsep, atau klaim baru yang tidak dapat diturunkan dari draft.
2. Terapkan hanya perubahan yang diminta guru dan pertahankan bagian lain semaksimal mungkin.
3. Kembalikan seluruh materi lengkap sesuai schema, bukan hanya bagian yang berubah.
4. Materi harus tetap jelas, berdiri sendiri, dan sesuai untuk peserta didik.
5. Jika merevisi contoh kode, sertakan kode minimum yang lengkap dan dapat dipahami.
6. Jangan menulis HTML. Gunakan teks biasa terstruktur.
7. Learning objectives, sections, summary, dan practice harus tetap konsisten setelah revisi.
8. Jangan menyimpan atau mempublikasikan apa pun. Hasil tetap draft untuk direview guru.

Konteks kursus: {$courseTitle}.
Bagian kursus: {$section->title}.
PROMPT;

        $schema = $this->materialSchema();
        $userContent = json_encode([
            'instruksi_revisi' => $instruction,
            'draft_saat_ini' => $material,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $output = $this->aiContentService->process(
            processType: AIProcessingLog::PROCESS_MATERIAL_REVISION,
            sourceModel: $section,
            systemPrompt: $systemPrompt,
            userContent: $userContent,
            schemaDefinition: $schema,
            maxOutputTokens: 9000,
            forceRegenerate: true,
        );

        return [
            'material' => $this->normalizeDraft($output['parsed_data']),
            'provider' => (string) $output['log']->provider,
            'model' => (string) $output['log']->model,
        ];
    }

    /** @return array<string,mixed> */
    private function materialSchema(): array
    {
        $sectionProperties = [
            'heading' => ['type' => 'string'],
            'content' => ['type' => 'string'],
            'example' => ['type' => 'string'],
        ];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'duration_minutes' => ['type' => 'integer'],
                'learning_objectives' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'maxItems' => 8,
                    'items' => ['type' => 'string'],
                ],
                'sections' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'maxItems' => 10,
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
                    'minItems' => 0,
                    'maxItems' => 8,
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['title', 'description', 'duration_minutes', 'learning_objectives', 'sections', 'summary', 'practice'],
        ];
    }

    /** @param array<string,mixed> $draft */
    private function validateDraft(array $draft): array
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
    private function normalizeDraft(array $draft): array
    {
        $validated = $this->validateDraft($draft);

        foreach ($validated['sections'] as $index => $contentSection) {
            if (trim(strip_tags((string) $contentSection['content'])) === '') {
                throw new RuntimeException('AI menghasilkan bagian materi kosong pada bagian '.($index + 1).' saat revisi.');
            }
        }

        $validated['practice'] = array_values($validated['practice'] ?? []);

        return $validated;
    }
}
