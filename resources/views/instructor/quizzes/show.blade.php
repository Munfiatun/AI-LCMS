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
        <div role="alert" class="rounded-2xl border theme-danger-border theme-danger-surface p-4 text-sm theme-danger-text">
            <p class="font-bold">Ada yang perlu diperbaiki:</p>
            <div class="mt-2 space-y-1">
                @foreach($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 max-w-3xl">
                <a href="{{ route('instructor.quizzes.index') }}" class="text-sm font-semibold theme-muted transition theme-accent-text-hover">← Kembali ke Kuis</a>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border px-3 py-1 text-sm font-semibold {{ $isPublished ? 'theme-success-border theme-success-surface theme-success-text' : 'theme-warning-border theme-warning-surface theme-warning-text' }}">{{ $isPublished ? 'Terbit' : 'Draft' }}</span>
                    <span class="rounded-full border theme-border theme-surface px-3 py-1 text-sm font-semibold theme-body">{{ ucfirst($quiz->assessment_type ?? 'formative') }}</span>
                    @if($isDraft)
                        <span class="rounded-full border theme-accent-border theme-accent-surface px-3 py-1 text-sm font-semibold theme-accent-text">AI Generated Draft</span>
                    @endif
                </div>
                <h1 class="teacher-page-title mt-3 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">{{ $quiz->title }}</h1>
                <p class="mt-2 text-base leading-7 theme-body">{{ $quiz->course->title }} · {{ $quiz->quizQuestions->count() }} soal dipilih · nilai lulus {{ $quiz->passing_score }}%</p>
            </div>

            <a href="{{ route('instructor.quizzes.results', $quiz) }}" class="teacher-secondary-button rounded-xl border theme-border theme-muted-surface px-4 py-2.5 text-sm font-semibold theme-heading transition theme-muted-surface-hover">Lihat Hasil</a>
        </div>
    </section>

    @if($isPublished)
        <section class="rounded-2xl border {{ $attemptStats['completed'] > 0 ? 'theme-success-border theme-success-surface' : 'theme-accent-border theme-accent-surface' }} p-5">
            @if($attemptStats['completed'] > 0)
                <h2 class="font-bold theme-success-text">Kuis sudah digunakan siswa</h2>
                <p class="mt-1 text-sm leading-6 theme-body">{{ $attemptStats['completed'] }} attempt sudah selesai. Komposisi soal dikunci agar histori hasil belajar tetap valid. Jika ingin versi berbeda, buat kuis baru.</p>
            @else
                <h2 class="font-bold theme-accent-text">Kuis sudah terbit</h2>
                <p class="mt-1 text-sm leading-6 theme-body">Belum ada attempt yang selesai. Jika masih perlu mengubah soal, kembalikan ke Draft terlebih dahulu.</p>
            @endif
        </section>
    @endif

    @if($isDraft && $publicationErrors !== [])
        <section class="rounded-2xl border theme-warning-border theme-warning-surface p-5">
            <h2 class="font-bold theme-warning-text">Belum siap diterbitkan</h2>
            <p class="mt-1 text-sm leading-6 theme-body">{{ implode(' ', $publicationErrors) }}</p>
        </section>
    @endif

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="rounded-3xl border teacher-step-card p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $quiz->quizQuestions->count() > 0 ? 'theme-accent-fill teacher-on-solid' : 'teacher-step-number' }} text-sm font-extrabold">1</div>
                <div>
                    <h2 class="text-lg font-extrabold theme-heading">Pilih Soal</h2>
                    <p class="mt-1 text-sm leading-6 theme-muted">{{ $quiz->quizQuestions->count() }}/{{ $quiz->total_questions }} soal sudah masuk ke kuis.</p>
                </div>
            </div>

            @if($isDraft)
                <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="mt-5 block rounded-xl theme-accent-fill px-4 py-2.5 text-center text-sm font-bold teacher-on-solid transition theme-accent-fill-hover">Buka Builder Soal</a>
            @else
                <div class="mt-5 rounded-xl border teacher-card px-4 py-3 text-center text-sm font-semibold theme-muted">Builder terkunci saat kuis terbit</div>
            @endif
        </article>

        <article class="rounded-3xl border teacher-step-card p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $reviewStats['pending'] === 0 && $quiz->quizQuestions->count() > 0 ? 'theme-success-fill teacher-on-solid' : 'theme-warning-fill teacher-on-solid' }} text-sm font-extrabold">2</div>
                <div>
                    <h2 class="text-lg font-extrabold theme-heading">Review & Verifikasi Soal</h2>
                    <p class="mt-1 text-sm leading-6 theme-muted">{{ $reviewStats['pending'] > 0 ? $reviewStats['pending'].' soal masih perlu diverifikasi guru.' : ($quiz->quizQuestions->count() > 0 ? 'Semua soal sudah terverifikasi.' : 'Pilih soal terlebih dahulu.') }}</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-2">
                <div class="rounded-xl border teacher-card p-3 text-center">
                    <div class="text-xl font-extrabold theme-success-text">{{ $reviewStats['verified'] }}</div>
                    <div class="mt-1 text-sm theme-muted">Terverifikasi</div>
                </div>
                <div class="rounded-xl border teacher-card p-3 text-center">
                    <div class="text-xl font-extrabold {{ $reviewStats['pending'] > 0 ? 'theme-warning-text' : 'theme-muted' }}">{{ $reviewStats['pending'] }}</div>
                    <div class="mt-1 text-sm theme-muted">Perlu review</div>
                </div>
            </div>
        </article>

        <article class="rounded-3xl border teacher-step-card p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $isPublished || $canPublish ? 'theme-success-fill teacher-on-solid' : 'teacher-step-number' }} text-sm font-extrabold">3</div>
                <div>
                    <h2 class="text-lg font-extrabold theme-heading">Terbitkan</h2>
                    <p class="mt-1 text-sm leading-6 theme-muted">{{ $isPublished ? 'Kuis sudah dapat diakses sesuai aturan course.' : ($canPublish ? 'Semua quality gate terpenuhi.' : 'Selesaikan langkah sebelumnya terlebih dahulu.') }}</p>
                </div>
            </div>

            @if($canPublish)
                <form action="{{ route('instructor.quizzes.publish', $quiz) }}" method="POST" class="mt-5">
                    @csrf
                    <button type="submit" class="w-full rounded-xl theme-success-fill px-4 py-2.5 text-sm font-extrabold teacher-on-solid transition theme-success-fill-hover">Terbitkan Kuis</button>
                </form>
            @elseif($isPublished)
                <div class="mt-5 rounded-xl border theme-success-border theme-success-surface px-4 py-3 text-center text-sm font-bold theme-success-text">✓ Sudah terbit</div>
            @else
                <div class="mt-5 rounded-xl border teacher-card px-4 py-3 text-center text-sm font-semibold theme-muted">Belum siap terbit</div>
            @endif
        </article>
    </section>

    @if($isPublished && $attemptStats['completed'] === 0)
        <details class="rounded-2xl border teacher-card">
            <summary class="cursor-pointer px-5 py-4 text-sm font-semibold theme-body">Perlu mengubah kuis yang sudah terbit?</summary>
            <div class="border-t theme-border p-5">
                <p class="text-sm leading-6 theme-muted">Kuis dapat dikembalikan ke Draft karena belum ada attempt selesai. {{ $attemptStats['in_progress'] > 0 ? $attemptStats['in_progress'].' attempt yang sedang berjalan akan dibatalkan agar siswa tidak melanjutkan snapshot soal lama.' : '' }}</p>
                <form action="{{ route('instructor.quizzes.unpublish', $quiz) }}" method="POST" class="mt-4" onsubmit="return confirm('{{ $attemptStats['in_progress'] > 0 ? 'Attempt yang belum selesai akan dibatalkan. Kembalikan kuis ke Draft?' : 'Kembalikan kuis ke Draft agar dapat diedit?' }}')">
                    @csrf
                    <button type="submit" class="rounded-xl border theme-warning-border theme-warning-surface px-4 py-2.5 text-sm font-semibold theme-warning-text theme-warning-surface-hover">Kembalikan ke Draft</button>
                </form>
            </div>
        </details>
    @endif

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-extrabold theme-heading">Soal dalam Kuis</h2>
                <p class="mt-1 text-sm theme-muted">Klik satu soal untuk melihat atau memverifikasi detailnya.</p>
            </div>
            <span class="text-sm theme-muted">{{ $quiz->quizQuestions->count() }} soal</span>
        </div>

        <div class="space-y-3">
            @forelse($quiz->quizQuestions as $quizQuestion)
                <details class="group overflow-hidden rounded-2xl border {{ $quizQuestion->question->needs_review ? 'theme-warning-border' : 'theme-border' }} theme-surface" @if($quizQuestion->question->needs_review && $isDraft) open @endif>
                    <summary class="flex cursor-pointer list-none items-start gap-4 p-4 sm:p-5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl theme-muted-surface text-sm font-extrabold theme-body">{{ $quizQuestion->order }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-semibold leading-6 theme-heading">{{ strip_tags($quizQuestion->question->question_text) }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full border theme-border theme-surface px-2.5 py-1 text-sm theme-body">{{ ucfirst($quizQuestion->question->difficulty) }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-sm font-semibold {{ $quizQuestion->question->needs_review ? 'theme-warning-border theme-warning-surface theme-warning-text' : 'theme-success-border theme-success-surface theme-success-text' }}">{{ $quizQuestion->question->needs_review ? 'Perlu review' : 'Terverifikasi' }}</span>
                                <span class="text-sm theme-muted">{{ $quizQuestion->points }} poin</span>
                            </div>
                        </div>
                        <span class="mt-1 text-sm font-semibold theme-accent-text group-open:hidden">Lihat ↓</span>
                        <span class="mt-1 hidden text-sm font-semibold theme-accent-text group-open:inline">Tutup ↑</span>
                    </summary>

                    <div class="border-t theme-border p-4 sm:p-5 sm:pl-[4.5rem]">
                        @if($isDraft)
                            @include('instructor.quizzes.question-review', ['question' => $quizQuestion->question, 'number' => $quizQuestion->order])
                        @else
                            <div class="grid gap-2">
                                @foreach($quizQuestion->question->options as $index => $option)
                                    <div class="flex items-start gap-3 rounded-xl border {{ $option->is_correct ? 'theme-success-border theme-success-surface' : 'teacher-card' }} p-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border {{ $option->is_correct ? 'theme-success-border theme-success-text' : 'theme-border theme-muted' }} text-sm font-bold">{{ chr(65 + $index) }}</span>
                                        <span class="text-sm leading-6 {{ $option->is_correct ? 'font-semibold theme-success-text' : 'theme-body' }}">{{ $option->option_text }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>
            @empty
                <div class="rounded-3xl border border-dashed teacher-card p-10 text-center">
                    <h3 class="text-lg font-bold theme-heading">Belum ada soal</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 theme-muted">Buka Builder untuk memilih soal dari bank soal Anda.</p>
                    @if($isDraft)
                        <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="mt-5 inline-flex rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Buka Builder Soal</a>
                    @endif
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
