@props([
    'tone' => 'info',
    'title' => null,
])

@php
    $tones = [
        'success' => ['wrap' => 'border-emerald-800/40 bg-emerald-950/20', 'icon' => 'bg-emerald-500/15 text-emerald-300', 'text' => 'text-emerald-200', 'symbol' => '✓'],
        'warning' => ['wrap' => 'border-amber-800/40 bg-amber-950/20', 'icon' => 'bg-amber-500/15 text-amber-300', 'text' => 'text-amber-100', 'symbol' => '!'],
        'danger' => ['wrap' => 'border-rose-800/40 bg-rose-950/20', 'icon' => 'bg-rose-500/15 text-rose-300', 'text' => 'text-rose-100', 'symbol' => '!'],
        'info' => ['wrap' => 'border-cyan-800/40 bg-cyan-950/20', 'icon' => 'bg-cyan-500/15 text-cyan-300', 'text' => 'text-cyan-100', 'symbol' => 'i'],
    ];
    $style = $tones[$tone] ?? $tones['info'];
@endphp

<div role="status" {{ $attributes->class('rounded-2xl border p-4 sm:p-5 '.$style['wrap']) }}>
    <div class="flex items-start gap-3">
        <span aria-hidden="true" class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-black {{ $style['icon'] }}">{{ $style['symbol'] }}</span>
        <div class="min-w-0 flex-1">
            @if($title)
                <h3 class="text-sm font-bold text-white">{{ $title }}</h3>
            @endif
            <div class="{{ $title ? 'mt-1' : '' }} text-xs leading-relaxed {{ $style['text'] }}">{{ $slot }}</div>
        </div>
    </div>
</div>
