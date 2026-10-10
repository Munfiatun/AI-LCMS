@extends('layouts.app')

@php
    $title = 'Kerjakan Kuis';
    $breadcrumb = 'Kerjakan Kuis';
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="quizExam()">
    <form action="{{ route('student.quizzes.submit', ['quiz' => $quiz->id, 'attempt' => $attempt->id]) }}" method="POST" id="examForm">
        @csrf

        <section class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/75 shadow-xl shadow-black/10">
            <div class="p-5 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <span class="inline-flex rounded-full border border-indigo-500/20 bg-indigo-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-indigo-300">Live Assessment</span>
                        <h1 class="mt-3 text-xl font-extrabold text-white sm:text-2xl">{{ $quiz->title }}</h1>
                        <p class="mt-1 text-sm text-slate-400">Jawaban disimpan sementara di browser selama attempt berlangsung.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <div class="rounded-xl border border-slate-800 bg-slate-950/55 px-4 py-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Progress</p>
                            <p class="mt-1 text-sm font-bold text-white"><span x-text="currentQuestion + 1"></span> / {{ $attempt->attemptQuestions->count() }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-800 bg-slate-950/55 px-4 py-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Terjawab</p>
                            <p class="mt-1 text-sm font-bold text-emerald-300"><span x-text="answeredCount"></span> / {{ $attempt->attemptQuestions->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-5">
                @foreach($attempt->attemptQuestions as $index => $attemptQuestion)
                    <section x-show="currentQuestion === {{ $index }}" x-cloak class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70">
                        <div class="flex items-center justify-between gap-4 border-b border-slate-800 px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-indigo-500/20 bg-indigo-500/10 text-sm font-extrabold text-indigo-300">{{ $index + 1 }}</span>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Soal No. {{ $index + 1 }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-300">{{ $attemptQuestion->question->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Benar/Salah' }}</p>
                                </div>
                            </div>
                            <span class="rounded-full border border-slate-700 bg-slate-950/60 px-2.5 py-1 text-[10px] font-semibold text-slate-400">{{ ucfirst($attemptQuestion->question->difficulty) }}</span>
                        </div>

                        <div class="p-5 sm:p-6 lg:p-8">
                            <div class="prose prose-invert max-w-none text-base leading-7 text-slate-100 sm:text-lg">
                                {!! $attemptQuestion->question->question_text !!}
                            </div>

                            <div class="mt-7 space-y-3">
                                @foreach($attemptQuestion->attemptOptions as $attemptOption)
                                    <label class="flex cursor-pointer items-start gap-4 rounded-2xl border p-4 transition"
                                        :class="answers[{{ $attemptQuestion->question_id }}] == {{ $attemptOption->option_id }} ? 'border-indigo-500/60 bg-indigo-500/10 ring-1 ring-indigo-500/30' : 'border-slate-800 bg-slate-950/45 hover:border-slate-700 hover:bg-slate-800/35'">
                                        <input type="radio"
                                            name="answers[{{ $attemptQuestion->question_id }}]"
                                            value="{{ $attemptOption->option_id }}"
                                            x-model="answers[{{ $attemptQuestion->question_id }}]"
                                            class="mt-1 h-5 w-5 border-slate-600 bg-slate-950 text-indigo-600 focus:ring-indigo-500">
                                        <span class="min-w-0 flex-1 text-sm leading-6 text-slate-200 sm:text-base">{!! $attemptOption->option->option_text !!}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" @click="prevQuestion" :disabled="currentQuestion === 0" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-900 px-5 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-slate-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">← Sebelumnya</button>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="button" @click="nextQuestion" x-show="currentQuestion < {{ $attempt->attemptQuestions->count() - 1 }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">Selanjutnya →</button>
                        <button type="submit" x-show="currentQuestion === {{ $attempt->attemptQuestions->count() - 1 }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-extrabold text-slate-950 transition hover:bg-emerald-400" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan dan mengumpulkan ujian ini? Anda tidak bisa mengubah jawaban setelah ini.')">Kumpulkan Ujian</button>
                    </div>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                <section class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5 text-center">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Sisa Waktu</p>
                    <p class="mt-3 font-mono text-3xl font-black tracking-wider sm:text-4xl" :class="timeRemaining <= 300 ? 'animate-pulse text-rose-300' : 'text-white'" x-text="formattedTime">--:--:--</p>
                    <p class="mt-2 text-xs leading-5 text-slate-500">Timer dihitung dari server dan attempt akan berakhir saat waktu habis.</p>
                    <button type="submit" class="mt-5 inline-flex w-full items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700" onclick="return confirm('Kumpulkan ujian sekarang?')">Kumpulkan Sekarang</button>
                </section>

                <section class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Question Map</p>
                            <h2 class="mt-1 text-sm font-bold text-white">Navigasi Soal</h2>
                        </div>
                        <span class="rounded-lg border border-slate-700 bg-slate-950/60 px-2 py-1 text-[10px] font-bold text-slate-500"><span x-text="answeredCount"></span> dijawab</span>
                    </div>

                    <div class="mt-4 grid grid-cols-5 gap-2">
                        @foreach($attempt->attemptQuestions as $index => $attemptQuestion)
                            <button type="button" @click="currentQuestion = {{ $index }}" class="flex h-10 items-center justify-center rounded-lg border text-sm font-bold transition"
                                :class="{
                                    'border-indigo-500 bg-indigo-600 text-white shadow-lg shadow-indigo-950/20': currentQuestion === {{ $index }},
                                    'border-emerald-500/25 bg-emerald-500/10 text-emerald-300': answers[{{ $attemptQuestion->question_id }}] && currentQuestion !== {{ $index }},
                                    'border-slate-700 bg-slate-950/60 text-slate-400 hover:border-slate-600 hover:text-white': !answers[{{ $attemptQuestion->question_id }}] && currentQuestion !== {{ $index }}
                                }">{{ $index + 1 }}</button>
                        @endforeach
                    </div>

                    <div class="mt-5 grid gap-2 text-[11px] text-slate-500">
                        <div class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span> Soal saat ini</div>
                        <div class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span> Sudah dijawab</div>
                        <div class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full border border-slate-600 bg-slate-900"></span> Belum dijawab</div>
                    </div>
                </section>
            </aside>
        </div>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('quizExam', () => ({
        currentQuestion: 0,
        totalQuestions: {{ $attempt->attemptQuestions->count() }},
        answers: {},
        timeRemaining: {{ $attempt->expires_at ? now()->diffInSeconds($attempt->expires_at, false) : 99999999 }},
        timerInterval: null,

        init() {
            const savedAnswers = sessionStorage.getItem('quiz_attempt_{{ $attempt->id }}');
            if (savedAnswers) {
                try {
                    this.answers = JSON.parse(savedAnswers) ?? {};
                } catch (error) {
                    sessionStorage.removeItem('quiz_attempt_{{ $attempt->id }}');
                    this.answers = {};
                }
            }

            this.$watch('answers', value => {
                sessionStorage.setItem('quiz_attempt_{{ $attempt->id }}', JSON.stringify(value));
            });

            if (this.timeRemaining > 0 && this.timeRemaining < 999999) {
                this.timerInterval = setInterval(() => {
                    this.timeRemaining--;
                    if (this.timeRemaining <= 0) {
                        clearInterval(this.timerInterval);
                        sessionStorage.removeItem('quiz_attempt_{{ $attempt->id }}');
                        alert('Waktu ujian telah habis! Jawaban Anda akan otomatis dikumpulkan.');
                        document.getElementById('examForm').submit();
                    }
                }, 1000);
            }
        },

        get answeredCount() {
            return Object.values(this.answers).filter(value => value !== null && value !== '').length;
        },

        nextQuestion() {
            if (this.currentQuestion < this.totalQuestions - 1) {
                this.currentQuestion++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prevQuestion() {
            if (this.currentQuestion > 0) {
                this.currentQuestion--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        get formattedTime() {
            if (this.timeRemaining >= 999999) return 'Tanpa Batas';
            if (this.timeRemaining <= 0) return '00:00:00';

            const h = Math.floor(this.timeRemaining / 3600).toString().padStart(2, '0');
            const m = Math.floor((this.timeRemaining % 3600) / 60).toString().padStart(2, '0');
            const s = Math.floor(this.timeRemaining % 60).toString().padStart(2, '0');

            return `${h}:${m}:${s}`;
        }
    }));
});
</script>
@endsection