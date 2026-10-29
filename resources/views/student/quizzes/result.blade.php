@extends('layouts.app')

@php
    $title = 'Hasil Kuis';
    $breadcrumb = 'Hasil Kuis';
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <section class="overflow-hidden rounded-2xl border {{ $attempt->isPassed() ? 'border-emerald-500/20 bg-emerald-500/[0.04]' : 'border-amber-500/20 bg-amber-500/[0.04]' }}">
        <div class="p-6 sm:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $attempt->isPassed() ? 'bg-emerald-500/10 text-emerald-300' : 'bg-amber-500/10 text-amber-300' }}">
                        @if($attempt->isPassed())
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        @else
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z" /></svg>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] {{ $attempt->isPassed() ? 'text-emerald-300' : 'text-amber-300' }}">Assessment Result</p>
                        <h1 class="mt-1 text-2xl font-extrabold text-white">{{ $quiz->title }}</h1>
                        @if($attempt->isPassed())
                            <p class="mt-2 text-sm font-semibold text-emerald-200">SELAMAT, ANDA LULUS!</p>
                            <p class="mt-1 text-sm text-slate-400">Anda telah memenuhi standar kelulusan untuk kuis ini.</p>
                        @else
                            <p class="mt-2 text-sm font-semibold text-amber-200">ANDA BELUM LULUS</p>
                            <p class="mt-1 text-sm text-slate-400">{{ $attempt->status === 'expired' ? 'Waktu ujian telah habis. Attempt ini tidak dihitung sebagai kelulusan.' : 'Nilai Anda masih di bawah standar kelulusan ('.$quiz->passing_score.'%).' }}</p>
                        @endif
                    </div>
                </div>

                <div class="grid min-w-[260px] grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Nilai</p>
                        <p class="mt-1 text-3xl font-black {{ $attempt->isPassed() ? 'text-emerald-300' : 'text-amber-300' }}">{{ round($attempt->percentage, 1) }}%</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Batas Lulus</p>
                        <p class="mt-1 text-3xl font-black text-indigo-300">{{ $quiz->passing_score }}%</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-4">
                    <p class="text-xs text-slate-500">Jawaban Benar</p>
                    <p class="mt-1 text-xl font-bold text-emerald-300">{{ $attempt->correct_count }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-4">
                    <p class="text-xs text-slate-500">Salah / Kosong</p>
                    <p class="mt-1 text-xl font-bold text-rose-300">{{ $attempt->wrong_count }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-4">
                    <p class="text-xs text-slate-500">Total Skor Poin</p>
                    <p class="mt-1 text-xl font-bold text-white">{{ $attempt->score }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-4">
                    <p class="text-xs text-slate-500">Waktu Pengerjaan</p>
                    <p class="mt-1 text-xl font-bold text-white">
                        @if($attempt->duration_seconds)
                            {{ floor($attempt->duration_seconds / 60) }}m {{ $attempt->duration_seconds % 60 }}s
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </section>

    @if($canRetry)
        <section class="rounded-2xl border border-indigo-500/20 bg-indigo-500/[0.05] p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-300">Retry Guidance</p>
                    <h2 class="mt-1 text-lg font-bold text-white">Masih ada {{ $remainingAttempts }} kesempatan</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-400">Tinjau soal yang belum tepat dan kembali ke materi sumber sebelum mencoba lagi. Untuk menjaga integritas asesmen, kunci jawaban lengkap belum ditampilkan.</p>
                </div>
                <a href="{{ route('student.quizzes.show', $quiz) }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">
                    Coba Lagi Kuis Ini
                </a>
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60">
        <div class="border-b border-slate-800 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-300">Learning Feedback</p>
                    <h2 class="mt-1 text-lg font-bold text-white">Review Jawaban</h2>
                </div>
                <p class="text-xs text-slate-500">{{ $revealSolutions ? 'Kunci dan pembahasan dapat ditinjau.' : 'Kunci dibuka setelah lulus atau attempt terakhir.' }}</p>
            </div>
        </div>

        <div class="divide-y divide-slate-800">
            @foreach($attempt->attemptQuestions as $attemptQuestion)
                @php
                    $question = $attemptQuestion->question;
                    $answer = $answerMap->get($question->id);
                    $selected = $answer?->selectedOption;
                    $correct = $question->options->firstWhere('is_correct', true);
                @endphp
                <article class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $answer?->is_correct ? 'bg-emerald-500/10 text-emerald-300' : 'bg-rose-500/10 text-rose-300' }} text-xs font-extrabold">
                            {{ $attemptQuestion->order }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <h3 class="whitespace-pre-wrap break-words text-sm font-semibold leading-6 text-slate-100">{{ $question->question_text }}</h3>
                                <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $answer?->is_correct ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-rose-500/20 bg-rose-500/10 text-rose-300' }}">
                                    {{ $answer?->is_correct ? 'Benar' : 'Perlu Ditinjau' }}
                                </span>
                            </div>

                            <div class="mt-4 grid gap-3 {{ $revealSolutions ? 'md:grid-cols-2' : '' }}">
                                <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-4">
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Jawaban Anda</p>
                                    <p class="mt-1 whitespace-pre-wrap break-words text-sm {{ $answer?->is_correct ? 'text-emerald-200' : 'text-slate-300' }}">{{ $selected?->option_text ?? 'Tidak dijawab' }}</p>
                                </div>
                                @if($revealSolutions)
                                    <div class="rounded-xl border border-emerald-500/15 bg-emerald-500/[0.04] p-4">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-400">Kunci Jawaban</p>
                                        <p class="mt-1 whitespace-pre-wrap break-words text-sm text-emerald-100">{{ $correct?->option_text ?? '-' }}</p>
                                    </div>
                                @endif
                            </div>

                            @if($revealSolutions && filled($question->explanation))
                                <div class="mt-3 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-indigo-300">Pembahasan</p>
                                    <p class="mt-1 whitespace-pre-wrap break-words text-sm leading-6 text-slate-300">{{ $question->explanation }}</p>
                                </div>
                            @elseif(! $revealSolutions && $question->source_slide_number)
                                <div class="mt-3 rounded-xl border border-cyan-500/15 bg-cyan-500/[0.04] px-4 py-3 text-xs leading-5 text-cyan-100/90">
                                    Tinjau kembali materi pada <strong>Slide {{ $question->source_slide_number }}</strong> sebelum attempt berikutnya.
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-slate-600 hover:text-white">
            Kembali ke Dashboard
        </a>
        <a href="{{ route('student.quizzes.index') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
            Kembali ke Kuis & Nilai
        </a>
    </div>
</div>
@endsection