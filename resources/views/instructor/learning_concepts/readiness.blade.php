@extends('layouts.app')

@php
    $title = 'Kesiapan Konsep & Mapping';
    $breadcrumb = 'Pengaturan Adaptif';
@endphp

@section('content')
<div class="space-y-7">
    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-4xl">

                <h1 class="teacher-page-title mt-3 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Kesiapan Konsep & Mapping</h1>
                <p class="mt-3 text-base leading-7 theme-body">Periksa apakah domain model sudah cukup lengkap untuk ditutup sebagai R1 sebelum masuk ke Learner Model pada R2.</p>
                <p class="mt-2 text-sm leading-6 theme-body">Audit ini belum menghitung mastery. Soal course diperlakukan sebagai kandidat item diagnostik sampai set diagnostik resmi ditentukan pada R2.</p>
                <span class="teacher-research-badge">R1.4 — Acceptance Audit</span>
            </div>

            <a href="{{ route('instructor.learning-concepts.index', $selectedCourse ? ['course_id' => $selectedCourse->id] : []) }}" class="teacher-secondary-button inline-flex items-center justify-center rounded-xl border theme-border theme-muted-surface px-4 py-2.5 text-sm font-bold theme-heading transition-colors theme-muted-surface-hover">← Kembali ke Domain Model</a>
        </div>
    </section>

    <section class="rounded-2xl border teacher-card p-5 sm:p-6">
        <form method="GET" action="{{ route('instructor.learning-concepts.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <input type="hidden" name="readiness" value="1">
            <div class="flex-1">
                <label for="course_id" class="mb-2 block text-sm font-semibold theme-body">Kursus yang diaudit</label>
                <select id="course_id" name="course_id" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base theme-heading">
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl theme-success-fill px-5 py-3 text-sm font-bold teacher-on-solid transition-colors theme-success-fill-hover">Jalankan Audit</button>
        </form>
    </section>

    @if(!$selectedCourse || !$readiness)
        <section class="rounded-2xl border teacher-card p-8 text-center">
            <p class="text-lg font-bold theme-heading">Belum ada kursus yang dapat diaudit.</p>
        </section>
    @else
        <section class="rounded-2xl border {{ $readiness['passed'] ? 'theme-success-border theme-success-surface' : 'theme-warning-border theme-warning-surface' }} p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] {{ $readiness['passed'] ? 'theme-success-text' : 'theme-warning-text' }}">R1 Gate Status</p>
                    <h2 class="mt-2 text-2xl font-extrabold theme-heading">{{ $readiness['passed'] ? 'PASS — Domain Model Siap' : 'BELUM PASS — Lengkapi Checklist' }}</h2>
                    <p class="mt-2 text-sm leading-6 theme-body">{{ $selectedCourse->title }}</p>
                </div>
                <div class="rounded-xl border {{ $readiness['passed'] ? 'theme-success-border theme-success-surface theme-success-text' : 'theme-warning-border theme-warning-surface theme-warning-text' }} px-4 py-3 text-sm font-bold">
                    {{ collect($readiness['checks'])->where('passed', true)->count() }}/{{ count($readiness['checks']) }} gate terpenuhi
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            @foreach([
                ['label' => 'Concept Aktif', 'value' => $readiness['metrics']['active_concepts']],
                ['label' => 'Concept + Materi', 'value' => $readiness['metrics']['concepts_with_materials']],
                ['label' => 'Concept + Soal', 'value' => $readiness['metrics']['concepts_with_questions']],
                ['label' => 'Total Soal Course', 'value' => $readiness['metrics']['total_questions']],
                ['label' => 'Soal Terpetakan', 'value' => $readiness['metrics']['mapped_questions']],
            ] as $metric)
                <div class="rounded-2xl border teacher-card p-4">
                    <div class="text-2xl font-extrabold theme-heading">{{ $metric['value'] }}</div>
                    <div class="mt-1 text-sm theme-body">{{ $metric['label'] }}</div>
                </div>
            @endforeach
        </section>

        <section class="rounded-2xl border teacher-card p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-xl font-extrabold theme-heading">Checklist Acceptance R1</h2>
                <p class="mt-1 text-sm theme-body">Empat gate berikut harus hijau sebelum R1 ditutup.</p>
            </div>

            <div class="space-y-3">
                @foreach($readiness['checks'] as $check)
                    <div class="flex gap-4 rounded-xl border {{ $check['passed'] ? 'theme-success-border theme-success-surface' : 'theme-warning-border theme-warning-surface' }} p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $check['passed'] ? 'theme-success-surface theme-success-text' : 'theme-warning-surface theme-warning-text' }} text-base font-extrabold">
                            {{ $check['passed'] ? '✓' : '!' }}
                        </div>
                        <div>
                            <h3 class="text-base font-bold theme-heading">{{ $check['label'] }}</h3>
                            <p class="mt-1 text-sm leading-6 theme-body">{{ $check['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if($readiness['warnings']['concepts_without_questions']->isNotEmpty())
            <section class="rounded-2xl border theme-accent-border theme-accent-surface p-5 sm:p-6">
                <h2 class="text-lg font-extrabold theme-heading">Catatan Evidence untuk R2</h2>
                <p class="mt-2 text-sm leading-6 theme-body">Concept berikut belum mempunyai soal: <strong class="theme-accent-text">{{ $readiness['warnings']['concepts_without_questions']->pluck('code')->implode(', ') }}</strong>. Ini belum menjadi gate wajib R1, tetapi sebaiknya dilengkapi sebelum diagnostic mastery pada R2 agar setiap concept memiliki evidence yang dapat diukur.</p>
            </section>
        @endif

        <section class="rounded-2xl border teacher-card p-5 sm:p-6">
            <h2 class="text-lg font-extrabold theme-heading">Target Concept Unit Penelitian</h2>
            <p class="mt-2 text-sm leading-6 theme-body">Untuk unit Struktur Kontrol Pemrograman, gunakan struktur ini sebagai draft penelitian. Parent LO dan prerequisite tetap perlu divalidasi secara pedagogis sebelum dipakai pada eksperimen.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b theme-border theme-body">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Kode</th>
                            <th class="px-3 py-3 font-semibold">Concept</th>
                            <th class="px-3 py-3 font-semibold">Prerequisite draft</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 theme-heading">
                        <tr><td class="px-3 py-3 font-bold theme-accent-text">C01</td><td class="px-3 py-3">Variabel dan Tipe Data</td><td class="px-3 py-3 theme-body">—</td></tr>
                        <tr><td class="px-3 py-3 font-bold theme-accent-text">C02</td><td class="px-3 py-3">Operator dan Ekspresi</td><td class="px-3 py-3 theme-body">C01</td></tr>
                        <tr><td class="px-3 py-3 font-bold theme-accent-text">C03</td><td class="px-3 py-3">Percabangan</td><td class="px-3 py-3 theme-body">C01, C02</td></tr>
                        <tr><td class="px-3 py-3 font-bold theme-accent-text">C04</td><td class="px-3 py-3">Perulangan</td><td class="px-3 py-3 theme-body">C01, C02</td></tr>
                        <tr><td class="px-3 py-3 font-bold theme-accent-text">C05</td><td class="px-3 py-3">Nested Control Structure</td><td class="px-3 py-3 theme-body">C03, C04</td></tr>
                        <tr><td class="px-3 py-3 font-bold theme-accent-text">C06</td><td class="px-3 py-3">Program Sederhana</td><td class="px-3 py-3 theme-body">C03, C04, C05</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
