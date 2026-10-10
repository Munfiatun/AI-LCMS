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
    <section class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/75 shadow-xl shadow-black/10">
        <div class="p-6 lg:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[0.18em] text-indigo-300">Assessment Analytics</span>
                        <span class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-[10px] font-semibold text-slate-400">{{ ucfirst($quiz->status) }}</span>
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Hasil Kuis: {{ $quiz->title }}</h1>
                    <p class="mt-2 text-sm text-slate-400">Pantau nilai, status kelulusan, durasi, dan riwayat percobaan siswa.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-slate-600 hover:text-white">Kelola Kuis</a>
                    <a href="{{ route('instructor.quizzes.index') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">Daftar Kuis</a>
                </div>
            </div>

            <div class="mt-7 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total Attempt</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ $attemptCount }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-500/15 bg-emerald-500/[0.04] p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Lulus di Halaman Ini</p>
                    <p class="mt-2 text-2xl font-extrabold text-emerald-300">{{ $pagePassed }}</p>
                </div>
                <div class="rounded-2xl border border-indigo-500/15 bg-indigo-500/[0.04] p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Rata-rata Halaman</p>
                    <p class="mt-2 text-2xl font-extrabold text-indigo-300">{{ $pageAverage !== null ? round($pageAverage, 1).'%' : '-' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Batas Lulus</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ $quiz->passing_score }}%</p>
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70">
        <div class="flex flex-col gap-1 border-b border-slate-800 px-5 py-4 sm:flex-row sm:items-end sm:justify-between sm:px-6">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-slate-500">Attempt History</p>
                <h2 class="mt-1 text-lg font-bold text-white">Riwayat Pengerjaan Siswa</h2>
            </div>
            <p class="text-xs text-slate-500">Data disusun berdasarkan riwayat attempt yang tersimpan.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800">
                <thead class="bg-slate-950/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Siswa</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Tanggal & Status</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Durasi</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Benar / Salah</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/90">
                    @forelse($attempts as $attempt)
                        <tr class="transition hover:bg-slate-800/25">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-indigo-500/20 bg-indigo-500/10 text-sm font-extrabold text-indigo-300">{{ substr($attempt->student->name, 0, 1) }}</div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-100">{{ $attempt->student->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $attempt->student->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-300">
                                <p>{{ $attempt->submitted_at ? $attempt->submitted_at->format('d M Y, H:i') : '-' }}</p>
                                <span class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $attempt->status === 'submitted' ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : ($attempt->status === 'expired' ? 'border-amber-500/20 bg-amber-500/10 text-amber-300' : 'border-slate-700 bg-slate-800 text-slate-400') }}">{{ ucfirst($attempt->status) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-300">
                                @if($attempt->duration_seconds)
                                    {{ floor($attempt->duration_seconds / 60) }}m {{ $attempt->duration_seconds % 60 }}s
                                @else
                                    -
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span class="font-bold text-emerald-300">{{ $attempt->correct_count }}</span>
                                <span class="mx-1 text-slate-600">/</span>
                                <span class="font-bold text-rose-300">{{ $attempt->wrong_count }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-extrabold {{ $attempt->isPassed() ? 'text-emerald-300' : 'text-rose-300' }}">{{ round($attempt->percentage, 2) }}%</span>
                                    <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $attempt->isPassed() ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-rose-500/20 bg-rose-500/10 text-rose-300' }}">{{ $attempt->isPassed() ? 'LULUS' : 'GAGAL' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Skor: {{ $attempt->score }} poin</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-800 bg-slate-950/60 text-slate-500">∅</div>
                                <p class="mt-4 text-sm font-semibold text-slate-300">Belum ada percobaan ujian pada kuis ini.</p>
                                <p class="mt-1 text-xs text-slate-500">Data hasil akan muncul setelah siswa mengumpulkan kuis.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attempts->hasPages())
            <div class="border-t border-slate-800 px-6 py-4">{{ $attempts->links() }}</div>
        @endif
    </section>
</div>
@endsection