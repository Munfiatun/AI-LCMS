@extends('layouts.app')

@php
    $title = 'Portal Siswa';
    $breadcrumb = 'Student Dashboard';
@endphp

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-emerald-950/40 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Learning Overview
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Selamat Datang, {{ $user->name }}</h1>
                <p class="text-sm text-slate-400 mt-1">Pantau progres, lanjutkan materi yang tertunda, dan lihat performa quiz dari kursus yang sedang Anda ikuti.</p>
            </div>
            <a href="{{ route('student.courses.explore') }}" class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                Jelajahi Kursus
            </a>
        </div>
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kursus Diikuti</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['enrolled_courses'] }}</span>
                <span class="text-xs text-emerald-400">{{ $stats['completed_courses'] }} tuntas</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-Rata Progres</span>
            <p class="mt-3 text-3xl font-extrabold text-white">{{ number_format($stats['average_progress'], 1) }}%</p>
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-3">
                <div class="bg-gradient-to-r from-emerald-500 to-cyan-400 h-1.5 rounded-full" style="width: {{ max(0, min(100, $stats['average_progress'])) }}%"></div>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Materi Selesai</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold text-white">{{ $stats['completed_materials'] }}<span class="text-lg text-slate-500">/{{ $stats['total_materials'] }}</span></span>
                <span class="text-xs text-purple-400">Materi published</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-Rata Quiz</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold {{ $stats['submitted_attempts'] > 0 ? 'text-amber-300' : 'text-white' }}">{{ number_format($stats['average_score'], 1) }}%</span>
                <span class="text-xs text-cyan-400">{{ $stats['passed_quizzes'] }}/{{ $stats['total_quizzes'] }} lulus</span>
            </div>
        </div>
    </div>

    @if($courseRows->isNotEmpty())
        <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-300">Next Learning Action</p>
                    <h3 class="mt-1 text-lg font-bold text-white">Fokus Belajar Berikutnya</h3>
                    <p class="mt-1 text-xs text-slate-500">Saran di bawah adalah ringkasan umum dari progres materi dan quiz. Untuk rekomendasi berbasis mastery dan prasyarat, buka Adaptive Learning.</p>
                </div>
                <a href="{{ route('student.adaptive-recommendations.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-4 py-2.5 text-sm font-bold text-cyan-300 transition-colors hover:bg-cyan-500/20 hover:text-cyan-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5m4.75-11.396A2.25 2.25 0 0112 1.5h0a2.25 2.25 0 012.25 1.604v5.714c0 .597.237 1.169.659 1.591L19 14.5m-9.25-11.396h4.5M5 14.5h14m-14 0v2.25A2.25 2.25 0 007.25 19h9.5A2.25 2.25 0 0019 16.75V14.5M9 19v1.5m6-1.5v1.5" /></svg>
                    Lihat Rekomendasi Adaptif
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                @foreach($courseRows as $row)
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-white">{{ $row['course']->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">Progres {{ number_format($row['enrollment']->progress_percentage, 0) }}% &bull; Quiz {{ number_format($row['average_quiz_score'], 1) }}%</p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide
                                {{ $row['recommendation_type'] === 'complete' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : ($row['recommendation_type'] === 'quiz' ? 'border-amber-500/30 bg-amber-500/10 text-amber-300' : 'border-indigo-500/30 bg-indigo-500/10 text-indigo-300') }}">
                                {{ $row['recommendation_type'] === 'complete' ? 'Tuntas' : ($row['recommendation_type'] === 'quiz' ? 'Quiz' : 'Lanjut Belajar') }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-slate-300">{{ $row['recommendation'] }}</p>
                        <div class="mt-4 flex justify-end">
                            <a href="{{ route('student.courses.continue', $row['course']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-300 hover:text-emerald-200">
                                Buka Kursus
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div id="my-courses" class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl">
        <div class="mb-4">
            <h3 class="text-lg font-bold text-white">Kelas Saya</h3>
            <p class="mt-1 text-xs text-slate-500">Ringkasan progres materi dan assessment untuk setiap kursus.</p>
        </div>

        @if($courseRows->isEmpty())
            <div class="p-8 rounded-xl bg-slate-950/60 border border-slate-800/80 text-center flex flex-col items-center justify-center">
                <div class="w-12 h-12 rounded-full bg-slate-800/60 text-slate-400 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </div>
                <p class="text-sm font-medium text-slate-300">Belum ada kelas yang diikuti.</p>
                <p class="mt-1 text-xs text-slate-500">Jelajahi kursus yang tersedia untuk mulai belajar.</p>
                <a href="{{ route('student.courses.explore') }}" class="mt-4 px-4 py-2 text-sm text-white bg-emerald-600 rounded-lg hover:bg-emerald-500">Jelajahi Kelas</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($courseRows as $row)
                    <div class="p-5 bg-slate-950/50 rounded-xl border border-slate-800">
                        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-5">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-lg font-bold text-white">
                                        <a href="{{ route('courses.show', $row['course']->slug) }}" class="hover:text-emerald-300 transition-colors">{{ $row['course']->title }}</a>
                                    </h4>
                                    @if($row['enrollment']->status === 'completed')
                                        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-300">Tuntas</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $row['course']->category?->name ?? 'Uncategorized' }}</p>

                                <div class="mt-4 w-full bg-slate-800 rounded-full h-2">
                                    <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ max(0, min(100, (float) $row['enrollment']->progress_percentage)) }}%"></div>
                                </div>
                                <div class="mt-2 flex justify-between text-xs text-slate-400">
                                    <span>{{ number_format($row['enrollment']->progress_percentage, 0) }}% Selesai</span>
                                    <span>{{ $row['completed_materials'] }}/{{ $row['total_materials'] }} materi selesai</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 xl:w-64">
                                <div class="rounded-lg border border-slate-800 bg-slate-900/70 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Quiz Lulus</p>
                                    <p class="mt-1 text-lg font-bold text-white">{{ $row['passed_quizzes'] }}/{{ $row['total_quizzes'] }}</p>
                                </div>
                                <div class="rounded-lg border border-slate-800 bg-slate-900/70 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Rata-Rata</p>
                                    <p class="mt-1 text-lg font-bold text-white">{{ number_format($row['average_quiz_score'], 1) }}%</p>
                                </div>
                            </div>

                            <a href="{{ route('student.courses.continue', $row['course']) }}" class="shrink-0 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors inline-flex items-center justify-center gap-2">
                                Lanjutkan
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
