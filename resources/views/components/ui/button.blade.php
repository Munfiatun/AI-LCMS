@props([
    'href' => null,
    'variant' => 'primary',
    'size' => 'md',
    'block' => false,
    'disabled' => false,
    'type' => 'button',
])

@php
    $base = 'teacher-button inline-flex items-center justify-center gap-2 rounded-xl font-bold transition-colors focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'primary' => 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid hover:bg-indigo-500 theme-accent-fill-hover shadow-sm shadow-indigo-950/30',
        'success' => 'bg-emerald-600 theme-success-fill text-white teacher-on-solid hover:bg-emerald-500 theme-success-fill-hover shadow-sm shadow-emerald-950/30',
        'secondary' => 'teacher-secondary-button border border-slate-700 theme-border bg-slate-800 theme-muted-surface text-slate-200 theme-heading hover:bg-slate-700 theme-muted-surface-hover',
        'warning' => 'border border-amber-500/30 theme-warning-border bg-amber-500/10 theme-warning-surface text-amber-200 theme-warning-text hover:bg-amber-500/20 theme-warning-surface-hover',
        'danger' => 'border border-rose-500/30 theme-danger-border bg-rose-500/10 theme-danger-surface text-rose-300 theme-danger-text hover:bg-rose-500/20 theme-danger-surface-hover',
        'ghost' => 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover',
        'link' => 'text-indigo-300 theme-accent-text hover:text-indigo-200 theme-accent-text-hover',
    ];
    $sizes = [
        'sm' => 'min-h-9 px-3 py-1.5 text-xs',
        'md' => 'min-h-11 px-4 py-2.5 text-xs',
        'lg' => 'min-h-12 px-5 py-3 text-sm',
    ];
    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']).($block ? ' w-full' : ''));
@endphp

@if($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@elseif($href && $disabled)
    <span aria-disabled="true" {{ $attributes->class($classes.' pointer-events-none opacity-50') }}>{{ $slot }}</span>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
