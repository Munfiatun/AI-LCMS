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
        'success' => 'border-emerald-500/30 theme-success-border bg-emerald-500/15 theme-success-surface text-emerald-300 theme-success-text',
        'warning' => 'border-amber-500/30 theme-warning-border bg-amber-500/15 theme-warning-surface text-amber-300 theme-warning-text',
        'danger' => 'border-rose-500/30 theme-danger-border bg-rose-500/15 theme-danger-surface text-rose-300 theme-danger-text',
        'info' => 'border-cyan-500/30 theme-accent-border bg-cyan-500/15 theme-accent-surface text-cyan-300 theme-accent-text',
        'indigo' => 'border-indigo-500/30 theme-accent-border bg-indigo-500/15 theme-accent-surface text-indigo-300 theme-accent-text',
        'neutral' => 'border-slate-700 theme-border bg-slate-800/80 theme-muted-surface text-slate-300 theme-body',
    ];
    $sizes = [
        'xs' => 'px-1.5 py-0.5 text-[9px]',
        'sm' => 'px-2 py-0.5 text-[10px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];
    $classes = 'teacher-badge inline-flex items-center rounded-full border font-bold uppercase tracking-wide '.($tones[$tone] ?? $tones['neutral']).' '.($sizes[$size] ?? $sizes['sm']);
    $display = $label ?? ($status !== null ? ucfirst((string) $status) : null);
@endphp

<span {{ $attributes->class($classes) }}>{{ $display ?? $slot }}</span>
