<?php

namespace App\Services;

class SlideVisualEnrichment
{
    /**
     * Extract explicitly labelled percentages that are safe to visualize.
     *
     * The parser is intentionally conservative: a value is charted only when a
     * source line has a clear label + separator + percentage. The original text
     * remains the source of truth and is never replaced by this visualization.
     *
     * @return list<array{label: string, value: float}>
     */
    public function percentageStats(?string $content): array
    {
        if ($content === null || trim($content) === '') {
            return [];
        }

        $lines = preg_split('/\R/u', str_replace(["\r\n", "\r"], "\n", $content)) ?: [];
        $stats = [];
        $seenLabels = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Supports: "- Aktif: 72%", "1. Lulus = 85%", "Retensi — 64%".
            if (! preg_match('/^(?:[-*]\s*|\d+[.)]\s*)?(.{2,48}?)\s*(?:[:=]|—|–|\s-\s)\s*(100|\d{1,2})(?:[.,](\d+))?\s*%\s*$/u', $line, $matches)) {
                continue;
            }

            $label = trim($matches[1], " \t\n\r\0\x0B:=-–—");
            if ($label === '') {
                continue;
            }

            $fraction = isset($matches[3]) && $matches[3] !== '' ? '.'.$matches[3] : '';
            $value = (float) ($matches[2].$fraction);
            if ($value < 0 || $value > 100) {
                continue;
            }

            $key = mb_strtolower($label);
            if (isset($seenLabels[$key])) {
                continue;
            }

            $seenLabels[$key] = true;
            $stats[] = ['label' => $label, 'value' => $value];

            if (count($stats) === 5) {
                break;
            }
        }

        // A single percentage is better left as text; two or more establish a
        // meaningful visual comparison without inventing additional context.
        return count($stats) >= 2 ? $stats : [];
    }
}
