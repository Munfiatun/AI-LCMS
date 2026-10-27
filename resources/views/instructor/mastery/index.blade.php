@extends('layouts.app')

@php
    $title = 'Mastery Profile';
    $breadcrumb = 'Mastery Profile';
@endphp

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 lg:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full border border-violet-500/30 bg-violet-500/10 px-3 py-1 text-xs font-bold text-violet-300">R2.3 — Learner Mastery Profile</span>
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Profil Penguasaan Konsep Siswa</h1>
                <p class="mt-2 text-sm leading-6 text-slate-400">Lihat hasil diagnostic assessment per concept beserta jumlah evidence. Pada tahap R2 ini skor ditampilkan apa adanya tanpa label rendah/sedang/tinggi karena threshold belum divalidasi.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('instructor.diagnostics.index', ['course_id' => $selectedCourse?->id]) }}" class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2.5 text-sm font-semibold text-indigo-200">Diagnostic Assessment</a>
                <a href="{{ route('instructor.learning-concepts.index', ['course_id' => $selectedCourse?->id]) }}" class="rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-4 py-2.5 text-sm font-semibold text-cyan-200">Domain Model</a>
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('instructor.mastery.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
        <label class="block text-sm font-semibold text-slate-300">Course penelitian</label>
        <div class="mt-3 flex flex-col gap-3 sm:flex-row">
            <select name="course_id" class="min-w-0 flex-1 rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-slate-100">
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-violet-600 px-5 py-3 text-sm font-bold text-white hover:bg-violet-500">Buka Course</button>
        </div>
    </form>

    @if($selectedCourse)
        <section class="space-y-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-violet-300">Learner Model</p>
                <h2 class="mt-1 text-xl font-bold text-white">Siswa dengan Evidence pada {{ $selectedCourse->title }}</h2>
                <p class="mt-1 text-sm text-slate-500">Siswa baru muncul setelah minimal satu diagnostic attempt menghasilkan mastery evidence.</p>
            </div>

            @forelse($students as $row)
                <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-white">{{ $row['student']->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $row['student']->email }}</p>
                            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-slate-300">{{ $row['concept_count'] }} concept observed</span>
                                <span class="rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-slate-300">{{ $row['evidence_count'] }} evidence</span>
                                <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-3 py-1 text-violet-200">Rata-rata deskriptif: {{ $row['average_mastery'] !== null ? number_format($row['average_mastery'], 2) . '%' : '—' }}</span>
                            </div>
                        </div>
                        <a href="{{ route('instructor.mastery.show', ['course' => $selectedCourse, 'student' => $row['student']]) }}" class="inline-flex justify-center rounded-xl bg-violet-600 px-5 py-3 text-sm font-bold text-white hover:bg-violet-500">Lihat Profil Mastery</a>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-6 py-12 text-center">
                    <h3 class="text-base font-bold text-white">Belum ada mastery evidence</h3>
                    <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500">Publikasikan Diagnostic Assessment, lalu minta siswa mengerjakannya. Setelah submit, profil mastery akan muncul otomatis di sini.</p>
                </div>
            @endforelse
        </section>
    @endif
</div>
@endsection
