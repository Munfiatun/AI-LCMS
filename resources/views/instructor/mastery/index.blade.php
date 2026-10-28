@extends('layouts.app')

@php
    $title = 'Mastery Siswa';
    $breadcrumb = 'Mastery Siswa';
@endphp

@section('content')
<div class="space-y-6">
    <section class="teacher-page-header border p-6 lg:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">

                <h1 class="teacher-page-title mt-4 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Profil Penguasaan Konsep Siswa</h1>
                <p class="mt-2 text-sm leading-6 theme-body">Lihat hasil diagnostic assessment per concept beserta jumlah evidence. Pada tahap R2 ini skor ditampilkan apa adanya tanpa label rendah/sedang/tinggi karena threshold belum divalidasi.</p>
                <span class="teacher-research-badge">R2.3 — Learner Mastery Profile</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('instructor.mastery.audit', ['course_id' => $selectedCourse?->id]) }}" class="rounded-xl border theme-success-border theme-success-surface px-4 py-2.5 text-sm font-semibold theme-success-text">Validasi Mastery</a>
                <a href="{{ route('instructor.diagnostics.index', ['course_id' => $selectedCourse?->id]) }}" class="rounded-xl border theme-accent-border theme-accent-surface px-4 py-2.5 text-sm font-semibold theme-accent-text">Diagnostik Awal</a>
                <a href="{{ route('instructor.learning-concepts.index', ['course_id' => $selectedCourse?->id]) }}" class="rounded-xl border theme-accent-border theme-accent-surface px-4 py-2.5 text-sm font-semibold theme-accent-text">Konsep & Mapping</a>
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('instructor.mastery.index') }}" class="rounded-2xl border theme-border theme-surface p-5">
        <label class="block text-sm font-semibold theme-body">Course penelitian</label>
        <div class="mt-3 flex flex-col gap-3 sm:flex-row">
            <select name="course_id" class="min-w-0 flex-1 rounded-xl border theme-border theme-surface px-4 py-3 text-base theme-heading">
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl theme-accent-fill px-5 py-3 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Buka Course</button>
        </div>
    </form>

    @if($selectedCourse)
        <section class="space-y-4">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] theme-accent-text">Learner Model</p>
                <h2 class="mt-1 text-xl font-bold theme-heading">Siswa dengan Evidence pada {{ $selectedCourse->title }}</h2>
                <p class="mt-1 text-sm theme-muted">Siswa baru muncul setelah minimal satu diagnostic attempt menghasilkan mastery evidence.</p>
            </div>

            @forelse($students as $row)
                <article class="rounded-2xl border teacher-card p-5">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold theme-heading">{{ $row['student']->name }}</h3>
                            <p class="mt-1 text-sm theme-muted">{{ $row['student']->email }}</p>
                            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                                <span class="rounded-full border theme-border theme-surface px-3 py-1 theme-body">{{ $row['concept_count'] }} concept observed</span>
                                <span class="rounded-full border theme-border theme-surface px-3 py-1 theme-body">{{ $row['evidence_count'] }} evidence</span>
                                <span class="rounded-full border theme-accent-border theme-accent-surface px-3 py-1 theme-accent-text">Rata-rata deskriptif: {{ $row['average_mastery'] !== null ? number_format($row['average_mastery'], 2) . '%' : '—' }}</span>
                            </div>
                        </div>
                        <a href="{{ route('instructor.mastery.show', ['course' => $selectedCourse, 'student' => $row['student']]) }}" class="inline-flex justify-center rounded-xl theme-accent-fill px-5 py-3 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Lihat Profil Mastery</a>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border teacher-card px-6 py-12 text-center">
                    <h3 class="text-base font-bold theme-heading">Belum ada mastery evidence</h3>
                    <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 theme-muted">Publikasikan Diagnostic Assessment, lalu minta siswa mengerjakannya. Setelah submit, profil mastery akan muncul otomatis di sini.</p>
                </div>
            @endforelse
        </section>
    @endif
</div>
@endsection
