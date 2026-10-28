@extends('layouts.app')

@php
    $title = 'Review Kuis';
    $breadcrumb = 'Review Kuis';
@endphp

@section('content')
<div class="space-y-6">
    @if($quiz->status === 'draft' && $publicationErrors)
        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-300">!</div>
                <div>
                    <p class="font-semibold text-amber-100">Quiz belum siap diterbitkan</p>
                    <p class="mt-1 text-xs leading-5 text-amber-200/80">{{ implode(' ', $publicationErrors) }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if($quiz->status === 'published')
        <div class="rounded-2xl border {{ $attemptStats['completed'] > 0 ? 'border-rose-500/20 bg-rose-500/10' : 'border-amber-500/20 bg-amber-500/10' }} px-4 py-3 text-sm">
            @if($attemptStats['completed'] > 0)
                <p class="font-semibold text-rose-100">Quiz terkunci karena sudah memiliki attempt selesai.</p>
                <p class="mt-1 text-xs leading-5 text-rose-200/80">Builder dan perubahan soal dinonaktifkan untuk menjaga histori hasil belajar. Buat quiz baru jika komposisi soal perlu diubah.</p>
            @else
                <p class="font-semibold text-amber-100">Quiz sedang Published.</p>
                <p class="mt-1 text-xs leading-5 text-amber-200/80">Kembalikan ke Draft sebelum membuka Builder. {{ $attemptStats['in_progress'] > 0 ? $attemptStats['in_progress'].' attempt yang belum selesai akan dibatalkan.' : 'Belum ada attempt yang selesai, jadi quiz masih aman untuk diedit.' }}</p>
            @endif
        </div>
    @endif

    <section class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80 shadow-2xl shadow-black/10">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-indigo-400/70 to-transparent"></div>
        <div class="p-6 lg:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full border px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] {{ $quiz->status === 'published' ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/20 bg-amber-500/10 text-amber-300' }}">
                            {{ $quiz->status === 'published' ? 'Published' : 'Draft Review' }}
                        </span>
                        @if($quiz->status === 'draft')
                            <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-300">AI Generated Draft</span>
                        @endif
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $quiz->title }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-400">
                        <span>Kursus: <strong class="font-semibold text-slate-200">{{ $quiz->course->title }}</strong></span>
                        <span class="hidden h-1 w-1 rounded-full bg-slate-600 sm:block"></span>
                        <span>{{ $quiz->quizQuestions->count() }} soal tersinkronisasi</span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('instructor.quizzes.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-slate-600 hover:bg-slate-800 hover:text-white">
                        ← Daftar Kuis
                    </a>

                    @if($quiz->status === 'draft')
                        <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="inline-flex items-center gap-2 rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2.5 text-sm font-semibold text-indigo-200 transition hover:bg-indigo-500/20">
                            Buka Builder
                        </a>
                    @else
                        <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl border border-slate-700 bg-slate-800/60 px-4 py-2.5 text-sm font-semibold text-slate-500" title="Kembalikan quiz ke Draft sebelum membuka Builder">
                            Builder Terkunci
                        </span>
                    @endif

                    @if($publicationErrors === [] && $quiz->status === 'draft')
                        <form action="{{ route('instructor.quizzes.publish', $quiz) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-400">
                                Terbitkan Kuis
                            </button>
                        </form>
                    @endif

                    @if($quiz->status === 'published' && $attemptStats['completed'] === 0)
                        <form action="{{ route('instructor.quizzes.unpublish', $quiz) }}" method="POST" onsubmit="return confirm('{{ $attemptStats['in_progress'] > 0 ? 'Quiz memiliki attempt yang belum selesai. Attempt tersebut akan dibatalkan. Kembalikan quiz ke Draft?' : 'Kembalikan quiz ke Draft agar dapat diedit?' }}')">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2.5 text-sm font-semibold text-amber-200 transition hover:bg-amber-500/20">
                                Kembalikan ke Draft
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 border-t border-slate-800 pt-6 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Target Soal</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ $quiz->total_questions }}</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Tersinkronisasi</p>
                    <p class="mt-2 text-2xl font-extrabold {{ $quiz->quizQuestions->count() < $quiz->total_questions ? 'text-amber-300' : 'text-emerald-300' }}">{{ $quiz->quizQuestions->count() }}</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Durasi</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ $quiz->duration_minutes ? $quiz->duration_minutes . ' m' : '∞' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Nilai Lulus</p>
                    <p class="mt-2 text-2xl font-extrabold text-white">{{ $quiz->passing_score }}</p>
                </div>
            </div>
        </div>
    </section>

    @if($quiz->status === 'draft')
        <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6 lg:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.22em] text-indigo-300">Assessment Quality Gate</p>
                    <h2 class="mt-2 text-xl font-bold text-white">Review & Verifikasi Soal</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">Periksa kualitas pertanyaan, kunci jawaban, dan pembahasan sebelum quiz diterbitkan. AI membantu menyusun draft, keputusan akhir tetap pada guru.</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3 text-xs text-slate-400">
                    {{ $reviewStats['pending'] > 0 ? $reviewStats['pending'].' soal masih menunggu verifikasi' : 'Semua soal sudah lolos review guru' }}
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border {{ $reviewStats['pending'] > 0 ? 'border-amber-500/20 bg-amber-500/5' : 'border-emerald-500/20 bg-emerald-500/5' }} p-4">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-400">Perlu Review</p>
                        <span class="h-2 w-2 rounded-full {{ $reviewStats['pending'] > 0 ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                    </div>
                    <p class="mt-3 text-3xl font-extrabold {{ $reviewStats['pending'] > 0 ? 'text-amber-300' : 'text-emerald-300' }}">{{ $reviewStats['pending'] }}</p>
                    <p class="mt-1 text-[11px] text-slate-500">{{ $reviewStats['pending'] }} perlu review guru</p>
                </div>
                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-4">
                    <p class="text-xs font-semibold text-slate-400">Terverifikasi</p>
                    <p class="mt-3 text-3xl font-extrabold text-emerald-300">{{ $reviewStats['verified'] }}</p>
                    <p class="mt-1 text-[11px] text-slate-500">Siap masuk quality gate</p>
                </div>
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4">
                    <p class="text-xs font-semibold text-slate-400">AI Inferred</p>
                    <p class="mt-3 text-3xl font-extrabold text-amber-300">{{ $reviewStats['inferred'] }}</p>
                    <p class="mt-1 text-[11px] text-slate-500">Kunci diinferensikan dari materi</p>
                </div>
                <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-4">
                    <p class="text-xs font-semibold text-slate-400">Sumber Terverifikasi</p>
                    <p class="mt-3 text-3xl font-extrabold text-indigo-300">{{ $reviewStats['explicit'] + $reviewStats['manual'] }}</p>
                    <p class="mt-1 text-[11px] text-slate-500">{{ $reviewStats['explicit'] }} eksplisit · {{ $reviewStats['manual'] }} manual</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-slate-800 bg-slate-950/40 p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs leading-5 text-slate-400">Edit dan simpan setiap soal untuk memverifikasi jawaban. Gunakan Builder jika ingin menghapus atau mengganti komposisi soal.</p>
                @foreach($quiz->quizQuestions->pluck('question.questionBank')->unique('id') as $bank)
                    <a href="{{ route('instructor.question-banks.show', $bank) }}" class="shrink-0 text-xs font-semibold text-indigo-300 transition hover:text-indigo-200">Tambah soal manual →</a>
                @endforeach
            </div>

            <div class="mt-6 space-y-4">
                @foreach($quiz->quizQuestions as $quizQuestion)
                    @include('instructor.quizzes.question-review', ['question' => $quizQuestion->question, 'number' => $quizQuestion->order])
                @endforeach
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70">
        <div class="flex flex-col gap-2 border-b border-slate-800 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-slate-500">Question Set</p>
                <h2 class="mt-1 text-lg font-bold text-white">Daftar Soal Saat Ini</h2>
            </div>
            @if($quiz->quizQuestions->count() < $quiz->total_questions && $quiz->status === 'draft')
                <span class="rounded-full border border-rose-500/20 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-300">Butuh {{ $quiz->total_questions - $quiz->quizQuestions->count() }} soal lagi</span>
            @endif
        </div>

        @if($quiz->quizQuestions->count() > 0)
            <div class="divide-y divide-slate-800">
                @foreach($quiz->quizQuestions as $quizQuestion)
                    <div class="flex items-start gap-4 px-6 py-4 transition hover:bg-slate-800/30">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-indigo-500/20 bg-indigo-500/10 text-sm font-extrabold text-indigo-300">{{ $quizQuestion->order }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold leading-6 text-slate-200">{{ strip_tags($quizQuestion->question->question_text) }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                <span class="rounded-full border border-slate-700 bg-slate-950/60 px-2 py-1">{{ str_replace('_', ' ', $quizQuestion->question->type) }}</span>
                                <span>{{ ucfirst($quizQuestion->question->difficulty) }}</span>
                                <span>·</span>
                                <span>{{ $quizQuestion->points }} poin</span>
                                @if($quizQuestion->question->needs_review)
                                    <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 font-semibold text-amber-300">Perlu review</span>
                                @else
                                    <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 font-semibold text-emerald-300">Terverifikasi</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-6 py-14 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-800 bg-slate-950/60 text-slate-500">?</div>
                <h3 class="mt-4 text-sm font-bold text-white">Belum Ada Soal</h3>
                <p class="mx-auto mt-2 max-w-md text-xs leading-5 text-slate-400">Buka Builder Kuis untuk memilih dan menyinkronkan soal dari Bank Soal Anda.</p>
                @if($quiz->status === 'draft')
                    <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">Buka Builder Kuis</a>
                @else
                    <p class="mx-auto mt-4 max-w-md text-xs text-amber-300">Kembalikan quiz ke Draft sebelum membuka Builder.</p>
                @endif
            </div>
        @endif
    </section>
</div>
@endsection