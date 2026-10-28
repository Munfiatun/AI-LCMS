@extends('layouts.app')

@php
    $title = 'Validasi Mastery Siswa';
    $breadcrumb = 'Mastery Siswa';
@endphp

@section('content')
<div class="space-y-6">
    <section class="teacher-page-header border p-6 lg:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">

                <h1 class="teacher-page-title mt-4 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Validasi Profil Mastery Siswa</h1>
                <p class="mt-2 text-sm leading-6 theme-body">R2 ditutup hanya jika minimal dua siswa menghasilkan profil mastery yang berbeda dan setiap skor dapat ditelusuri ke evidence diagnostic. Audit ini tidak menetapkan label rendah/sedang/tinggi.</p>
                <span class="teacher-research-badge">R2.4 — Learner Model Acceptance Audit</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('instructor.mastery.index', $selectedCourse ? ['course_id' => $selectedCourse->id] : []) }}" class="teacher-secondary-button rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm font-semibold theme-body theme-muted-surface-hover">Mastery Siswa</a>
                <a href="{{ route('instructor.diagnostics.index', $selectedCourse ? ['course_id' => $selectedCourse->id] : []) }}" class="rounded-xl border theme-accent-border theme-accent-surface px-4 py-2.5 text-sm font-semibold theme-accent-text theme-accent-surface-hover">Diagnostik Awal</a>
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('instructor.mastery.audit') }}" class="rounded-2xl border theme-border theme-surface p-5">
        <label for="course_id" class="mb-2 block text-sm font-semibold theme-body">Course penelitian</label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <select id="course_id" name="course_id" class="min-w-0 flex-1 rounded-xl border theme-border theme-surface px-4 py-3 text-base theme-heading">
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl theme-accent-fill px-5 py-3 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Buka Audit</button>
        </div>
    </form>

    @if($selectedCourse)
        <section class="rounded-3xl border {{ $summary['passed'] ? 'theme-success-border theme-success-surface' : 'theme-warning-border theme-warning-surface' }} p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.2em] {{ $summary['passed'] ? 'theme-success-text' : 'theme-warning-text' }}">R2 Gate Status</p>
                    <h2 class="mt-2 text-2xl font-extrabold theme-heading">{{ $summary['passed'] ? 'PASS — Learner Model Siap' : 'BELUM PASS — Lengkapi Evidence' }}</h2>
                    <p class="mt-2 text-sm theme-body">{{ $summary['passed_gates'] }}/{{ $summary['total_gates'] }} gate terpenuhi untuk {{ $selectedCourse->title }}.</p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div class="rounded-xl border teacher-card p-3 text-center">
                        <div class="text-xl font-extrabold theme-heading">{{ $summary['active_concepts'] }}</div>
                        <div class="text-sm theme-muted">Concept aktif</div>
                    </div>
                    <div class="rounded-xl border teacher-card p-3 text-center">
                        <div class="text-xl font-extrabold theme-heading">{{ $summary['students_with_mastery'] }}</div>
                        <div class="text-sm theme-muted">Siswa observed</div>
                    </div>
                    <div class="rounded-xl border teacher-card p-3 text-center">
                        <div class="text-xl font-extrabold theme-heading">{{ $summary['complete_profiles'] }}</div>
                        <div class="text-sm theme-muted">Profil lengkap</div>
                    </div>
                    <div class="rounded-xl border teacher-card p-3 text-center">
                        <div class="text-xl font-extrabold theme-heading">{{ $summary['distinct_profiles'] }}</div>
                        <div class="text-sm theme-muted">Profil unik</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border teacher-card p-6">
            <h2 class="text-xl font-bold theme-heading">Checklist Acceptance R2</h2>
            <p class="mt-1 text-sm theme-muted">Semua gate harus hijau sebelum R2 ditutup.</p>

            <div class="mt-5 space-y-3">
                @foreach($gates as $gate)
                    <div class="rounded-2xl border p-4 {{ $gate['passed'] ? 'theme-success-border theme-success-surface' : 'theme-warning-border theme-warning-surface' }}">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $gate['passed'] ? 'theme-success-surface theme-success-text' : 'theme-warning-surface theme-warning-text' }}">{{ $gate['passed'] ? '✓' : '!' }}</div>
                            <div>
                                <h3 class="font-bold theme-heading">{{ $gate['title'] }}</h3>
                                <p class="mt-1 text-sm theme-body">{{ $gate['detail'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border teacher-card">
            <div class="border-b theme-border px-6 py-5">
                <h2 class="text-lg font-bold theme-heading">Evidence per Siswa</h2>
                <p class="mt-1 text-sm theme-muted">Profil lengkap berarti seluruh concept aktif memiliki mastery observed.</p>
            </div>

            @if($rows->isEmpty())
                <div class="px-6 py-12 text-center">
                    <h3 class="font-bold theme-heading">Belum ada mastery siswa</h3>
                    <p class="mt-2 text-sm theme-muted">Minta siswa mengerjakan Diagnostic Assessment terlebih dahulu.</p>
                </div>
            @else
                <div class="divide-y divide-slate-800">
                    @foreach($rows as $row)
                        <div class="grid gap-4 px-6 py-5 lg:grid-cols-[1.4fr_0.7fr_0.7fr_0.7fr_auto] lg:items-center">
                            <div>
                                <p class="font-bold theme-heading">{{ $row['student']->name }}</p>
                                <p class="mt-1 text-sm theme-muted">Student ID #{{ $row['student']->id }}</p>
                            </div>
                            <div>
                                <p class="text-sm theme-muted">Concept observed</p>
                                <p class="mt-1 font-bold theme-heading">{{ $row['observed_concepts'] }}/{{ $row['total_concepts'] }}</p>
                            </div>
                            <div>
                                <p class="text-sm theme-muted">Evidence</p>
                                <p class="mt-1 font-bold theme-heading">{{ $row['evidence_count'] }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-sm font-bold {{ $row['complete'] ? 'theme-success-border theme-success-surface theme-success-text' : 'theme-warning-border theme-warning-surface theme-warning-text' }}">{{ $row['complete'] ? 'Profil lengkap' : 'Belum lengkap' }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-sm font-bold {{ $row['explainable'] ? 'theme-accent-border theme-accent-surface theme-accent-text' : 'theme-border theme-surface theme-body' }}">{{ $row['explainable'] ? 'Explainable' : 'Evidence belum lengkap' }}</span>
                            </div>
                            <a href="{{ route('instructor.mastery.show', ['course' => $selectedCourse, 'student' => $row['student']]) }}" class="rounded-xl border theme-accent-border theme-accent-surface px-4 py-2 text-center text-sm font-semibold theme-accent-text theme-accent-surface-hover">Lihat Profil</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <div class="rounded-2xl border teacher-card px-6 py-12 text-center text-sm theme-body">Belum ada course yang dapat diaudit.</div>
    @endif
</div>
@endsection
