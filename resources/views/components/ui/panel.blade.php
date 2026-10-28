@props([
    'variant' => 'default',
    'padding' => 'md',
])

@php
    $variants = [
        'default' => 'border-slate-800 theme-border bg-slate-900/80 theme-surface',
        'muted' => 'border-slate-800 theme-border bg-slate-950/50 theme-surface',
        'elevated' => 'border-slate-800 theme-border bg-slate-900 theme-surface shadow-xl',
        'success' => 'border-emerald-800/40 theme-success-border bg-emerald-950/20 theme-success-surface',
        'warning' => 'border-amber-800/40 theme-warning-border bg-amber-950/20 theme-warning-surface',
        'danger' => 'border-rose-800/40 theme-danger-border bg-rose-950/20 theme-danger-surface',
    ];
    $paddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-5 sm:p-6',
        'lg' => 'p-6 sm:p-8',
    ];
    $classes = 'rounded-2xl border '.($variants[$variant] ?? $variants['default']).' '.($paddings[$padding] ?? $paddings['md']);
@endphp

<div {{ $attributes->class($classes) }}>{{ $slot }}</div>
