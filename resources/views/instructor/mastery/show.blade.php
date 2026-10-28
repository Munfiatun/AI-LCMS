@extends('layouts.app')

@php
    $title = 'Detail Mastery';
    $breadcrumb = 'Detail Mastery';
@endphp

@section('content')
<div class="space-y-6">
    <section class="teacher-page-header border p-6 lg:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="{{ route('instructor.mastery.index', ['course_id' => $course->id]) }}" class="text-sm font-semibold theme-accent-text theme-accent-text-hover">← Kembali ke Mastery Profile</a>

                <h1 class="teacher-page-title mt-4 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">{{ $student->name }}</h1>
                <p class="mt-1 text-sm theme-muted">{{ $course->title }} · {{ $student->email }}</p>
                <p class="mt-3 max-w-3xl text-sm leading-6 theme-body">Skor berikut berasal dari evidence diagnostic yang terhubung ke Concept Mapping. Belum ada klasifikasi rendah/sedang/tinggi atau rekomendasi adaptif pada tahap R2.</p>
                <span class="teacher-research-badge">R2.3 — Explainable Learner Model</span>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border teacher-card p-4">
                <p class="text-sm font-semibold theme-muted">Concept Observed</p>
                <p class="mt-2 text-2xl font-extrabold theme-heading">{{ $summary['observed_concepts'] }}/{{ $summary['total_concepts'] }}</p>
            </div>
            <div class="rounded-2xl border teacher-card p-4">
                <p class="text-sm font-semibold theme-muted">Rata-rata Deskriptif</p>
                <p class="mt-2 text-2xl font-extrabold theme-accent-text">{{ $summary['average_mastery'] !== null ? number_format($summary['average_mastery'], 2) . '%' : '—' }}</p>
            </div>
            <div class="rounded-2xl border teacher-card p-4">
                <p class="text-sm font-semibold theme-muted">Total Evidence</p>
                <p class="mt-2 text-2xl font-extrabold theme-heading">{{ $summary['evidence_count'] }}</p>
            </div>
            <div class="rounded-2xl border teacher-card p-4">
                <p class="text-sm font-semibold theme-muted">History Records</p>
                <p class="mt-2 text-2xl font-extrabold theme-heading">{{ $summary['history_count'] }}</p>
            </div>
        </div>
    </section>

    <section class="space-y-4">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] theme-accent-text">Concept Mastery</p>
            <h2 class="mt-1 text-xl font-bold theme-heading">Profil Penguasaan per Concept</h2>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach($profile as $row)
                @php
                    $score = $row['score'];
                    $width = $score === null ? 0 : max(0, min(100, $score));
                @endphp
                <article class="rounded-2xl border teacher-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-lg border theme-accent-border theme-accent-surface px-2 py-1 text-sm font-bold theme-accent-text">{{ $row['concept']->code }}</span>
                                @if($row['concept']->learningObjective)
                                    <span class="text-sm theme-muted">{{ $row['concept']->learningObjective->code }}</span>
                                @endif
                            </div>
                            <h3 class="mt-3 text-base font-bold theme-heading">{{ $row['concept']->name }}</h3>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-2xl font-extrabold theme-heading">{{ $score !== null ? number_format($score, 2) . '%' : '—' }}</p>
                            <p class="mt-1 text-sm theme-muted">{{ $row['evidence_count'] }} evidence</p>
                        </div>
                    </div>

                    <div class="mt-5 h-3 overflow-hidden rounded-full border teacher-card" role="progressbar" aria-label="Mastery {{ $row['concept']->code }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $score ?? 0 }}">
                        <div class="h-full rounded-full theme-accent-fill" style="width: {{ $width }}%"></div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-sm theme-muted">
                        <span>{{ $row['mastery']?->status ?? 'belum_observed' }}</span>
                        <span>{{ $row['last_evidence_at'] ? 'Evidence terakhir ' . $row['last_evidence_at']->format('d M Y H:i') : 'Belum ada evidence' }}</span>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border teacher-card">
        <div class="border-b theme-border px-6 py-5">
            <p class="text-sm font-bold uppercase tracking-[0.18em] theme-accent-text">Audit Trail</p>
            <h2 class="mt-1 text-xl font-bold theme-heading">Mastery History & Evidence</h2>
            <p class="mt-1 text-sm theme-muted">Setiap perubahan mastery dapat ditelusuri ke attempt diagnostik dan versi model yang digunakan.</p>
        </div>

        @if($history->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="theme-surface text-left text-sm uppercase tracking-wide theme-muted">
                        <tr>
                            <th class="px-6 py-3">Concept</th>
                            <th class="px-6 py-3">Sebelum</th>
                            <th class="px-6 py-3">Sesudah</th>
                            <th class="px-6 py-3">Evidence</th>
                            <th class="px-6 py-3">Model</th>
                            <th class="px-6 py-3">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 theme-body">
                        @foreach($history as $item)
                            <tr>
                                <td class="px-6 py-4">
                                    <span class="font-semibold theme-heading">{{ $item->concept->code }}</span>
                                    <span class="ml-1 theme-muted">{{ $item->concept->name }}</span>
                                </td>
                                <td class="px-6 py-4">{{ $item->before_score !== null ? number_format($item->before_score, 2) . '%' : '—' }}</td>
                                <td class="px-6 py-4 font-semibold theme-heading">{{ number_format($item->after_score, 2) }}%</td>
                                <td class="px-6 py-4">
                                    <div>{{ $item->evidence_type }}</div>
                                    <div class="mt-1 text-sm theme-muted">Attempt #{{ $item->evidence_id }} · {{ $item->evidence_count }} item evidence</div>
                                </td>
                                <td class="px-6 py-4 text-sm theme-body">{{ $item->model_version }}</td>
                                <td class="px-6 py-4 text-sm theme-muted">{{ $item->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center text-sm theme-muted">Belum ada history mastery.</div>
        @endif
    </section>
</div>
@endsection
