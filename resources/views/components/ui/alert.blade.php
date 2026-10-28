@props([
    'tone' => 'info',
    'title' => null,
])

@php
    $tones = [
        'success' => ['wrap' => 'border-emerald-800/40 theme-success-border bg-emerald-950/20 theme-success-surface', 'icon' => 'bg-emerald-500/15 theme-success-surface text-emerald-300 theme-success-text', 'text' => 'text-emerald-200 theme-success-text', 'symbol' => '✓'],
        'warning' => ['wrap' => 'border-amber-800/40 theme-warning-border bg-amber-950/20 theme-warning-surface', 'icon' => 'bg-amber-500/15 theme-warning-surface text-amber-300 theme-warning-text', 'text' => 'text-amber-100 theme-warning-text', 'symbol' => '!'],
        'danger' => ['wrap' => 'border-rose-800/40 theme-danger-border bg-rose-950/20 theme-danger-surface', 'icon' => 'bg-rose-500/15 theme-danger-surface text-rose-300 theme-danger-text', 'text' => 'text-rose-100 theme-danger-text', 'symbol' => '!'],
        'info' => ['wrap' => 'border-cyan-800/40 theme-accent-border bg-cyan-950/20 theme-accent-surface', 'icon' => 'bg-cyan-500/15 theme-accent-surface text-cyan-300 theme-accent-text', 'text' => 'text-cyan-100 theme-accent-text', 'symbol' => 'i'],
    ];
    $style = $tones[$tone] ?? $tones['info'];
@endphp

<div role="status" {{ $attributes->class('rounded-2xl border p-4 sm:p-5 '.$style['wrap']) }}>
    <div class="flex items-start gap-3">
        <span aria-hidden="true" class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-black {{ $style['icon'] }}">{{ $style['symbol'] }}</span>
        <div class="min-w-0 flex-1">
            @if($title)
                <h3 class="text-sm font-bold text-white theme-heading">{{ $title }}</h3>
            @endif
            <div class="{{ $title ? 'mt-1' : '' }} teacher-alert-copy text-xs leading-relaxed {{ $style['text'] }}">{{ $slot }}</div>
        </div>
    </div>
</div>
