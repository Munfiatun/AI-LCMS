<?php

namespace App\Services;

use App\Models\Slide;
use App\Models\Slidebook;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SlidebookQualityService
{
    private const CRITICAL_DEDUCTION = 12;

    private const WARNING_DEDUCTION = 3;

    /**
     * @return array{
     *   score:int,
     *   status:string,
     *   audience:string,
     *   summary:array<string,int|float>,
     *   issues:list<array<string,mixed>>,
     *   blocking_messages:list<string>
     * }
     */
    public function evaluate(Slidebook $slidebook): array
    {
        $slidebook->loadMissing(['slides', 'material.documents.extraction']);

        $audience = $this->audience($slidebook);
        $slides = $slidebook->slides->where('status', 'active')->sortBy('order')->values();
        $sourceText = $this->sourceText($slidebook);
        $sourceTokens = $this->meaningfulTokens($sourceText);
        $issues = [];

        foreach ($slides as $slide) {
            $issues = [...$issues, ...$this->slideIssues($slide, $audience, $sourceTokens)];
        }

        $issues = [...$issues, ...$this->globalIssues($slides, $sourceText)];

        $criticalCount = collect($issues)->where('severity', 'critical')->count();
        $warningCount = collect($issues)->where('severity', 'warning')->count();
        $score = max(0, 100
            - ($criticalCount * self::CRITICAL_DEDUCTION)
            - ($warningCount * self::WARNING_DEDUCTION));

        $sourceCovered = $slides->filter(fn (Slide $slide): bool => $this->hasSourceReference($slide))->count();
        $uniqueLayouts = $slides->map(fn (Slide $slide): string => $this->layout($slide))->unique()->count();
        $needsReview = $slides->where('needs_review', true)->count();

        $status = $criticalCount > 0
            ? 'blocked'
            : (($warningCount > 0 || $score < 90) ? 'needs_attention' : 'good');

        $blockingMessages = collect($issues)
            ->where('severity', 'critical')
            ->pluck('message')
            ->unique()
            ->take(6)
            ->values()
            ->all();

        return [
            'score' => $score,
            'status' => $status,
            'audience' => $audience,
            'summary' => [
                'slides' => $slides->count(),
                'critical_count' => $criticalCount,
                'warning_count' => $warningCount,
                'needs_review_count' => $needsReview,
                'source_coverage_percent' => $slides->isEmpty() ? 0 : (int) round(($sourceCovered / $slides->count()) * 100),
                'unique_layouts' => $uniqueLayouts,
            ],
            'issues' => array_values($issues),
            'blocking_messages' => $blockingMessages,
        ];
    }

    /**
     * @param  Collection<int, string>  $sourceTokens
     * @return list<array<string,mixed>>
     */
    private function slideIssues(Slide $slide, string $audience, Collection $sourceTokens): array
    {
        $issues = [];
        $plain = $this->plainText($slide->content ?? '');
        $title = trim((string) $slide->title);
        $wordCount = str_word_count($plain);
        $pointCount = $this->pointCount((string) $slide->content);
        $order = (int) $slide->order;
        $label = 'Slide '.$order;

        if ($title === '') {
            $issues[] = $this->issue('critical', 'empty_title', $label.' belum memiliki judul.', $slide);
        } elseif (mb_strlen($title) > 110) {
            $issues[] = $this->issue('critical', 'title_too_long', $label.' memiliki judul terlalu panjang ('.mb_strlen($title).' karakter).', $slide);
        } elseif (mb_strlen($title) > 80) {
            $issues[] = $this->issue('warning', 'title_long', $label.' memiliki judul lebih dari 80 karakter.', $slide);
        }

        if ($plain === '') {
            $issues[] = $this->issue('critical', 'empty_content', $label.' belum memiliki isi pembelajaran.', $slide);

            return $issues;
        }

        [$warningWords, $criticalWords, $warningPoints, $criticalPoints] = match ($audience) {
            'smp' => [90, 140, 4, 7],
            'general' => [150, 220, 6, 9],
            default => [130, 190, 5, 8],
        };

        if ($wordCount > $criticalWords) {
            $issues[] = $this->issue('critical', 'text_density_critical', $label.' terlalu padat untuk target '.$this->audienceLabel($audience).' ('.$wordCount.' kata). Pecah menjadi beberapa slide.', $slide);
        } elseif ($wordCount > $warningWords) {
            $issues[] = $this->issue('warning', 'text_density_high', $label.' cukup padat ('.$wordCount.' kata). Pertimbangkan meringkas atau membaginya.', $slide);
        }

        if ($pointCount > $criticalPoints) {
            $issues[] = $this->issue('critical', 'too_many_points_critical', $label.' memiliki '.$pointCount.' poin. Kurangi atau pecah menjadi beberapa slide.', $slide);
        } elseif ($pointCount > $warningPoints) {
            $issues[] = $this->issue('warning', 'too_many_points', $label.' memiliki '.$pointCount.' poin; target '.$this->audienceLabel($audience).' sebaiknya lebih ringkas.', $slide);
        }

        if ($audience === 'smp' && $this->averageSentenceWords($plain) > 24) {
            $issues[] = $this->issue('warning', 'sentence_complexity', $label.' menggunakan kalimat yang cukup panjang untuk target SMP.', $slide);
        }

        if (! $this->hasSourceReference($slide)) {
            $issues[] = $this->issue('warning', 'missing_source_reference', $label.' belum memiliki rujukan sumber yang dapat diperiksa guru.', $slide);
        } elseif (! ($slide->source_reference['manual'] ?? false) && $sourceTokens->isNotEmpty()) {
            $slideTokens = $this->meaningfulTokens($title.' '.$plain);
            if ($slideTokens->count() >= 8) {
                $matched = $slideTokens->intersect($sourceTokens)->count();
                $overlap = $matched / max(1, $slideTokens->count());
                if ($overlap < 0.08) {
                    $issues[] = $this->issue('warning', 'low_source_overlap', $label.' memiliki keterkaitan istilah yang rendah dengan materi sumber. Periksa kembali fidelitas isinya.', $slide);
                }
            }
        }

        if ($slide->needs_review) {
            $issues[] = $this->issue('warning', 'needs_teacher_review', $label.' masih ditandai Perlu Dicek Guru.', $slide);
        }

        $issues = [...$issues, ...$this->visualOpportunityIssues($slide, $plain)];

        return $issues;
    }

    /**
     * @param  Collection<int, Slide>  $slides
     * @return list<array<string,mixed>>
     */
    private function globalIssues(Collection $slides, string $sourceText): array
    {
        $issues = [];
        $count = $slides->count();

        if ($count === 0) {
            return [$this->issue('critical', 'no_slides', 'Slidebook belum memiliki slide aktif.')];
        }

        $layouts = $slides->map(fn (Slide $slide): string => $this->layout($slide));
        $uniqueLayouts = $layouts->unique()->count();

        if ($count >= 4 && $uniqueLayouts === 1) {
            $issues[] = $this->issue('warning', 'layout_monotony', 'Semua slide menggunakan tampilan yang sama. Variasikan Tampilan Slide sesuai struktur materi.');
        } elseif ($count >= 6 && ($uniqueLayouts / $count) < 0.30) {
            $issues[] = $this->issue('warning', 'layout_low_variety', 'Variasi Tampilan Slide masih rendah untuk '.$count.' slide.');
        }

        $sourceCoverage = $slides->filter(fn (Slide $slide): bool => $this->hasSourceReference($slide))->count() / max(1, $count);
        if ($sourceCoverage < 0.50) {
            $issues[] = $this->issue('critical', 'source_coverage_critical', 'Kurang dari separuh slide memiliki Sumber Materi yang dapat ditelusuri.');
        } elseif ($sourceCoverage < 0.80) {
            $issues[] = $this->issue('warning', 'source_coverage_low', 'Sebagian slide belum memiliki Sumber Materi yang dapat ditelusuri.');
        }

        if (trim($sourceText) === '' && $slides->contains(fn (Slide $slide): bool => ! ($slide->source_reference['manual'] ?? false))) {
            $issues[] = $this->issue('warning', 'source_text_unavailable', 'Teks sumber tidak tersedia untuk pemeriksaan fidelitas otomatis.');
        }

        return $issues;
    }

    /** @return list<array<string,mixed>> */
    private function visualOpportunityIssues(Slide $slide, string $plain): array
    {
        $layout = $this->layout($slide);
        $issues = [];

        if (preg_match('/\b(langkah|tahap|alur|proses|urutan|pertama|kedua|selanjutnya)\b/ui', $plain)
            && ! in_array($layout, ['process', 'timeline', 'diagram'], true)) {
            $issues[] = $this->issue('warning', 'process_visual_opportunity', 'Slide '.$slide->order.' memuat struktur proses tetapi Tampilan Slide belum menonjolkan alurnya.', $slide);
        }

        if (preg_match('/\b(perbedaan|dibandingkan|perbandingan|versus|kelebihan|kekurangan)\b/ui', $plain)
            && $layout !== 'comparison') {
            $issues[] = $this->issue('warning', 'comparison_visual_opportunity', 'Slide '.$slide->order.' memuat perbandingan yang berpotensi lebih jelas dengan Tampilan Slide Comparison.', $slide);
        }

        if ($this->looksLikeCode($slide->content ?? '') && $layout !== 'code') {
            $issues[] = $this->issue('warning', 'code_visual_opportunity', 'Slide '.$slide->order.' memuat potongan kode tetapi belum memakai Tampilan Slide Code.', $slide);
        }

        preg_match_all('/\b\d+(?:[.,]\d+)?\s*%/u', $plain, $percentages);
        if (count($percentages[0] ?? []) >= 2 && ! in_array($layout, ['diagram', 'comparison', 'key-points'], true)) {
            $issues[] = $this->issue('warning', 'data_visual_opportunity', 'Slide '.$slide->order.' memuat beberapa nilai persentase yang dapat diperjelas dengan visual data.', $slide);
        }

        return $issues;
    }

    /** @return array<string,mixed> */
    private function issue(string $severity, string $code, string $message, ?Slide $slide = null): array
    {
        return [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'slide_id' => $slide?->id,
            'slide_order' => $slide?->order,
            'slide_title' => $slide?->title,
        ];
    }

    private function audience(Slidebook $slidebook): string
    {
        $audience = $slidebook->design_settings['audience'] ?? 'sma-smk';

        return in_array($audience, ['smp', 'sma-smk', 'general'], true) ? $audience : 'sma-smk';
    }

    private function audienceLabel(string $audience): string
    {
        return match ($audience) {
            'smp' => 'SMP',
            'general' => 'Umum',
            default => 'SMA/SMK',
        };
    }

    private function layout(Slide $slide): string
    {
        $layout = strtolower(trim((string) $slide->layout));

        return $layout !== '' ? $layout : 'reading';
    }

    private function hasSourceReference(Slide $slide): bool
    {
        return is_array($slide->source_reference) && $slide->source_reference !== [];
    }

    private function plainText(string $content): string
    {
        $text = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function pointCount(string $content): int
    {
        $count = preg_match_all('/<li\b/iu', $content, $matches);
        if ($count > 0) {
            return $count;
        }

        return preg_match_all('/(?:^|\R)\s*(?:[-*•]|\d+[.)])\s+/u', $content, $matches) ?: 0;
    }

    private function averageSentenceWords(string $plain): float
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($sentences === []) {
            return (float) str_word_count($plain);
        }

        $total = array_sum(array_map(fn (string $sentence): int => str_word_count($sentence), $sentences));

        return $total / count($sentences);
    }

    private function looksLikeCode(string $content): bool
    {
        return (bool) preg_match('/(<\?php|\bfunction\s+\w+|\bSELECT\s+.+\bFROM\b|<\/?[a-z][^>]*>|\{\s*[^}]+;)/isu', $content);
    }

    private function sourceText(Slidebook $slidebook): string
    {
        $material = $slidebook->material;
        if (! $material) {
            return '';
        }

        $parts = $material->documents
            ->map(fn ($document): string => $document->extraction?->status === 'completed'
                ? (string) ($document->extraction?->content ?? '')
                : '')
            ->filter(fn (string $text): bool => trim($text) !== '')
            ->values();

        if ($parts->isNotEmpty()) {
            return $parts->implode("\n");
        }

        return trim((string) ($material->content ?: $material->description ?: ''));
    }

    /** @return Collection<int,string> */
    private function meaningfulTokens(string $text): Collection
    {
        $normalized = Str::lower($this->plainText($text));
        $tokens = preg_split('/[^\pL\pN]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stopwords = [
            'yang', 'dan', 'atau', 'dari', 'untuk', 'pada', 'dengan', 'dalam', 'adalah', 'sebagai', 'oleh', 'ke', 'di', 'ini', 'itu',
            'the', 'and', 'for', 'with', 'from', 'that', 'this', 'into', 'are', 'is', 'of', 'to',
        ];

        return collect($tokens)
            ->filter(fn (string $token): bool => mb_strlen($token) >= 4 && ! in_array($token, $stopwords, true))
            ->unique()
            ->values();
    }
}
