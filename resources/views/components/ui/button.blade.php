@props([
    'href' => null,
    'variant' => 'primary',
    'size' => 'md',
    'block' => false,
    'disabled' => false,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-bold transition-colors focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-500 shadow-sm shadow-indigo-950/30',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-500 shadow-sm shadow-emerald-950/30',
        'secondary' => 'border border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700',
        'warning' => 'border border-amber-500/30 bg-amber-500/10 text-amber-200 hover:bg-amber-500/20',
        'danger' => 'border border-rose-500/30 bg-rose-500/10 text-rose-300 hover:bg-rose-500/20',
        'ghost' => 'text-slate-300 hover:bg-slate-800 hover:text-white',
        'link' => 'text-indigo-300 hover:text-indigo-200',
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
