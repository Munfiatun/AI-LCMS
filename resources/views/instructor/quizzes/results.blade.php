@extends('layouts.app')

@php
    $title = 'Hasil Kuis';
    $breadcrumb = 'Hasil Kuis';
    $attemptCount = $attempts->total();
    $pageAttempts = collect($attempts->items());
    $pagePassed = $pageAttempts->filter(fn ($attempt) => $attempt->isPassed())->count();
    $pageAverage = $pageAttempts->whereNotNull('percentage')->avg('percentage');
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <section class="teacher-page-header teacher-page-header-flush overflow-hidden border">
        <div class="p-6 lg:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full border theme-accent-border theme-accent-surface px-3 py-1 text-sm font-extrabold uppercase tracking-[0.18em] theme-accent-text">Assessment Analytics</span>
                        <span class="rounded-full border theme-border theme-surface px-3 py-1 text-sm font-semibold theme-body">{{ ucfirst($quiz->status) }}</span>
                    </div>
                    <h1 class="teacher-page-title text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Hasil Kuis: {{ $quiz->title }}</h1>
                    <p class="mt-2 text-sm theme-body">Pantau nilai, status kelulusan, durasi, dan riwayat percobaan siswa.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="teacher-secondary-button inline-flex items-center justify-center rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm font-semibold theme-body transition theme-border-hover theme-heading-hover">Kelola Kuis</a>
                    <a href="{{ route('instructor.quizzes.index') }}" class="inline-flex items-center justify-center rounded-xl theme-accent-fill px-4 py-2.5 text-sm font-bold teacher-on-solid transition theme-accent-fill-hover">Daftar Kuis</a>
                </div>
            </div>

            <div class="mt-7 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border teacher-card p-4">
                    <p class="text-sm font-semibold uppercase tracking-wide theme-muted">Total Attempt</p>
                    <p class="mt-2 text-2xl font-extrabold theme-heading">{{ $attemptCount }}</p>
                </div>
                <div class="rounded-2xl border theme-success-border theme-success-surface p-4">
                    <p class="text-sm font-semibold uppercase tracking-wide theme-muted">Lulus di Halaman Ini</p>
                    <p class="mt-2 text-2xl font-extrabold theme-success-text">{{ $pagePassed }}</p>
                </div>
                <div class="rounded-2xl border theme-accent-border theme-accent-surface p-4">
                    <p class="text-sm font-semibold uppercase tracking-wide theme-muted">Rata-rata Halaman</p>
                    <p class="mt-2 text-2xl font-extrabold theme-accent-text">{{ $pageAverage !== null ? round($pageAverage, 1).'%' : '-' }}</p>
                </div>
                <div class="rounded-2xl border teacher-card p-4">
                    <p class="text-sm font-semibold uppercase tracking-wide theme-muted">Batas Lulus</p>
                    <p class="mt-2 text-2xl font-extrabold theme-heading">{{ $quiz->passing_score }}%</p>
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border teacher-card">
        <div class="flex flex-col gap-1 border-b theme-border px-5 py-4 sm:flex-row sm:items-end sm:justify-between sm:px-6">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-[0.18em] theme-muted">Attempt History</p>
                <h2 class="mt-1 text-lg font-bold theme-heading">Riwayat Pengerjaan Siswa</h2>
            </div>
            <p class="text-sm theme-muted">Data disusun berdasarkan riwayat attempt yang tersimpan.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800">
                <thead class="theme-surface">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-bold uppercase tracking-wider theme-muted">Siswa</th>
                        <th class="px-6 py-3 text-left text-sm font-bold uppercase tracking-wider theme-muted">Tanggal & Status</th>
                        <th class="px-6 py-3 text-left text-sm font-bold uppercase tracking-wider theme-muted">Durasi</th>
                        <th class="px-6 py-3 text-left text-sm font-bold uppercase tracking-wider theme-muted">Benar / Salah</th>
                        <th class="px-6 py-3 text-left text-sm font-bold uppercase tracking-wider theme-muted">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/90">
                    @forelse($attempts as $attempt)
                        <tr class="transition theme-muted-surface-hover">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border theme-accent-border theme-accent-surface text-sm font-extrabold theme-accent-text">{{ substr($attempt->student->name, 0, 1) }}</div>
                                    <div>
                                        <p class="text-sm font-semibold theme-heading">{{ $attempt->student->name }}</p>
                                        <p class="mt-0.5 text-sm theme-muted">{{ $attempt->student->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm theme-body">
                                <p>{{ $attempt->submitted_at ? $attempt->submitted_at->format('d M Y, H:i') : '-' }}</p>
                                <span class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-sm font-bold uppercase tracking-wide {{ $attempt->status === 'submitted' ? 'theme-success-border theme-success-surface theme-success-text' : ($attempt->status === 'expired' ? 'theme-warning-border theme-warning-surface theme-warning-text' : 'theme-border theme-muted-surface theme-body') }}">{{ ucfirst($attempt->status) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm theme-body">
                                @if($attempt->duration_seconds)
                                    {{ floor($attempt->duration_seconds / 60) }}m {{ $attempt->duration_seconds % 60 }}s
                                @else
                                    -
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span class="font-bold theme-success-text">{{ $attempt->correct_count }}</span>
                                <span class="mx-1 theme-muted">/</span>
                                <span class="font-bold theme-danger-text">{{ $attempt->wrong_count }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-extrabold {{ $attempt->isPassed() ? 'theme-success-text' : 'theme-danger-text' }}">{{ round($attempt->percentage, 2) }}%</span>
                                    <span class="rounded-full border px-2 py-0.5 text-sm font-bold uppercase tracking-wide {{ $attempt->isPassed() ? 'theme-success-border theme-success-surface theme-success-text' : 'theme-danger-border theme-danger-surface theme-danger-text' }}">{{ $attempt->isPassed() ? 'LULUS' : 'GAGAL' }}</span>
                                </div>
                                <p class="mt-1 text-sm theme-muted">Skor: {{ $attempt->score }} poin</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border teacher-card theme-muted">∅</div>
                                <p class="mt-4 text-sm font-semibold theme-body">Belum ada percobaan ujian pada kuis ini.</p>
                                <p class="mt-1 text-sm theme-muted">Data hasil akan muncul setelah siswa mengumpulkan kuis.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attempts->hasPages())
            <div class="border-t theme-border px-6 py-4">{{ $attempts->links() }}</div>
        @endif
    </section>
</div>
@endsection
