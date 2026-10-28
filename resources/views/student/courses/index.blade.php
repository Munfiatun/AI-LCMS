@extends('layouts.app')

@php
    $title = 'Kursus Saya';
    $breadcrumb = 'Kursus Saya';
@endphp

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 sm:p-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Kursus Saya</h1>
                <p class="mt-2 text-base text-slate-400">Lanjutkan kursus yang sedang kamu ikuti.</p>
            </div>
            <a href="{{ route('student.courses.explore') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">Jelajah Kursus</a>
        </div>
    </section>

    @if($courses->isEmpty())
        <section class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
            <h2 class="text-lg font-bold text-white">Belum ada kursus yang diikuti</h2>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">Pilih kursus yang tersedia untuk mulai belajar.</p>
            <a href="{{ route('student.courses.explore') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-500">Jelajah Kursus Sekarang</a>
        </section>
    @else
        <section class="grid gap-4 lg:grid-cols-2">
            @foreach($courses as $course)
                @php
                    $enrollment = $course->enrollments->first();
                @endphp

                <article class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-slate-500">{{ $course->category?->name ?? 'Tanpa kategori' }}</p>
                            <h2 class="mt-1 text-xl font-extrabold text-white">{{ $course->title }}</h2>
                            <p class="mt-1 text-sm text-slate-500">Pengajar: {{ $course->instructor->name }}</p>
                        </div>
                        @if($enrollment?->status === 'completed')
                            <span class="shrink-0 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-300">Tuntas</span>
                        @endif
                    </div>

                    @if($enrollment)
                        <div class="mt-5">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-slate-500">Progres Belajar</span>
                                <strong class="{{ $enrollment->status === 'completed' ? 'text-emerald-300' : 'text-indigo-300' }}">{{ number_format($enrollment->progress_percentage, 0) }}% Selesai</strong>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-950" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ max(0, min(100, (float) $enrollment->progress_percentage)) }}">
                                <div class="h-full rounded-full {{ $enrollment->status === 'completed' ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ max(0, min(100, (float) $enrollment->progress_percentage)) }}%"></div>
                            </div>
                        </div>
                    @endif

                    <a href="{{ route('student.courses.continue', $course) }}" class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-500">Lanjutkan Belajar</a>
                    <a href="{{ route('student.courses.progress', $course) }}" class="mt-3 block text-center text-sm font-semibold text-slate-400 hover:text-white">Lihat detail progres</a>
                </article>
            @endforeach
        </section>

        <div>{{ $courses->links() }}</div>
    @endif
</div>
@endsection
