@extends('layouts.app')

@php
    $title = 'Rekomendasi Adaptif';
    $breadcrumb = 'Adaptive Learning';
@endphp

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 lg:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-bold text-cyan-300">R4.3 — Adaptive Learning Path</span>
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Rekomendasi Belajar untuk {{ $student->name }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-400">Sistem menggabungkan recommendation yang mengarah ke concept target yang sama, lalu menentukan satu fokus belajar utama secara deterministik. Langkah berikutnya bersifat sementara dan akan dihitung ulang ketika mastery atau evidence siswa berubah.</p>
            </div>
            <a href="{{ route('student.dashboard') }}" class="rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Kembali ke Dashboard</a>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Rekomendasi Aktif</p>
            <p class="mt-2 text-2xl font-extrabold text-white">{{ $summary['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Perlu Penguatan Ulang</p>
            <p class="mt-2 text-2xl font-extrabold text-amber-300">{{ $summary['remediation'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Reinforcement</p>
            <p class="mt-2 text-2xl font-extrabold text-indigo-300">{{ $summary['reinforcement'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Siap Lanjut / Pengayaan</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-300">{{ $summary['progression'] }}</p>
        </div>
    </section>

    @if($rows->isEmpty())
        <section class="rounded-3xl border border-slate-800 bg-slate-900/70 px-6 py-12 text-center">
            <h2 class="text-lg font-bold text-white">Belum ada rekomendasi adaptif aktif</h2>
            <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500">Selesaikan diagnostic assessment pada course yang diikuti. Setelah profil mastery tersedia dan adaptive engine menghasilkan recommendation, fokus belajar akan muncul di halaman ini.</p>
            <a href="{{ route('student.quizzes.index') }}" class="mt-5 inline-flex rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white hover:bg-cyan-500">Lihat Assessment</a>
        </section>
    @else
        <div class="space-y-6">
            @foreach($learningPaths as $path)
                @php
                    $course = $path['course'];
                    $current = $path['current_step'];
                    $currentRow = $current['representative_row'];
                    $currentRecommendation = $currentRow['recommendation'];
                    $currentTarget = $current['target_concept'];
                    $currentMaterial = $currentRow['recommended_material'];
                @endphp

                <section class="overflow-hidden rounded-3xl border border-cyan-500/20 bg-slate-900/80 shadow-xl">
                    <div class="border-b border-slate-800 px-6 py-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-300">Jalur Prioritas Adaptif</p>
                                <h2 class="mt-1 text-xl font-bold text-white">{{ $course->title }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $path['recommendation_count'] }} recommendation diringkas menjadi {{ $path['unique_target_count'] }} target belajar unik.</p>
                            </div>
                            <span class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1.5 text-xs font-semibold text-slate-400">Path akan dihitung ulang setelah learner state berubah</span>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-5">
                            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-emerald-500 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-slate-950">Fokus Sekarang</span>
                                        <span class="rounded-full border border-slate-700 px-2.5 py-1 text-[10px] font-bold text-slate-400">Langkah {{ $current['position'] }}</span>
                                        <span class="rounded-full border border-cyan-500/20 bg-cyan-500/10 px-2.5 py-1 text-[10px] font-bold text-cyan-300">{{ $currentRow['action_label'] }}</span>
                                    </div>

                                    <h3 class="mt-4 text-xl font-extrabold text-white">{{ $currentTarget?->code }} — {{ $currentTarget?->name }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">{{ $current['priority_reason'] }}</p>

                                    <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
                                        <span>{{ $current['support_count'] }} recommendation mendukung fokus ini</span>
                                        <span>&bull;</span>
                                        <span>Sumber: {{ $current['source_concepts']->pluck('code')->implode(', ') }}</span>
                                    </div>

                                    @if($currentRow['activity_type'] === 'material' && $currentMaterial)
                                        <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                                            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Aktivitas Utama</p>
                                            <p class="mt-1 text-sm font-bold text-white">{{ $currentMaterial->title }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $currentMaterial->section?->title ?? 'Materi Course' }}@if($currentMaterial->duration_minutes) &bull; {{ $currentMaterial->duration_minutes }} menit @endif</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="shrink-0">
                                    @if($currentRow['activity_type'] === 'material' && $currentMaterial)
                                        <a href="{{ route('student.materials.show', [$course, $currentMaterial]) }}" class="inline-flex rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-500">Mulai Fokus Sekarang</a>
                                    @elseif($currentRow['activity_type'] === 'diagnostic')
                                        <a href="{{ route('student.quizzes.index') }}" class="inline-flex rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white hover:bg-cyan-500">Lengkapi Assessment</a>
                                    @else
                                        <a href="{{ route('student.courses.progress', $course) }}" class="inline-flex rounded-xl border border-slate-700 px-5 py-3 text-sm font-bold text-slate-200 hover:bg-slate-800">Buka Progres Kursus</a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($path['queued_steps']->isNotEmpty())
                            <div class="mt-5">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Antrean Sementara</p>
                                        <p class="mt-1 text-xs text-slate-600">Urutan ini bukan jalur tetap; sistem akan menilai ulang setelah fokus sekarang menghasilkan evidence baru.</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    @foreach($path['queued_steps'] as $step)
                                        @php
                                            $stepRow = $step['representative_row'];
                                            $stepTarget = $step['target_concept'];
                                        @endphp
                                        <div class="flex flex-col gap-3 rounded-xl border border-slate-800 bg-slate-950/40 p-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="flex min-w-0 items-start gap-3">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-700 bg-slate-900 text-xs font-bold text-slate-400">{{ $step['position'] }}</span>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-white">{{ $stepTarget?->code }} — {{ $stepTarget?->name }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">{{ $stepRow['action_label'] }} &bull; didukung {{ $step['support_count'] }} recommendation</p>
                                                </div>
                                            </div>
                                            <span class="shrink-0 rounded-full border border-slate-700 px-2.5 py-1 text-[10px] font-bold uppercase text-slate-500">Menunggu evaluasi ulang</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach

            @foreach($courseGroups as $group)
                @php
                    $first = $group->first();
                    $course = $first['recommendation']->course;
                @endphp
                <section class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70">
                    <div class="flex flex-col gap-3 border-b border-slate-800 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Detail Keputusan Engine</p>
                            <h2 class="mt-1 text-xl font-bold text-white">{{ $course->title }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $group->count() }} recommendation aktif tetap ditampilkan untuk transparansi dan audit.</p>
                        </div>
                        <a href="{{ route('student.courses.progress', $course) }}" class="rounded-xl border border-slate-700 px-4 py-2.5 text-center text-sm font-semibold text-slate-300 hover:bg-slate-800">Lihat Progres Kursus</a>
                    </div>

                    <div class="divide-y divide-slate-800">
                        @foreach($group as $row)
                            @php
                                $recommendation = $row['recommendation'];
                                $source = $recommendation->sourceConcept;
                                $target = $recommendation->targetConcept;
                                $material = $row['recommended_material'];
                            @endphp
                            <article class="px-6 py-5">
                                <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-start">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2.5 py-1 text-[11px] font-bold text-cyan-300">{{ $row['action_label'] }}</span>
                                            @if($row['is_prerequisite_redirect'])
                                                <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-[11px] font-bold text-amber-300">Fokus Prasyarat</span>
                                            @endif
                                        </div>

                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Konsep yang Dievaluasi</p>
                                                <p class="mt-2 font-bold text-white">{{ $source?->code }} — {{ $source?->name }}</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Fokus yang Direkomendasikan</p>
                                                <p class="mt-2 font-bold text-white">{{ $target?->code }} — {{ $target?->name }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                                            <p class="text-xs font-bold text-slate-300">Mengapa rekomendasi ini muncul?</p>
                                            <p class="mt-2 text-sm leading-6 text-slate-400">{{ $row['reason_text'] }}</p>
                                        </div>

                                        @if($row['activity_type'] === 'material' && $material)
                                            <div class="mt-4 flex flex-col gap-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Aktivitas yang Disarankan</p>
                                                    <p class="mt-1 text-sm font-bold text-white">{{ $material->title }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">{{ $material->section?->title ?? 'Materi Course' }}@if($material->duration_minutes) &bull; {{ $material->duration_minutes }} menit @endif</p>
                                                </div>
                                                <a href="{{ route('student.materials.show', [$course, $material]) }}" class="shrink-0 rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-bold text-white hover:bg-emerald-500">Mulai Materi</a>
                                            </div>
                                        @elseif($row['activity_type'] === 'diagnostic')
                                            <div class="mt-4 flex flex-col gap-4 rounded-xl border border-cyan-500/20 bg-cyan-500/5 p-4 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <p class="text-[10px] font-bold uppercase tracking-wide text-cyan-300">Aktivitas yang Disarankan</p>
                                                    <p class="mt-1 text-sm font-bold text-white">Lengkapi assessment untuk menambah evidence penguasaan konsep.</p>
                                                </div>
                                                <a href="{{ route('student.quizzes.index') }}" class="shrink-0 rounded-xl bg-cyan-600 px-4 py-2.5 text-center text-sm font-bold text-white hover:bg-cyan-500">Lihat Assessment</a>
                                            </div>
                                        @else
                                            <div class="mt-4 flex flex-col gap-4 rounded-xl border border-slate-700 bg-slate-950/50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Aktivitas yang Disarankan</p>
                                                    <p class="mt-1 text-sm font-bold text-white">Belum ada material published yang dipetakan ke konsep target ini.</p>
                                                    <p class="mt-1 text-xs text-slate-500">Buka progres course untuk melihat aktivitas belajar yang tersedia.</p>
                                                </div>
                                                <a href="{{ route('student.courses.progress', $course) }}" class="shrink-0 rounded-xl border border-slate-700 px-4 py-2.5 text-center text-sm font-bold text-slate-200 hover:bg-slate-800">Buka Progres Kursus</a>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-[190px] rounded-xl border border-slate-800 bg-slate-950/60 p-4 text-xs">
                                        <p class="font-bold uppercase tracking-wide text-slate-500">Audit Detail</p>
                                        <dl class="mt-3 space-y-2">
                                            <div>
                                                <dt class="text-slate-600">Reason code</dt>
                                                <dd class="mt-0.5 break-all font-mono text-slate-300">{{ $recommendation->reason_code }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-slate-600">Algorithm</dt>
                                                <dd class="mt-0.5 font-mono text-slate-300">{{ ($recommendation->algorithmVersion?->code ?? 'unknown') . '@' . ($recommendation->algorithmVersion?->version ?? 'unknown') }}</dd>
                                            </div>
                                            @if($row['material_relevance'] !== null)
                                                <div>
                                                    <dt class="text-slate-600">Material relevance</dt>
                                                    <dd class="mt-0.5 font-mono text-slate-300">{{ number_format($row['material_relevance'], 2) }}</dd>
                                                </div>
                                            @endif
                                            <div>
                                                <dt class="text-slate-600">Generated</dt>
                                                <dd class="mt-0.5 text-slate-300">{{ $recommendation->generated_at?->format('d M Y H:i') ?? '—' }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
