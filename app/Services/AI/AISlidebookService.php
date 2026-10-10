<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\LearningMaterial;
use App\Models\Slide;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\Document\DocumentService;
use App\Services\SlideLayoutRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AISlidebookService
{
    public function __construct(
        protected AIContentService $aiContentService
    ) {}

    /**
     * Generate structured slidebook from learning material's extracted documents.
     */
    public function generateSlidebookForMaterial(LearningMaterial $material, User $creator, bool $forceRegenerate = false): Slidebook
    {
        $timeout = max(60, (int) config('ai.providers.'.config('ai.provider').'.timeout', 60));
        $lock = Cache::lock('slidebook-generation:'.$material->id, $timeout + 120);
        if (! $lock->get()) {
            throw ValidationException::withMessages(['slidebook' => 'Generation masih berjalan. Silakan tunggu.']);
        }
        try {
            $material->refresh();
            if (! $material->section?->course || $material->section->course->status === 'archived'
                || $material->status === 'processing') {
                throw ValidationException::withMessages(['slidebook' => 'Materi atau kursus tidak tersedia untuk generation.']);
            }
            $latest = $material->slidebooks()->latest('version')->first();
            if (! $forceRegenerate && $latest) {
                return $latest;
            }

            return $this->generate($material, $creator, $forceRegenerate);
        } finally {
            $lock->release();
        }
    }

    private function generate(LearningMaterial $material, User $creator, bool $forceRegenerate): Slidebook
    {
        // Find extracted documents for this material
        $documents = $material->documents()->with('extraction')->get();

        $extractedTexts = [];
        foreach ($documents as $doc) {
            if ($doc->extraction && $doc->extraction->status === 'completed' && ! empty($doc->extraction->content)) {
                $extractedTexts[] = "=== DOKUMEN: {$doc->original_name} ===\n".$doc->extraction->content;
            }
        }

        if (empty($extractedTexts)) {
            // If documents exist without extraction, try parsing now
            $documentService = app(DocumentService::class);
            foreach ($documents as $doc) {
                $extraction = $documentService->extractDocumentContent($doc);
                if ($extraction->status === 'completed' && ! empty($extraction->content)) {
                    $extractedTexts[] = "=== DOKUMEN: {$doc->original_name} ===\n".$extraction->content;
                }
            }
        }

        if (empty($extractedTexts)) {
            // Fallback: Use material content or description if present
            if (! empty($material->content)) {
                $extractedTexts[] = "=== KONTEN MATERI: {$material->title} ===\n".$material->content;
            } elseif (! empty($material->description)) {
                $extractedTexts[] = "=== DESKRIPSI MATERI: {$material->title} ===\n".$material->description;
            } else {
                throw new RuntimeException('Belum ada dokumen yang berhasil diekstrak atau konten materi untuk diproses AI.');
            }
        }

        $sourceContent = implode("\n\n", $extractedTexts);
        $layoutKeys = array_keys((new SlideLayoutRegistry)->getAvailableLayouts());

        // Preserve the teacher's latest design/audience preference when regenerating.
        $latestDesign = $material->slidebooks()->latest('version')->first()?->design_settings ?? [];
        $audience = in_array($latestDesign['audience'] ?? null, ['general', 'smp', 'sma-smk'], true)
            ? $latestDesign['audience']
            : 'sma-smk';
        $designSettings = array_merge(['preset' => 'indigo-dark', 'audience' => $audience], $latestDesign);

        $audienceInstruction = match ($audience) {
            'smp' => <<<'AUDIENCE'
Target audiens: siswa SMP.
- Prioritaskan visual explanation dan satu gagasan utama per slide.
- Gunakan kalimat singkat, konkret, dan mudah dipahami tanpa mengubah makna sumber.
- Untuk daftar, usahakan 3-4 poin inti per slide; pecah topik jika sumber terlalu padat.
- Gunakan process, diagram, comparison, atau example hanya jika benar-benar didukung materi.
- Jangan membuat contoh, analogi, angka, atau fakta baru yang tidak bersumber dari materi.
AUDIENCE,
            'general' => <<<'AUDIENCE'
Target audiens: umum.
- Jaga kepadatan informasi moderat dan struktur slide mudah dipindai.
- Pilih visual explanation saat struktur sumber memang mendukungnya.
AUDIENCE,
            default => <<<'AUDIENCE'
Target audiens: siswa SMA/SMK.
- Gunakan gaya visual modern dan informatif tanpa terasa kekanak-kanakan.
- Untuk daftar, usahakan maksimal 4-5 poin inti per slide dan pertahankan istilah teknis penting.
- Prioritaskan diagram sistem, process, comparison, timeline, dan data visualization ketika didukung sumber.
- Jangan membuat contoh, angka, atau fakta baru yang tidak bersumber dari materi.
AUDIENCE,
        };

        $systemPrompt = <<<'PROMPT'
Anda adalah asisten kurikulum dan presentation designer profesional. Tugas Anda adalah menganalisis dokumen ajar dan menyusun draft "Slidebook" (rangkaian slide presentasi interaktif) dalam Bahasa Indonesia.

Prinsip Wajib (Source Fidelity):
1. Utamakan kebenaran fakta dari dokumen sumber. Dilarang mengarang fakta, angka, statistik, kutipan, atau contoh yang tidak didukung materi.
2. Jika sebuah poin penting disimpulkan secara umum (bukan kutipan langsung), tandai `needs_review: true`.
3. Setiap slide harus memiliki:
   - title: Judul ringkas, menarik, dan informatif (maks 80 karakter).
   - subtitle: Sub-judul atau fokus bahasan.
   - content: Poin-poin materi dalam format teks bersih atau daftar bernomor.
   - summary: Rangkuman intisari slide (1-2 kalimat).
   - layout: Komposisi visual yang paling sesuai dengan isi.
   - order: Urutan integer (mulai dari 1).
   - source_reference: Objek yang menerangkan rujukan asal di dokumen (misal: {"paragraph": 1} atau {"topic": "Pengantar"}).
   - needs_review: Boolean (true jika memerlukan verifikasi guru).
4. Pilih `layout` hanya dari daftar berikut:
   cover, section-divider, concept, definition, key-points, process, timeline, comparison, example, case-study, image-focus, quote, code, diagram, checkpoint, summary, closing, reading.
5. Variasikan layout berdasarkan struktur materi, bukan secara acak:
   - cover untuk pembuka utama.
   - key-points untuk beberapa gagasan sejajar atau tujuan pembelajaran.
   - process/timeline hanya jika sumber benar-benar memuat urutan/tahapan/kronologi.
   - comparison hanya jika sumber membandingkan dua atau lebih hal.
   - code untuk potongan kode; diagram untuk hubungan/struktur yang dapat divisualisasikan tanpa menambah fakta.
   - example/case-study hanya jika sumber menyediakan contoh/kasus.
   - summary untuk rangkuman akhir.
   - reading untuk uraian panjang yang tidak cocok dengan komposisi lain.
6. Hindari monoton: jangan memakai satu layout yang sama untuk semua slide jika struktur isi memungkinkan variasi yang jujur.
7. Jika dokumen memuat angka persentase, pertahankan label dan nilainya secara eksplisit pada content agar sistem dapat membuat visual data otomatis. Jangan menciptakan angka baru.
8. Struktur Slidebook:
   - Slide 1: Cover (Judul materi, ringkasan pengantar), layout `cover`.
   - Slide 2: Tujuan Pembelajaran, umumnya layout `key-points`.
   - Slide 3..N: Topik inti dengan layout sesuai struktur sumber.
   - Slide Terakhir: Rangkuman & Intisari, layout `summary`.
PROMPT;
        $systemPrompt .= "\n\n".$audienceInstruction;

        $schemaDefinition = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'subtitle' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'slides' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'order' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'subtitle' => ['type' => 'string'],
                            'content' => ['type' => 'string'],
                            'summary' => ['type' => 'string'],
                            'layout' => ['type' => 'string', 'enum' => $layoutKeys],
                            'source_reference' => ['type' => 'object'],
                            'needs_review' => ['type' => 'boolean'],
                        ],
                        // Keep layout optional at schema level for provider/backward compatibility.
                        // Missing layouts safely fall back to the deterministic presentation heuristics.
                        'required' => ['order', 'title', 'content'],
                    ],
                ],
            ],
            'required' => ['title', 'slides'],
        ];

        // Process with AI
        $aiOutput = $this->aiContentService->process(
            processType: AIProcessingLog::PROCESS_SLIDEBOOK_GENERATION,
            sourceModel: $material,
            systemPrompt: $systemPrompt,
            userContent: $sourceContent,
            schemaDefinition: $schemaDefinition,
            forceRegenerate: $forceRegenerate
        );

        $parsed = $aiOutput['parsed_data'];
        $validator = Validator::make($parsed, [
            'title' => ['required', 'string', 'max:255'],
            'slides' => ['required', 'array', 'min:1'],
            'slides.*.title' => ['required', 'string', 'max:255'],
            'slides.*.content' => ['required', 'string'],
            'slides.*.layout' => ['sometimes', 'nullable', 'string', Rule::in($layoutKeys)],
        ]);
        if ($validator->fails()) {
            $aiOutput['log']->update(['status' => 'failed', 'error_message' => 'Output Slidebook AI tidak valid.']);
            throw ValidationException::withMessages(['slidebook' => 'Output Slidebook AI tidak valid. Silakan coba kembali.']);
        }

        return DB::transaction(function () use ($material, $creator, $parsed, $designSettings): Slidebook {
            LearningMaterial::whereKey($material->id)->lockForUpdate()->firstOrFail();

            // Archive any existing draft/review revisions to prevent orphaned duplicates.
            // The published version is intentionally left untouched.
            $material->slidebooks()
                ->whereIn('status', [Slidebook::STATUS_DRAFT, Slidebook::STATUS_REVIEW])
                ->update(['status' => Slidebook::STATUS_ARCHIVED]);

            // Determine version
            $latestSlidebook = Slidebook::where('material_id', $material->id)->latest('version')->first();
            $nextVersion = $latestSlidebook ? ($latestSlidebook->version + 1) : 1;

            // Create Slidebook in 'review' status (Human-in-the-Loop)
            $slidebook = Slidebook::create([
                'material_id' => $material->id,
                'title' => $parsed['title'] ?? $material->title,
                'subtitle' => $parsed['subtitle'] ?? 'Slidebook Interaktif Pembelajaran',
                'description' => $parsed['description'] ?? $material->description,
                'status' => Slidebook::STATUS_REVIEW,
                'version' => $nextVersion,
                'design_settings' => $designSettings,
                'created_by' => $creator->id,
            ]);

            $slidesData = $parsed['slides'] ?? [];
            foreach ($slidesData as $index => $slideItem) {
                Slide::create([
                    'slidebook_id' => $slidebook->id,
                    'title' => $slideItem['title'] ?? ('Slide '.($index + 1)),
                    'subtitle' => $slideItem['subtitle'] ?? null,
                    'content' => $slideItem['content'] ?? '',
                    'summary' => $slideItem['summary'] ?? null,
                    'layout' => $slideItem['layout'] ?? null,
                    'order' => (int) ($slideItem['order'] ?? ($index + 1)),
                    'source_reference' => $slideItem['source_reference'] ?? null,
                    'needs_review' => (bool) ($slideItem['needs_review'] ?? false),
                    'status' => 'active',
                ]);
            }

            // Update learning material status if needed
            if ($material->status === LearningMaterial::STATUS_DRAFT) {
                $material->update(['status' => LearningMaterial::STATUS_REVIEW]);
            }

            return $slidebook;
        });
    }
}
