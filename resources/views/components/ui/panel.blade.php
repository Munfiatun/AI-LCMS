@props([
    'variant' => 'default',
    'padding' => 'md',
])

@php
    $variants = [
        'default' => 'border-slate-800 bg-slate-900/80',
        'muted' => 'border-slate-800 bg-slate-950/50',
        'elevated' => 'border-slate-800 bg-slate-900 shadow-xl',
        'success' => 'border-emerald-800/40 bg-emerald-950/20',
        'warning' => 'border-amber-800/40 bg-amber-950/20',
        'danger' => 'border-rose-800/40 bg-rose-950/20',
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
