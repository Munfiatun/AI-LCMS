<?php

namespace App\Services;

use App\Models\AIProcessingLog;
use App\Models\LearningMaterial;
use App\Models\Slide;
use App\Models\Slidebook;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SlidebookService
{
    private SlidebookQualityService $qualityService;

    public function __construct(
        private MaterialService $materialService,
        ?SlidebookQualityService $qualityService = null,
    ) {
        $this->qualityService = $qualityService ?? new SlidebookQualityService;
    }

    /** @return array<string, mixed> */
    public function qualityReport(Slidebook $slidebook): array
    {
        return $this->qualityService->evaluate($slidebook);
    }

    /** @return list<string> */
    public function reviewErrors(Slidebook $slidebook): array
    {
        $material = $slidebook->material;
        if (! $material || ! in_array($slidebook->status, ['draft', 'review'], true)) {
            return ['Hanya versi draft/review yang dapat disetujui.'];
        }
        if ($slidebook->material->slidebooks()->where('status', 'published')->where('version', '>=', $slidebook->version)->exists()) {
            return ['Versi ini lebih lama daripada versi yang sudah terbit. Buat versi baru dari Slidebook terbaru.'];
        }
        if (AIProcessingLog::where('source_type', LearningMaterial::class)->where('source_id', $material->id)
            ->where('process_type', AIProcessingLog::PROCESS_SLIDEBOOK_GENERATION)->whereIn('status', ['pending', 'processing'])->exists()) {
            return ['Pembuatan Slidebook masih berjalan. Tunggu hingga proses selesai.'];
        }
        if ($errors = $this->materialService->publicationErrors($material)) {
            return $errors;
        }
        $slides = $slidebook->slides()->get();
        if (trim($slidebook->title) === '' || $slides->isEmpty() || $slides->contains(fn (Slide $slide): bool => $slide->status !== 'active' || trim($slide->title) === '' || trim(strip_tags($slide->content)) === ''
        )) {
            return ['Slidebook harus memiliki judul dan minimal satu slide aktif dengan konten valid.'];
        }

        $qualityReport = $this->qualityReport($slidebook);
        if ($qualityReport['blocking_messages'] !== []) {
            return collect($qualityReport['blocking_messages'])
                ->map(fn (string $message): string => 'Quality Check: '.$message)
                ->values()
                ->all();
        }

        return [];
    }

    /** @return list<string> */
    public function publicationErrors(Slidebook $slidebook): array
    {
        if ($errors = $this->reviewErrors($slidebook)) {
            return $errors;
        }
        if ($slidebook->status !== 'draft' || ! $slidebook->approved_by || $slidebook->slides()->where('needs_review', true)->exists()) {
            return ['Tinjau dan setujui Slidebook sebelum publikasi.'];
        }

        return [];
    }

    public function approve(Slidebook $slidebook, User $reviewer): void
    {
        $this->mutate($slidebook, function (Slidebook $locked) use ($reviewer): void {
            if ($errors = $this->reviewErrors($locked)) {
                throw ValidationException::withMessages(['slidebook' => $errors]);
            }
            $locked->slides()->update(['needs_review' => false]);
            $locked->update(['status' => 'draft', 'approved_by' => $reviewer->id]);
        }, invalidateApproval: false);
    }

    public function publish(Slidebook $slidebook): void
    {
        $this->mutate($slidebook, function (Slidebook $locked): void {
            if ($errors = $this->publicationErrors($locked)) {
                throw ValidationException::withMessages(['slidebook' => $errors]);
            }
            $locked->material->slidebooks()->where('status', 'published')->update(['status' => 'archived']);
            $locked->update(['status' => 'published', 'published_at' => now()]);
            $this->materialService->publishMaterial($locked->material);
        }, invalidateApproval: false);
    }

    public function createRevision(Slidebook $published, User $creator): Slidebook
    {
        return DB::transaction(function () use ($published, $creator): Slidebook {
            $material = LearningMaterial::whereKey($published->material_id)->lockForUpdate()->firstOrFail();
            $published->refresh();
            if (! $published->isPublished()) {
                throw ValidationException::withMessages(['slidebook' => 'Buat versi baru dari Slidebook yang sudah terbit.']);
            }
            $existing = $material->slidebooks()->whereIn('status', ['draft', 'review'])->where('version', '>', $published->version)->latest('version')->first();
            if ($existing) {
                return $existing;
            }
            $revision = $published->replicate(['approved_by', 'published_at']);
            $revision->fill(['status' => 'draft', 'version' => $material->slidebooks()->max('version') + 1, 'created_by' => $creator->id]);
            $revision->save();
            foreach ($published->slides()->get() as $slide) {
                $copy = $slide->replicate();
                $copy->slidebook_id = $revision->id;
                $copy->save();
            }

            return $revision;
        });
    }

    /** @param array<string, mixed> $data */
    public function addSlide(Slidebook $slidebook, array $data): void
    {
        $this->mutate($slidebook, function (Slidebook $locked) use ($data): void {
            $locked->slides()->create([...$data, 'order' => ($locked->slides()->max('order') ?? 0) + 1,
                'source_reference' => ['manual' => true], 'needs_review' => false, 'status' => 'active']);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateSlide(Slide $slide, array $data): void
    {
        $this->mutate($slide->slidebook, fn () => $slide->update($data));
    }

    public function deleteSlide(Slide $slide): void
    {
        $this->mutate($slide->slidebook, function (Slidebook $locked) use ($slide): void {
            $slide->delete();
            $locked->slides()->where('order', '>', $slide->order)->decrement('order');
        });
    }

    /** @param list<int> $slideIds */
    public function reorder(Slidebook $slidebook, array $slideIds): void
    {
        $this->mutate($slidebook, function (Slidebook $locked) use ($slideIds): void {
            $expectedIds = $locked->slides()->pluck('id')->sort()->values()->all();
            $actualIds = collect($slideIds)->map(fn ($id): int => (int) $id)->sort()->values()->all();
            if ($expectedIds !== $actualIds) {
                throw ValidationException::withMessages(['slide_ids' => 'Urutan harus berisi semua slide pada versi ini, tanpa duplikat.']);
            }
            foreach ($slideIds as $index => $slideId) {
                $locked->slides()->whereKey($slideId)->update(['order' => $index + 1]);
            }
        });
    }

    private function mutate(Slidebook $slidebook, Closure $callback, bool $invalidateApproval = true): void
    {
        DB::transaction(function () use ($slidebook, $callback, $invalidateApproval): void {
            LearningMaterial::whereKey($slidebook->material_id)->lockForUpdate()->firstOrFail();
            $slidebook->refresh();
            if (! in_array($slidebook->status, ['draft', 'review'], true)) {
                throw ValidationException::withMessages(['slidebook' => 'Versi yang sudah terbit/diarsipkan tidak dapat diubah. Buat versi baru terlebih dahulu.']);
            }
            $callback($slidebook);
            if ($invalidateApproval) {
                $slidebook->update(['approved_by' => null, 'status' => 'review']);
            }
        });
    }
}
