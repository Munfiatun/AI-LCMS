@extends('layouts.app')

@php
    $title = 'Rekomendasi Belajar';
    $breadcrumb = 'Rekomendasi Belajar';
@endphp

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-cyan-300">Belajar sesuai kebutuhanmu</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Rekomendasi Belajar untuk {{ $student->name }}</h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-400">Fokus pada satu langkah yang disarankan sekarang. Setelah materi atau uji pemahaman selesai, sistem akan menyesuaikan langkah berikutnya secara otomatis.</p>
            </div>
            <a href="{{ route('student.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">Kembali ke Beranda</a>
        </div>
    </section>

    @if($rows->isEmpty())
        <section class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 px-6 py-12 text-center">
            <h2 class="text-lg font-bold text-white">Belum ada rekomendasi adaptif aktif</h2>
            <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500">Selesaikan asesmen awal atau aktivitas belajar yang tersedia. Rekomendasi berikutnya akan muncul setelah sistem memiliki cukup informasi tentang progres belajarmu.</p>
            <a href="{{ route('student.quizzes.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white hover:bg-cyan-500">Lihat Kuis & Asesmen</a>
        </section>
    @else
        <div class="space-y-6">
            @foreach($learningPaths as $path)
                @php
                    $course = $path['course'];
                    $current = $path['current_step'];
                    $currentRow = $current['representative_row'];
                    $currentTarget = $current['target_concept'];
                    $currentMaterial = $currentRow['recommended_material'];
                    $currentFormative = $currentRow['formative_quiz'];
                    $canReassess = $currentFormative && $currentRow['reassessment_ready'];
                @endphp

                <section class="overflow-hidden rounded-3xl border border-cyan-500/20 bg-slate-900/80 shadow-xl">
                    <div class="border-b border-slate-800 px-5 py-4 sm:px-6">
                        <p class="text-sm font-semibold text-slate-400">{{ $course->title }}</p>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="grid gap-6 lg:grid-cols-[1fr_260px] lg:items-start">
                            <div class="min-w-0">
                                <div class="inline-flex rounded-full bg-emerald-500 px-3 py-1 text-xs font-extrabold text-slate-950">Fokus Sekarang</div>
                                <h2 class="mt-4 text-2xl font-extrabold text-white">{{ $currentTarget?->code }} — {{ $currentTarget?->name }}</h2>
                                <p class="mt-3 text-base leading-7 text-slate-300">{{ $current['priority_reason'] }}</p>

                                @if($currentRow['activity_type'] === 'material' && $currentMaterial)
                                    <div class="mt-5 rounded-2xl border border-slate-800 bg-slate-950/45 p-4 sm:p-5">
                                        <p class="text-sm font-semibold text-emerald-300">Materi yang disarankan</p>
                                        <h3 class="mt-1 text-base font-bold text-white">{{ $currentMaterial->title }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">{{ $currentMaterial->section?->title ?? 'Materi Kursus' }}@if($currentMaterial->duration_minutes) · {{ $currentMaterial->duration_minutes }} menit @endif</p>

                                        @if($currentRow['material_completed'])
                                            <p class="mt-3 text-sm text-emerald-300">✓ Materi selesai. Kamu dapat melanjutkan ke uji pemahaman.</p>
                                        @else
                                            <p class="mt-3 text-sm text-slate-400">Selesaikan materi ini terlebih dahulu. Setelah itu uji pemahaman akan terbuka.</p>
                                        @endif
                                    </div>
                                @elseif($currentRow['activity_type'] === 'diagnostic')
                                    <div class="mt-5 rounded-2xl border border-cyan-500/20 bg-cyan-500/5 p-4 sm:p-5">
                                        <p class="font-bold text-white">Lengkapi asesmen terlebih dahulu</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-400">Hasil asesmen membantu sistem menentukan materi yang paling sesuai untukmu.</p>
                                    </div>
                                @endif

                                @if($currentFormative)
                                    <div class="mt-4 rounded-2xl border {{ $canReassess ? 'border-cyan-500/30 bg-cyan-500/5' : 'border-slate-800 bg-slate-950/35' }} p-4 sm:p-5">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $canReassess ? 'bg-cyan-500 text-slate-950' : 'bg-slate-800 text-slate-400' }} text-sm font-extrabold">2</div>
                                            <div>
                                                <p class="font-bold text-white">Uji Pemahaman</p>
                                                <p class="mt-1 text-sm text-slate-400">{{ $currentFormative->title }}</p>
                                                @if(!$canReassess)
                                                    <p class="mt-2 text-sm text-slate-500">Tersedia setelah materi di atas selesai.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <aside class="rounded-2xl border border-slate-800 bg-slate-950/45 p-4 sm:p-5">
                                <p class="text-sm font-bold text-white">Langkah berikutnya</p>
                                <p class="mt-2 text-sm leading-6 text-slate-500">Kerjakan satu aktivitas ini saja. Sistem akan menentukan langkah selanjutnya setelah ada progres baru.</p>

                                @if($canReassess)
                                    <a href="{{ route('student.quizzes.show', $currentFormative) }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-cyan-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-cyan-500">Uji Pemahaman</a>
                                @elseif($currentRow['activity_type'] === 'material' && $currentMaterial)
                                    <a href="{{ route('student.materials.show', [$course, $currentMaterial]) }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-500">{{ $currentRow['material_completed'] ? 'Buka Materi Lagi' : 'Mulai Belajar' }}</a>
                                @elseif($currentRow['activity_type'] === 'diagnostic')
                                    <a href="{{ route('student.quizzes.index') }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-cyan-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-cyan-500">Lengkapi Asesmen</a>
                                @else
                                    <a href="{{ route('student.courses.progress', $course) }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-3 text-sm font-bold text-slate-200 transition hover:bg-slate-700">Buka Kursus</a>
                                @endif
                            </aside>
                        </div>

                        @if($path['queued_steps']->isNotEmpty())
                            <details class="mt-5 rounded-2xl border border-slate-800 bg-slate-950/30">
                                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-300">Lihat rencana setelah fokus ini</summary>
                                <div class="space-y-2 border-t border-slate-800 p-4">
                                    <p class="text-sm leading-6 text-slate-500">Urutan ini sementara dan dapat berubah setelah hasil belajar terbaru dinilai.</p>
                                    @foreach($path['queued_steps'] as $step)
                                        @php
                                            $stepTarget = $step['target_concept'];
                                        @endphp
                                        <div class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/60 p-3">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-slate-300">{{ $step['position'] }}</span>
                                            <p class="text-sm font-semibold text-slate-300">{{ $stepTarget?->code }} — {{ $stepTarget?->name }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                </section>
            @endforeach

            <details class="rounded-3xl border border-slate-800 bg-slate-900/60">
                <summary class="cursor-pointer list-none p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="font-bold text-white">Detail Sistem & Transparansi</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Informasi teknis tersedia untuk melihat alasan rekomendasi, tetapi tidak diperlukan untuk mengikuti pembelajaran.</p>
                        </div>
                        <span class="shrink-0 text-sm font-semibold text-cyan-300">Buka detail ↓</span>
                    </div>
                </summary>

                <div class="space-y-5 border-t border-slate-800 p-5 sm:p-6">
                    @foreach($courseGroups as $group)
                        @php
                            $first = $group->first();
                            $course = $first['recommendation']->course;
                        @endphp
                        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/35">
                            <div class="border-b border-slate-800 px-4 py-3">
                                <h3 class="font-bold text-white">{{ $course->title }}</h3>
                            </div>

                            <div class="divide-y divide-slate-800">
                                @foreach($group as $row)
                                    @php
                                        $recommendation = $row['recommendation'];
                                        $source = $recommendation->sourceConcept;
                                        $target = $recommendation->targetConcept;
                                    @endphp
                                    <article class="p-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2.5 py-1 text-xs font-bold text-cyan-300">{{ $row['action_label'] }}</span>
                                            @if($row['is_prerequisite_redirect'])
                                                <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-xs font-bold text-amber-300">Fokus Prasyarat</span>
                                            @endif
                                        </div>

                                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-3">
                                                <p class="text-xs text-slate-500">Konsep yang dievaluasi</p>
                                                <p class="mt-1 text-sm font-bold text-white">{{ $source?->code }} — {{ $source?->name }}</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-3">
                                                <p class="text-xs text-slate-500">Fokus yang direkomendasikan</p>
                                                <p class="mt-1 text-sm font-bold text-white">{{ $target?->code }} — {{ $target?->name }}</p>
                                            </div>
                                        </div>

                                        <p class="mt-3 text-sm leading-6 text-slate-400">{{ $row['reason_text'] }}</p>

                                        <dl class="mt-3 grid gap-2 text-xs text-slate-500 sm:grid-cols-3">
                                            <div>
                                                <dt>Reason code</dt>
                                                <dd class="mt-1 break-all font-mono text-slate-300">{{ $recommendation->reason_code }}</dd>
                                            </div>
                                            <div>
                                                <dt>Algorithm</dt>
                                                <dd class="mt-1 font-mono text-slate-300">{{ ($recommendation->algorithmVersion?->code ?? 'unknown') . '@' . ($recommendation->algorithmVersion?->version ?? 'unknown') }}</dd>
                                            </div>
                                            <div>
                                                <dt>Generated</dt>
                                                <dd class="mt-1 text-slate-300">{{ $recommendation->generated_at?->format('d M Y H:i') ?? '—' }}</dd>
                                            </div>
                                        </dl>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </details>
        </div>
    @endif
</div>
@endsection
