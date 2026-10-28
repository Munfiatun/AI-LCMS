@extends('layouts.app')

@php
    $title = 'Review Kuis';
    $breadcrumb = 'Kuis & Evaluasi';
    $isDraft = $quiz->status === 'draft';
    $isPublished = $quiz->status === 'published';
    $questionsReady = $quiz->quizQuestions->count() >= $quiz->total_questions && $reviewStats['pending'] === 0;
    $canPublish = $isDraft && $publicationErrors === [];
@endphp

@section('content')
<div class="space-y-6">
    @if($errors->any())
        <div role="alert" class="rounded-2xl border border-rose-500/25 bg-rose-500/10 p-4 text-sm text-rose-200">
            <p class="font-bold">Ada yang perlu diperbaiki:</p>
            <div class="mt-2 space-y-1">
                @foreach($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <section class="rounded-3xl border border-slate-800 bg-slate-900/75 p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 max-w-3xl">
                <a href="{{ route('instructor.quizzes.index') }}" class="text-sm font-semibold text-slate-500 transition hover:text-indigo-300">← Kembali ke Kuis</a>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $isPublished ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/20 bg-amber-500/10 text-amber-300' }}">{{ $isPublished ? 'Terbit' : 'Draft' }}</span>
                    <span class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-xs font-semibold text-slate-400">{{ ucfirst($quiz->assessment_type ?? 'formative') }}</span>
                    @if($isDraft)
                        <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-xs font-semibold text-indigo-300">AI Generated Draft</span>
                    @endif
                </div>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $quiz->title }}</h1>
                <p class="mt-2 text-base leading-7 text-slate-400">{{ $quiz->course->title }} · {{ $quiz->quizQuestions->count() }} soal dipilih · nilai lulus {{ $quiz->passing_score }}%</p>
            </div>

            <a href="{{ route('instructor.quizzes.results', $quiz) }}" class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">Lihat Hasil</a>
        </div>
    </section>

    @if($isPublished)
        <section class="rounded-2xl border {{ $attemptStats['completed'] > 0 ? 'border-emerald-500/20 bg-emerald-500/[0.05]' : 'border-indigo-500/20 bg-indigo-500/[0.05]' }} p-5">
            @if($attemptStats['completed'] > 0)
                <h2 class="font-bold text-emerald-200">Kuis sudah digunakan siswa</h2>
                <p class="mt-1 text-sm leading-6 text-slate-400">{{ $attemptStats['completed'] }} attempt sudah selesai. Komposisi soal dikunci agar histori hasil belajar tetap valid. Jika ingin versi berbeda, buat kuis baru.</p>
            @else
                <h2 class="font-bold text-indigo-200">Kuis sudah terbit</h2>
                <p class="mt-1 text-sm leading-6 text-slate-400">Belum ada attempt yang selesai. Jika masih perlu mengubah soal, kembalikan ke Draft terlebih dahulu.</p>
            @endif
        </section>
    @endif

    @if($isDraft && $publicationErrors !== [])
        <section class="rounded-2xl border border-amber-500/20 bg-amber-500/[0.05] p-5">
            <h2 class="font-bold text-amber-200">Belum siap diterbitkan</h2>
            <p class="mt-1 text-sm leading-6 text-slate-400">{{ implode(' ', $publicationErrors) }}</p>
        </section>
    @endif

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="rounded-3xl border {{ $quiz->quizQuestions->count() > 0 ? 'border-indigo-500/20 bg-indigo-500/[0.05]' : 'border-slate-800 bg-slate-900/60' }} p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $quiz->quizQuestions->count() > 0 ? 'bg-indigo-600' : 'bg-slate-800' }} text-sm font-extrabold text-white">1</div>
                <div>
                    <h2 class="text-lg font-extrabold text-white">Pilih Soal</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $quiz->quizQuestions->count() }}/{{ $quiz->total_questions }} soal sudah masuk ke kuis.</p>
                </div>
            </div>

            @if($isDraft)
                <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="mt-5 block rounded-xl bg-indigo-600 px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-indigo-500">Buka Builder Soal</a>
            @else
                <div class="mt-5 rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 text-center text-sm font-semibold text-slate-500">Builder terkunci saat kuis terbit</div>
            @endif
        </article>

        <article class="rounded-3xl border {{ $reviewStats['pending'] === 0 && $quiz->quizQuestions->count() > 0 ? 'border-emerald-500/20 bg-emerald-500/[0.05]' : 'border-amber-500/20 bg-amber-500/[0.04]' }} p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $reviewStats['pending'] === 0 && $quiz->quizQuestions->count() > 0 ? 'bg-emerald-500 text-slate-950' : 'bg-amber-400 text-slate-950' }} text-sm font-extrabold">2</div>
                <div>
                    <h2 class="text-lg font-extrabold text-white">Review</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $reviewStats['pending'] > 0 ? $reviewStats['pending'].' soal masih perlu diverifikasi guru.' : ($quiz->quizQuestions->count() > 0 ? 'Semua soal sudah terverifikasi.' : 'Pilih soal terlebih dahulu.') }}</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-2">
                <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-3 text-center">
                    <div class="text-xl font-extrabold text-emerald-300">{{ $reviewStats['verified'] }}</div>
                    <div class="mt-1 text-xs text-slate-500">Terverifikasi</div>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-3 text-center">
                    <div class="text-xl font-extrabold {{ $reviewStats['pending'] > 0 ? 'text-amber-300' : 'text-slate-500' }}">{{ $reviewStats['pending'] }}</div>
                    <div class="mt-1 text-xs text-slate-500">Perlu review</div>
                </div>
            </div>
        </article>

        <article class="rounded-3xl border {{ $isPublished ? 'border-emerald-500/20 bg-emerald-500/[0.05]' : ($canPublish ? 'border-emerald-500/20 bg-emerald-500/[0.05]' : 'border-slate-800 bg-slate-900/60') }} p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $isPublished || $canPublish ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-white' }} text-sm font-extrabold">3</div>
                <div>
                    <h2 class="text-lg font-extrabold text-white">Terbitkan</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $isPublished ? 'Kuis sudah dapat diakses sesuai aturan course.' : ($canPublish ? 'Semua quality gate terpenuhi.' : 'Selesaikan langkah sebelumnya terlebih dahulu.') }}</p>
                </div>
            </div>

            @if($canPublish)
                <form action="{{ route('instructor.quizzes.publish', $quiz) }}" method="POST" class="mt-5">
                    @csrf
                    <button type="submit" class="w-full rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-extrabold text-slate-950 transition hover:bg-emerald-400">Terbitkan Kuis</button>
                </form>
            @elseif($isPublished)
                <div class="mt-5 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-center text-sm font-bold text-emerald-200">✓ Sudah terbit</div>
            @else
                <div class="mt-5 rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 text-center text-sm font-semibold text-slate-500">Belum siap terbit</div>
            @endif
        </article>
    </section>

    @if($isPublished && $attemptStats['completed'] === 0)
        <details class="rounded-2xl border border-slate-800 bg-slate-900/60">
            <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-slate-300">Perlu mengubah kuis yang sudah terbit?</summary>
            <div class="border-t border-slate-800 p-5">
                <p class="text-sm leading-6 text-slate-500">Kuis dapat dikembalikan ke Draft karena belum ada attempt selesai. {{ $attemptStats['in_progress'] > 0 ? $attemptStats['in_progress'].' attempt yang sedang berjalan akan dibatalkan agar siswa tidak melanjutkan snapshot soal lama.' : '' }}</p>
                <form action="{{ route('instructor.quizzes.unpublish', $quiz) }}" method="POST" class="mt-4" onsubmit="return confirm('{{ $attemptStats['in_progress'] > 0 ? 'Attempt yang belum selesai akan dibatalkan. Kembalikan kuis ke Draft?' : 'Kembalikan kuis ke Draft agar dapat diedit?' }}')">
                    @csrf
                    <button type="submit" class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2.5 text-sm font-semibold text-amber-200 hover:bg-amber-500/20">Kembalikan ke Draft</button>
                </form>
            </div>
        </details>
    @endif

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-white">Soal dalam Kuis</h2>
                <p class="mt-1 text-sm text-slate-500">Klik satu soal untuk melihat atau memverifikasi detailnya.</p>
            </div>
            <span class="text-sm text-slate-500">{{ $quiz->quizQuestions->count() }} soal</span>
        </div>

        <div class="space-y-3">
            @forelse($quiz->quizQuestions as $quizQuestion)
                <details class="group overflow-hidden rounded-2xl border {{ $quizQuestion->question->needs_review ? 'border-amber-500/20' : 'border-slate-800' }} bg-slate-900/65" @if($quizQuestion->question->needs_review && $isDraft) open @endif>
                    <summary class="flex cursor-pointer list-none items-start gap-4 p-4 sm:p-5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-sm font-extrabold text-slate-300">{{ $quizQuestion->order }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-semibold leading-6 text-slate-100">{{ strip_tags($quizQuestion->question->question_text) }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-slate-700 bg-slate-950/60 px-2.5 py-1 text-xs text-slate-400">{{ ucfirst($quizQuestion->question->difficulty) }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $quizQuestion->question->needs_review ? 'border-amber-500/20 bg-amber-500/10 text-amber-300' : 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' }}">{{ $quizQuestion->question->needs_review ? 'Perlu review' : 'Terverifikasi' }}</span>
                                <span class="text-xs text-slate-600">{{ $quizQuestion->points }} poin</span>
                            </div>
                        </div>
                        <span class="mt-1 text-sm font-semibold text-indigo-300 group-open:hidden">Lihat ↓</span>
                        <span class="mt-1 hidden text-sm font-semibold text-indigo-300 group-open:inline">Tutup ↑</span>
                    </summary>

                    <div class="border-t border-slate-800 p-4 sm:p-5 sm:pl-[4.5rem]">
                        @if($isDraft)
                            @include('instructor.quizzes.question-review', ['question' => $quizQuestion->question, 'number' => $quizQuestion->order])
                        @else
                            <div class="grid gap-2">
                                @foreach($quizQuestion->question->options as $index => $option)
                                    <div class="flex items-start gap-3 rounded-xl border {{ $option->is_correct ? 'border-emerald-500/20 bg-emerald-500/[0.06]' : 'border-slate-800 bg-slate-950/40' }} p-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border {{ $option->is_correct ? 'border-emerald-500/30 text-emerald-300' : 'border-slate-700 text-slate-500' }} text-xs font-bold">{{ chr(65 + $index) }}</span>
                                        <span class="text-sm leading-6 {{ $option->is_correct ? 'font-semibold text-emerald-100' : 'text-slate-400' }}">{{ $option->option_text }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                    <h3 class="text-lg font-bold text-white">Belum ada soal</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">Buka Builder untuk memilih soal dari bank soal Anda.</p>
                    @if($isDraft)
                        <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">Buka Builder Soal</a>
                    @endif
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
