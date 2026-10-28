@extends('layouts.app')

@php
    $title = 'Performa Siswa: ' . $student->name;
    $breadcrumb = 'Analitik & Pelaporan';
    $levelClasses = [
        'high' => 'theme-danger-border theme-danger-surface theme-danger-text',
        'medium' => 'theme-warning-border theme-warning-surface theme-warning-text',
        'low' => 'theme-accent-border theme-accent-surface theme-accent-text',
        'stable' => 'theme-success-border theme-success-surface theme-success-text',
    ];
    $levelLabels = [
        'high' => 'Prioritas Tinggi',
        'medium' => 'Perlu Dipantau',
        'low' => 'Pemantauan Rutin',
        'stable' => 'Tuntas / Stabil',
    ];
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center gap-2 text-sm theme-body">
        <a href="{{ route('instructor.courses.analytics', $course) }}" class="theme-heading-hover transition-colors">&larr; Kembali ke Analitik Kursus</a>
        <span class="theme-muted">&bull;</span>
        <span class="theme-body">{{ $course->title }}</span>
    </div>

    <div class="teacher-page-header border p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border theme-accent-border theme-accent-surface text-xl font-extrabold theme-accent-text">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.2em] theme-accent-text">Student Performance Detail</p>
                    <h1 class="teacher-page-title mt-1 text-2xl font-extrabold theme-heading">{{ $student->name }}</h1>
                    <p class="mt-1 text-sm theme-body">{{ $student->email }}</p>
                </div>
            </div>
            <div class="rounded-xl border px-4 py-3 {{ $levelClasses[$interventionLevel] ?? $levelClasses['low'] }}">
                <p class="text-sm font-bold uppercase tracking-wider opacity-75">Status Intervensi</p>
                <p class="mt-1 text-sm font-extrabold">{{ $levelLabels[$interventionLevel] ?? 'Pemantauan Rutin' }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border teacher-card p-5">
            <p class="text-sm font-bold uppercase tracking-wider theme-body">Progres Kursus</p>
            <p class="mt-3 text-3xl font-extrabold theme-heading">{{ number_format($enrollment->progress_percentage, 1) }}%</p>
            <div class="mt-3 h-1.5 rounded-full theme-muted-surface">
                <div class="h-1.5 rounded-full theme-accent-fill" style="width: {{ max(0, min(100, (float) $enrollment->progress_percentage)) }}%"></div>
            </div>
        </div>
        <div class="rounded-2xl border teacher-card p-5">
            <p class="text-sm font-bold uppercase tracking-wider theme-body">Materi Selesai</p>
            <p class="mt-3 text-3xl font-extrabold theme-heading">{{ $completedMaterials }}<span class="text-lg theme-muted">/{{ $totalMaterials }}</span></p>
            <p class="mt-1 text-sm theme-muted">Materi published</p>
        </div>
        <div class="rounded-2xl border teacher-card p-5">
            <p class="text-sm font-bold uppercase tracking-wider theme-body">Quiz Lulus</p>
            <p class="mt-3 text-3xl font-extrabold theme-success-text">{{ $passedQuizzes }}<span class="text-lg theme-muted">/{{ $totalQuizzes }}</span></p>
            <p class="mt-1 text-sm theme-muted">{{ $submittedAttempts }} attempt submitted</p>
        </div>
        <div class="rounded-2xl border teacher-card p-5">
            <p class="text-sm font-bold uppercase tracking-wider theme-body">Rata-Rata Quiz</p>
            <p class="mt-3 text-3xl font-extrabold theme-warning-text">{{ number_format($averageQuizScore, 1) }}%</p>
            <p class="mt-1 text-sm theme-muted">Semua attempt submitted</p>
        </div>
    </div>

    <div class="rounded-2xl border p-5 {{ $levelClasses[$interventionLevel] ?? $levelClasses['low'] }}">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 text-lg">◎</div>
            <div>
                <h2 class="text-sm font-extrabold">Rekomendasi Tindak Lanjut</h2>
                <p class="mt-1 text-sm leading-relaxed opacity-90">{{ $interventionMessage }}</p>
                <p class="mt-2 text-sm opacity-65">Rekomendasi dibuat secara deterministik dari progres dan hasil assessment, bukan diagnosis otomatis.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border teacher-card p-6 shadow-xl">
            <div class="mb-4">
                <h2 class="text-lg font-bold theme-heading">Progres Materi</h2>
                <p class="mt-1 text-sm theme-muted">Status material published pada course ini.</p>
            </div>
            <div class="space-y-2">
                @forelse($materialRows as $material)
                    <div class="flex items-center justify-between gap-3 rounded-xl border teacher-card px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold theme-heading">{{ $material['title'] }}</p>
                            <p class="mt-1 truncate text-sm theme-muted">{{ $material['section'] }}</p>
                        </div>
                        @if($material['completed'])
                            <div class="text-right">
                                <span class="rounded-full border theme-success-border theme-success-surface px-2.5 py-1 text-sm font-bold uppercase theme-success-text">Selesai</span>
                                @if($material['completed_at'])
                                    <p class="mt-1 text-sm theme-muted">{{ $material['completed_at']->format('d M Y') }}</p>
                                @endif
                            </div>
                        @else
                            <span class="rounded-full border theme-warning-border theme-warning-surface px-2.5 py-1 text-sm font-bold uppercase theme-warning-text">Belum</span>
                        @endif
                    </div>
                @empty
                    <p class="rounded-xl border theme-border theme-surface px-4 py-6 text-center text-sm theme-muted">Belum ada materi published.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border teacher-card p-6 shadow-xl">
            <div class="mb-4">
                <h2 class="text-lg font-bold theme-heading">Performa Quiz</h2>
                <p class="mt-1 text-sm theme-muted">Nilai terbaik, nilai terbaru, dan jumlah attempt siswa.</p>
            </div>
            <div class="space-y-2">
                @forelse($quizRows as $quiz)
                    <div class="rounded-xl border teacher-card px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold theme-heading">{{ $quiz['title'] }}</p>
                                <p class="mt-1 text-sm theme-muted">Passing score {{ number_format($quiz['passing_score'], 0) }}% &bull; {{ $quiz['attempts'] }} attempt</p>
                            </div>
                            @if($quiz['passed'])
                                <span class="rounded-full border theme-success-border theme-success-surface px-2.5 py-1 text-sm font-bold uppercase theme-success-text">Lulus</span>
                            @elseif($quiz['attempts'] > 0)
                                <span class="rounded-full border theme-danger-border theme-danger-surface px-2.5 py-1 text-sm font-bold uppercase theme-danger-text">Belum Lulus</span>
                            @else
                                <span class="rounded-full border theme-border theme-muted-surface px-2.5 py-1 text-sm font-bold uppercase theme-body">Belum Mengerjakan</span>
                            @endif
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-lg theme-surface px-3 py-2">
                                <p class="theme-muted">Nilai Terbaik</p>
                                <p class="mt-1 font-bold theme-heading">{{ $quiz['best_score'] === null ? '-' : number_format($quiz['best_score'], 1).'%' }}</p>
                            </div>
                            <div class="rounded-lg theme-surface px-3 py-2">
                                <p class="theme-muted">Nilai Terbaru</p>
                                <p class="mt-1 font-bold theme-heading">{{ $quiz['latest_score'] === null ? '-' : number_format($quiz['latest_score'], 1).'%' }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border theme-border theme-surface px-4 py-6 text-center text-sm theme-muted">Belum ada quiz pada course ini.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
