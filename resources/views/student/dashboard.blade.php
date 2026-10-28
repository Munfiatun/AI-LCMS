@extends('layouts.app')

@php
    $title = 'Beranda';
    $breadcrumb = 'Beranda';
    $focusRow = $courseRows->first();
@endphp

@section('content')
<div class="space-y-6">
    <section class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-sm font-semibold text-emerald-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Learning Overview
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Belajar Hari Ini, {{ $user->name }}</h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-400">Mulai dari satu langkah berikutnya. Anda tidak perlu membuka semua menu untuk melanjutkan belajar.</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                @if(Route::has('student.adaptive-recommendations.index'))
                    <a href="{{ route('student.adaptive-recommendations.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-500">
                        Rekomendasi Belajar
                    </a>
                @endif
                <a href="{{ route('student.courses.explore') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                    Jelajah Kursus
                </a>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Ringkasan belajar">
        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
            <p class="text-sm font-semibold text-slate-400">Kursus</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['enrolled_courses'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['completed_courses'] }} tuntas</p>
        </article>
        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
            <p class="text-sm font-semibold text-slate-400">Progres</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ number_format($stats['average_progress'], 0) }}%</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-950" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ max(0, min(100, $stats['average_progress'])) }}">
                <div class="h-full rounded-full bg-emerald-500" style="width: {{ max(0, min(100, $stats['average_progress'])) }}%"></div>
            </div>
        </article>
        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
            <p class="text-sm font-semibold text-slate-400">Materi Selesai</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['completed_materials'] }}<span class="text-lg text-slate-500">/{{ $stats['total_materials'] }}</span></p>
            <p class="mt-1 text-xs text-slate-500">materi aktif</p>
        </article>
        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
            <p class="text-sm font-semibold text-slate-400">Rata-Rata Quiz</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ number_format($stats['average_score'], 1) }}%</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['passed_quizzes'] }}/{{ $stats['total_quizzes'] }} lulus</p>
        </article>
    </section>

    @if($focusRow)
        <section class="rounded-3xl border border-emerald-500/20 bg-emerald-500/[0.04] p-5 sm:p-6">
            <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-emerald-300">Fokus Belajar Berikutnya</p>
                    <h2 class="mt-2 text-xl font-extrabold text-white">{{ $focusRow['course']->title }}</h2>
                    <p class="mt-2 text-base leading-7 text-slate-300">{{ $focusRow['recommendation'] }}</p>
                    <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-sm text-slate-500">
                        <span>Progres {{ number_format($focusRow['enrollment']->progress_percentage, 0) }}%</span>
                        <span>{{ $focusRow['completed_materials'] }}/{{ $focusRow['total_materials'] }} materi selesai</span>
                        @if($focusRow['next_material'])
                            <span>Berikutnya: <strong class="text-slate-300">{{ $focusRow['next_material']->title }}</strong></span>
                        @endif
                    </div>
                </div>

                <a href="{{ route('student.courses.continue', $focusRow['course']) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-500">
                    Lanjutkan Belajar
                </a>
            </div>
        </section>
    @elseif($courseRows->isEmpty())
        <section class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-8 text-center sm:p-10">
            <h2 class="text-lg font-bold text-white">Belum ada kursus yang diikuti</h2>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">Cari kursus yang tersedia untuk mulai belajar.</p>
            <a href="{{ route('student.courses.explore') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-500">Jelajah Kursus</a>
        </section>
    @endif

    @if($courseRows->isNotEmpty())
        <section class="space-y-4" id="my-courses">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-extrabold text-white">Kursus Saya</h2>
                    <p class="mt-1 text-sm text-slate-500">Pilih kursus lain jika Anda ingin berpindah fokus.</p>
                </div>
                <a href="{{ route('student.courses.index') }}" class="text-sm font-semibold text-emerald-300 hover:text-emerald-200">Lihat semua →</a>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($courseRows as $row)
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-bold text-white">{{ $row['course']->title }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $row['course']->category?->name ?? 'Kursus' }}</p>
                            </div>
                            @if($row['enrollment']->status === 'completed')
                                <span class="shrink-0 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-300">Tuntas</span>
                            @endif
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-950" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ max(0, min(100, (float) $row['enrollment']->progress_percentage)) }}">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ max(0, min(100, (float) $row['enrollment']->progress_percentage)) }}%"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-3 text-xs text-slate-500">
                            <span>{{ number_format($row['enrollment']->progress_percentage, 0) }}% selesai</span>
                            <span>{{ $row['completed_materials'] }}/{{ $row['total_materials'] }} materi</span>
                        </div>

                        <a href="{{ route('student.courses.continue', $row['course']) }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">Buka Kursus</a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
