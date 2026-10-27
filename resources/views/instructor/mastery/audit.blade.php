@extends('layouts.app')

@php
    $title = 'R2 Acceptance Audit';
    $breadcrumb = 'R2 Acceptance Audit';
@endphp

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 lg:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full border border-indigo-500/30 bg-indigo-500/10 px-3 py-1 text-xs font-bold text-indigo-300">R2.4 — Learner Model Acceptance Audit</span>
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Validasi Profil Mastery Siswa</h1>
                <p class="mt-2 text-sm leading-6 text-slate-400">R2 ditutup hanya jika minimal dua siswa menghasilkan profil mastery yang berbeda dan setiap skor dapat ditelusuri ke evidence diagnostic. Audit ini tidak menetapkan label rendah/sedang/tinggi.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('instructor.mastery.index', $selectedCourse ? ['course_id' => $selectedCourse->id] : []) }}" class="rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Mastery Profile</a>
                <a href="{{ route('instructor.diagnostics.index', $selectedCourse ? ['course_id' => $selectedCourse->id] : []) }}" class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2.5 text-sm font-semibold text-indigo-200 hover:bg-indigo-500/20">Diagnostic Assessment</a>
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('instructor.mastery.audit') }}" class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
        <label for="course_id" class="mb-2 block text-sm font-semibold text-slate-300">Course penelitian</label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <select id="course_id" name="course_id" class="min-w-0 flex-1 rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-500">Buka Audit</button>
        </div>
    </form>

    @if($selectedCourse)
        <section class="rounded-3xl border {{ $summary['passed'] ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-amber-500/30 bg-amber-500/5' }} p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] {{ $summary['passed'] ? 'text-emerald-300' : 'text-amber-300' }}">R2 Gate Status</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-white">{{ $summary['passed'] ? 'PASS — Learner Model Siap' : 'BELUM PASS — Lengkapi Evidence' }}</h2>
                    <p class="mt-2 text-sm text-slate-400">{{ $summary['passed_gates'] }}/{{ $summary['total_gates'] }} gate terpenuhi untuk {{ $selectedCourse->title }}.</p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3 text-center">
                        <div class="text-xl font-extrabold text-white">{{ $summary['active_concepts'] }}</div>
                        <div class="text-[10px] text-slate-500">Concept aktif</div>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3 text-center">
                        <div class="text-xl font-extrabold text-white">{{ $summary['students_with_mastery'] }}</div>
                        <div class="text-[10px] text-slate-500">Siswa observed</div>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3 text-center">
                        <div class="text-xl font-extrabold text-white">{{ $summary['complete_profiles'] }}</div>
                        <div class="text-[10px] text-slate-500">Profil lengkap</div>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3 text-center">
                        <div class="text-xl font-extrabold text-white">{{ $summary['distinct_profiles'] }}</div>
                        <div class="text-[10px] text-slate-500">Profil unik</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6">
            <h2 class="text-xl font-bold text-white">Checklist Acceptance R2</h2>
            <p class="mt-1 text-sm text-slate-500">Semua gate harus hijau sebelum R2 ditutup.</p>

            <div class="mt-5 space-y-3">
                @foreach($gates as $gate)
                    <div class="rounded-2xl border p-4 {{ $gate['passed'] ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-amber-500/30 bg-amber-500/5' }}">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $gate['passed'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }}">{{ $gate['passed'] ? '✓' : '!' }}</div>
                            <div>
                                <h3 class="font-bold text-white">{{ $gate['title'] }}</h3>
                                <p class="mt-1 text-sm text-slate-400">{{ $gate['detail'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70">
            <div class="border-b border-slate-800 px-6 py-5">
                <h2 class="text-lg font-bold text-white">Evidence per Siswa</h2>
                <p class="mt-1 text-sm text-slate-500">Profil lengkap berarti seluruh concept aktif memiliki mastery observed.</p>
            </div>

            @if($rows->isEmpty())
                <div class="px-6 py-12 text-center">
                    <h3 class="font-bold text-white">Belum ada mastery siswa</h3>
                    <p class="mt-2 text-sm text-slate-500">Minta siswa mengerjakan Diagnostic Assessment terlebih dahulu.</p>
                </div>
            @else
                <div class="divide-y divide-slate-800">
                    @foreach($rows as $row)
                        <div class="grid gap-4 px-6 py-5 lg:grid-cols-[1.4fr_0.7fr_0.7fr_0.7fr_auto] lg:items-center">
                            <div>
                                <p class="font-bold text-white">{{ $row['student']->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">Student ID #{{ $row['student']->id }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Concept observed</p>
                                <p class="mt-1 font-bold text-slate-200">{{ $row['observed_concepts'] }}/{{ $row['total_concepts'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Evidence</p>
                                <p class="mt-1 font-bold text-slate-200">{{ $row['evidence_count'] }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $row['complete'] ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/30 bg-amber-500/10 text-amber-300' }}">{{ $row['complete'] ? 'Profil lengkap' : 'Belum lengkap' }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $row['explainable'] ? 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300' : 'border-slate-700 bg-slate-950 text-slate-400' }}">{{ $row['explainable'] ? 'Explainable' : 'Evidence belum lengkap' }}</span>
                            </div>
                            <a href="{{ route('instructor.mastery.show', ['course' => $selectedCourse, 'student' => $row['student']]) }}" class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2 text-center text-sm font-semibold text-indigo-200 hover:bg-indigo-500/20">Lihat Profil</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-6 py-12 text-center text-sm text-slate-400">Belum ada course yang dapat diaudit.</div>
    @endif
</div>
@endsection
