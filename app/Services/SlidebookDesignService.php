<?php

namespace App\Services;

use App\Models\Slidebook;

class SlidebookDesignService
{
    /**
     * @return array<string, mixed>
     */
    public function getDesignTokens(Slidebook $slidebook): array
    {
        $settings = $slidebook->design_settings ?? ['preset' => 'indigo-dark'];
        $preset = $settings['preset'] ?? 'indigo-dark';
        $audience = $this->normalizeAudience($settings['audience'] ?? 'sma-smk');

        $tokens = $this->getPresetTokens($preset);

        return [
            'css_variables' => $this->buildCssVariables($tokens),
            'font_class' => $tokens['font_class'],
            'audience' => $audience,
            'audience_class' => 'audience-'.str_replace('-', '_', $audience),
            'initial_visible_blocks' => match ($audience) {
                'smp' => 3,
                'sma-smk' => 4,
                default => 5,
            },
        ];
    }

    private function normalizeAudience(string $audience): string
    {
        return in_array($audience, ['general', 'smp', 'sma-smk'], true)
            ? $audience
            : 'sma-smk';
    }

    /**
     * @return array<string, string>
     */
    private function getPresetTokens(string $preset): array
    {
        $presets = [
            'indigo-dark' => [
                '--slide-color-scheme' => 'dark',
                '--slide-bg' => '#020617', // slate-950
                '--slide-surface' => '#0f172a', // slate-900
                '--slide-surface-raised' => '#1e293b', // slate-800
                '--slide-border' => '#334155', // slate-700
                '--slide-text' => '#e2e8f0', // slate-200
                '--slide-text-muted' => '#94a3b8', // slate-400
                '--slide-text-heading' => '#f8fafc', // slate-50
                '--slide-gradient-start' => '#818cf8', // indigo-400
                '--slide-gradient-end' => '#a78bfa', // violet-400
                '--slide-primary' => '#6366f1', // indigo-500
                '--slide-accent' => '#a5b4fc', // indigo-300
                '--slide-accent-soft' => '#818cf855',
                'font_class' => 'font-sans',
            ],
            'modern-tech' => [
                '--slide-color-scheme' => 'dark',
                '--slide-bg' => '#09090b', // zinc-950
                '--slide-surface' => '#18181b', // zinc-900
                '--slide-surface-raised' => '#27272a', // zinc-800
                '--slide-border' => '#3f3f46', // zinc-700
                '--slide-text' => '#e4e4e7', // zinc-200
                '--slide-text-muted' => '#a1a1aa', // zinc-400
                '--slide-text-heading' => '#fafafa', // zinc-50
                '--slide-gradient-start' => '#06b6d4', // cyan-500
                '--slide-gradient-end' => '#3b82f6', // blue-500
                '--slide-primary' => '#0ea5e9', // sky-500
                '--slide-accent' => '#7dd3fc', // sky-300
                '--slide-accent-soft' => '#38bdf855',
                'font_class' => 'font-sans',
            ],
            'academic-blue' => [
                '--slide-color-scheme' => 'light',
                '--slide-bg' => '#f8fafc', // slate-50 (light mode)
                '--slide-surface' => '#ffffff', // white
                '--slide-surface-raised' => '#f1f5f9', // slate-100
                '--slide-border' => '#e2e8f0', // slate-200
                '--slide-text' => '#334155', // slate-700
                '--slide-text-muted' => '#64748b', // slate-500
                '--slide-text-heading' => '#0f172a', // slate-900
                '--slide-gradient-start' => '#1d4ed8', // blue-700
                '--slide-gradient-end' => '#2563eb', // blue-600
                '--slide-primary' => '#2563eb', // blue-600
                '--slide-accent' => '#3b82f6', // blue-500
                '--slide-accent-soft' => '#93c5fd55',
                'font_class' => 'font-serif',
            ],
            'creative-education' => [
                '--slide-color-scheme' => 'light',
                '--slide-bg' => '#fff1f2', // rose-50
                '--slide-surface' => '#ffffff', // white
                '--slide-surface-raised' => '#ffe4e6', // rose-100
                '--slide-border' => '#fecdd3', // rose-200
                '--slide-text' => '#4c0519', // rose-950
                '--slide-text-muted' => '#9f1239', // rose-800
                '--slide-text-heading' => '#881337', // rose-900
                '--slide-gradient-start' => '#f43f5e', // rose-500
                '--slide-gradient-end' => '#ec4899', // pink-500
                '--slide-primary' => '#e11d48', // rose-600
                '--slide-accent' => '#fb7185', // rose-400
                '--slide-accent-soft' => '#fda4af55',
                'font_class' => 'font-sans',
            ],
            'fresh-learning' => [
                '--slide-color-scheme' => 'light',
                '--slide-bg' => '#f0fdf4', // green-50
                '--slide-surface' => '#ffffff', // white
                '--slide-surface-raised' => '#dcfce7', // green-100
                '--slide-border' => '#bbf7d0', // green-200
                '--slide-text' => '#14532d', // green-900
                '--slide-text-muted' => '#166534', // green-800
                '--slide-text-heading' => '#052e16', // green-950
                '--slide-gradient-start' => '#10b981', // emerald-500
                '--slide-gradient-end' => '#22c55e', // green-500
                '--slide-primary' => '#15803d', // green-700
                '--slide-accent' => '#34d399', // emerald-400
                '--slide-accent-soft' => '#6ee7b755',
                'font_class' => 'font-sans',
            ],
            'minimalist' => [
                '--slide-color-scheme' => 'light',
                '--slide-bg' => '#ffffff', // white
                '--slide-surface' => '#f9fafb', // gray-50
                '--slide-surface-raised' => '#f3f4f6', // gray-100
                '--slide-border' => '#e5e7eb', // gray-200
                '--slide-text' => '#374151', // gray-700
                '--slide-text-muted' => '#6b7280', // gray-500
                '--slide-text-heading' => '#111827', // gray-900
                '--slide-gradient-start' => '#6b7280', // gray-500
                '--slide-gradient-end' => '#4b5563', // gray-600
                '--slide-primary' => '#374151', // gray-700
                '--slide-accent' => '#9ca3af', // gray-400
                '--slide-accent-soft' => '#d1d5db55',
                'font_class' => 'font-sans font-light',
            ],
        ];

        return $presets[$preset] ?? $presets['indigo-dark'];
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private function buildCssVariables(array $tokens): string
    {
        $css = [];
        foreach ($tokens as $key => $value) {
            if (str_starts_with($key, '--')) {
                $css[] = "$key: $value;";
            }
        }

        return implode(' ', $css);
    }
}
