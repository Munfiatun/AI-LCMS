@props([
    'tone' => 'neutral',
    'status' => null,
    'label' => null,
    'size' => 'sm',
])

@php
    $statusMap = [
        'draft' => ['tone' => 'warning', 'label' => 'Draft'],
        'review' => ['tone' => 'warning', 'label' => 'Perlu Dicek Guru'],
        'published' => ['tone' => 'success', 'label' => 'Terbit'],
        'active' => ['tone' => 'success', 'label' => 'Aktif'],
        'completed' => ['tone' => 'success', 'label' => 'Selesai'],
        'archived' => ['tone' => 'neutral', 'label' => 'Diarsipkan'],
        'inactive' => ['tone' => 'neutral', 'label' => 'Nonaktif'],
        'pending' => ['tone' => 'info', 'label' => 'Menunggu'],
        'failed' => ['tone' => 'danger', 'label' => 'Gagal'],
        'expired' => ['tone' => 'danger', 'label' => 'Kedaluwarsa'],
    ];
    if ($status !== null && isset($statusMap[$status])) {
        $tone = $statusMap[$status]['tone'];
        $label = $label ?? $statusMap[$status]['label'];
    }
    $tones = [
        'success' => 'border-emerald-500/30 bg-emerald-500/15 text-emerald-300',
        'warning' => 'border-amber-500/30 bg-amber-500/15 text-amber-300',
        'danger' => 'border-rose-500/30 bg-rose-500/15 text-rose-300',
        'info' => 'border-cyan-500/30 bg-cyan-500/15 text-cyan-300',
        'indigo' => 'border-indigo-500/30 bg-indigo-500/15 text-indigo-300',
        'neutral' => 'border-slate-700 bg-slate-800/80 text-slate-300',
    ];
    $sizes = [
        'xs' => 'px-1.5 py-0.5 text-[9px]',
        'sm' => 'px-2 py-0.5 text-[10px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];
    $classes = 'inline-flex items-center rounded-full border font-bold uppercase tracking-wide '.($tones[$tone] ?? $tones['neutral']).' '.($sizes[$size] ?? $sizes['sm']);
    $display = $label ?? ($status !== null ? ucfirst((string) $status) : null);
@endphp

<span {{ $attributes->class($classes) }}>{{ $display ?? $slot }}</span>
