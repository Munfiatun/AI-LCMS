@extends('layouts.app')

@php
    $title = 'R1 Readiness Audit';
    $breadcrumb = 'Adaptive Learning Foundation';
@endphp

@section('content')
<div class="space-y-7">
    <section class="rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-emerald-950/30 to-slate-900 p-6 shadow-xl sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-4xl">
                <span class="inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-sm font-semibold text-emerald-200">R1.4 — Acceptance Audit</span>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Domain Model Readiness</h1>
                <p class="mt-3 text-base leading-7 text-slate-300">Periksa apakah domain model sudah cukup lengkap untuk ditutup sebagai R1 sebelum masuk ke Learner Model pada R2.</p>
                <p class="mt-2 text-sm leading-6 text-slate-400">Audit ini belum menghitung mastery. Soal course diperlakukan sebagai kandidat item diagnostik sampai set diagnostik resmi ditentukan pada R2.</p>
            </div>

            <a href="{{ route('instructor.learning-concepts.index', $selectedCourse ? ['course_id' => $selectedCourse->id] : []) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-bold text-slate-100 transition-colors hover:bg-slate-700">← Kembali ke Domain Model</a>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
        <form method="GET" action="{{ route('instructor.learning-concepts.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <input type="hidden" name="readiness" value="1">
            <div class="flex-1">
                <label for="course_id" class="mb-2 block text-sm font-semibold text-slate-300">Kursus yang diaudit</label>
                <select id="course_id" name="course_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-emerald-500">Jalankan Audit</button>
        </form>
    </section>

    @if(!$selectedCourse || !$readiness)
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-8 text-center">
            <p class="text-lg font-bold text-white">Belum ada kursus yang dapat diaudit.</p>
        </section>
    @else
        <section class="rounded-2xl border {{ $readiness['passed'] ? 'border-emerald-500/40 bg-emerald-950/25' : 'border-amber-500/30 bg-amber-950/20' }} p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] {{ $readiness['passed'] ? 'text-emerald-300' : 'text-amber-300' }}">R1 Gate Status</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-white">{{ $readiness['passed'] ? 'PASS — Domain Model Siap' : 'BELUM PASS — Lengkapi Checklist' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">{{ $selectedCourse->title }}</p>
                </div>
                <div class="rounded-xl border {{ $readiness['passed'] ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'border-amber-500/30 bg-amber-500/10 text-amber-200' }} px-4 py-3 text-sm font-bold">
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
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                    <div class="text-2xl font-extrabold text-white">{{ $metric['value'] }}</div>
                    <div class="mt-1 text-sm text-slate-400">{{ $metric['label'] }}</div>
                </div>
            @endforeach
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="text-xl font-extrabold text-white">Checklist Acceptance R1</h2>
                <p class="mt-1 text-sm text-slate-400">Empat gate berikut harus hijau sebelum R1 ditutup.</p>
            </div>

            <div class="space-y-3">
                @foreach($readiness['checks'] as $check)
                    <div class="flex gap-4 rounded-xl border {{ $check['passed'] ? 'border-emerald-500/20 bg-emerald-950/20' : 'border-amber-500/20 bg-amber-950/20' }} p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $check['passed'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }} text-base font-extrabold">
                            {{ $check['passed'] ? '✓' : '!' }}
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">{{ $check['label'] }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-400">{{ $check['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if($readiness['warnings']['concepts_without_questions']->isNotEmpty())
            <section class="rounded-2xl border border-sky-500/20 bg-sky-950/20 p-5 sm:p-6">
                <h2 class="text-lg font-extrabold text-white">Catatan Evidence untuk R2</h2>
                <p class="mt-2 text-sm leading-6 text-slate-300">Concept berikut belum mempunyai soal: <strong class="text-sky-200">{{ $readiness['warnings']['concepts_without_questions']->pluck('code')->implode(', ') }}</strong>. Ini belum menjadi gate wajib R1, tetapi sebaiknya dilengkapi sebelum diagnostic mastery pada R2 agar setiap concept memiliki evidence yang dapat diukur.</p>
            </section>
        @endif

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
            <h2 class="text-lg font-extrabold text-white">Target Concept Unit Penelitian</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">Untuk unit Struktur Kontrol Pemrograman, gunakan struktur ini sebagai draft penelitian. Parent LO dan prerequisite tetap perlu divalidasi secara pedagogis sebelum dipakai pada eksperimen.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-800 text-slate-400">
                        <tr>
                            <th class="px-3 py-3 font-semibold">Kode</th>
                            <th class="px-3 py-3 font-semibold">Concept</th>
                            <th class="px-3 py-3 font-semibold">Prerequisite draft</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-200">
                        <tr><td class="px-3 py-3 font-bold text-cyan-300">C01</td><td class="px-3 py-3">Variabel dan Tipe Data</td><td class="px-3 py-3 text-slate-400">—</td></tr>
                        <tr><td class="px-3 py-3 font-bold text-cyan-300">C02</td><td class="px-3 py-3">Operator dan Ekspresi</td><td class="px-3 py-3 text-slate-400">C01</td></tr>
                        <tr><td class="px-3 py-3 font-bold text-cyan-300">C03</td><td class="px-3 py-3">Percabangan</td><td class="px-3 py-3 text-slate-400">C01, C02</td></tr>
                        <tr><td class="px-3 py-3 font-bold text-cyan-300">C04</td><td class="px-3 py-3">Perulangan</td><td class="px-3 py-3 text-slate-400">C01, C02</td></tr>
                        <tr><td class="px-3 py-3 font-bold text-cyan-300">C05</td><td class="px-3 py-3">Nested Control Structure</td><td class="px-3 py-3 text-slate-400">C03, C04</td></tr>
                        <tr><td class="px-3 py-3 font-bold text-cyan-300">C06</td><td class="px-3 py-3">Program Sederhana</td><td class="px-3 py-3 text-slate-400">C03, C04, C05</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
