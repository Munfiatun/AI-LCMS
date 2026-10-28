@extends('layouts.app')

@php
    $title = 'Kuis & Nilai';
    $breadcrumb = 'Kuis & Nilai';
@endphp

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 sm:p-8">
        <p class="text-sm font-semibold text-violet-300">Assessment Overview</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Kuis & Nilai</h1>
        <p class="mt-3 max-w-2xl text-base leading-7 text-slate-400">Lihat kuis yang perlu dikerjakan dan hasil terbaru tanpa harus membaca tabel yang panjang.</p>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Ringkasan kuis">
        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
            <p class="text-sm font-semibold text-slate-400">Kuis Tersedia</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['available_quizzes'] }}</p>
        </article>
        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
            <p class="text-sm font-semibold text-slate-400">Sudah Dikerjakan</p>
            <p class="mt-2 text-3xl font-extrabold text-white">{{ $stats['attempted_quizzes'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['submitted_attempts'] }} attempt submitted</p>
        </article>
        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
            <p class="text-sm font-semibold text-slate-400">Lulus</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-300">{{ $stats['passed_quizzes'] }}</p>
        </article>
        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
            <p class="text-sm font-semibold text-slate-400">Rata-Rata Nilai</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-300">{{ number_format($stats['average_score'], 1) }}%</p>
        </article>
    </section>

    @if($recentAttempts->isNotEmpty())
        <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6">
            <div>
                <h2 class="text-xl font-extrabold text-white">Riwayat Nilai Terbaru</h2>
                <p class="mt-1 text-sm text-slate-500">Hasil terbaru dari kuis yang sudah selesai.</p>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                @foreach($recentAttempts as $attempt)
                    @php
                        $passed = $attempt->percentage !== null && $attempt->percentage >= (float) $attempt->quiz->passing_score;
                    @endphp
                    <article class="rounded-2xl border border-slate-800 bg-slate-950/45 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-bold text-white">{{ $attempt->quiz->title }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $attempt->quiz->course->title }}</p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $passed ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/20 bg-amber-500/10 text-amber-300' }}">{{ $passed ? 'Lulus' : 'Belum Lulus' }}</span>
                        </div>

                        <div class="mt-4 flex items-end justify-between gap-4">
                            <div>
                                <p class="text-xs text-slate-500">Nilai</p>
                                <p class="mt-1 text-2xl font-extrabold text-white">{{ number_format((float) $attempt->percentage, 1) }}%</p>
                            </div>
                            <a href="{{ route('student.quizzes.result', ['quiz' => $attempt->quiz, 'attempt' => $attempt]) }}" class="text-sm font-semibold text-violet-300 hover:text-violet-200">Lihat Hasil →</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="space-y-4">
        <div>
            <h2 class="text-xl font-extrabold text-white">Kuis yang Tersedia</h2>
            <p class="mt-1 text-sm text-slate-500">Setiap kartu menampilkan satu tindakan utama sesuai status kuis.</p>
        </div>

        @if($quizzes->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                <h3 class="font-bold text-white">Belum ada kuis yang tersedia.</h3>
                <p class="mt-2 text-sm text-slate-500">Kuis akan muncul setelah guru menerbitkannya pada kursus yang kamu ikuti.</p>
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($quizzes as $quiz)
                    @php
                        $quizAttempts = $attempts->get($quiz->id, collect());
                        $latestAttempt = $quizAttempts->last();
                        $submitted = $quizAttempts->where('status', 'submitted');
                        $bestPercentage = $submitted->whereNotNull('percentage')->max('percentage');
                        $hasPassed = $submitted->contains(fn ($attempt) => $attempt->percentage !== null && $attempt->percentage >= (float) $quiz->passing_score);

                        $status = 'Belum Dikerjakan';
                        $statusClass = 'border-slate-700 bg-slate-800 text-slate-300';
                        if ($latestAttempt?->status === 'in_progress') {
                            $status = 'Sedang Dikerjakan';
                            $statusClass = 'border-amber-500/20 bg-amber-500/10 text-amber-300';
                        } elseif ($hasPassed) {
                            $status = 'Lulus';
                            $statusClass = 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300';
                        } elseif ($submitted->isNotEmpty()) {
                            $status = 'Belum Lulus';
                            $statusClass = 'border-amber-500/20 bg-amber-500/10 text-amber-300';
                        } elseif ($latestAttempt?->status === 'expired') {
                            $status = 'Waktu Habis';
                            $statusClass = 'border-rose-500/20 bg-rose-500/10 text-rose-300';
                        }
                    @endphp

                    <article class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm text-slate-500">{{ $quiz->course->title }}</p>
                                <h3 class="mt-1 text-lg font-extrabold text-white">{{ $quiz->title }}</h3>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-3">
                                <p class="text-xs text-slate-500">Soal</p>
                                <p class="mt-1 font-bold text-white">{{ $quiz->total_questions }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-3">
                                <p class="text-xs text-slate-500">Durasi</p>
                                <p class="mt-1 font-bold text-white">{{ $quiz->duration_minutes }} mnt</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/45 p-3">
                                <p class="text-xs text-slate-500">Nilai Terbaik</p>
                                <p class="mt-1 font-bold text-white">{{ $bestPercentage !== null ? number_format((float) $bestPercentage, 1).'%' : '—' }}</p>
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-slate-500">Percobaan {{ $quizAttempts->count() }}{{ $quiz->max_attempts ? ' dari '.$quiz->max_attempts : '' }} · nilai lulus {{ number_format($quiz->passing_score, 0) }}%</p>

                        <div class="mt-5">
                            @if($latestAttempt?->status === 'in_progress')
                                <a href="{{ route('student.quizzes.take', ['quiz' => $quiz, 'attempt' => $latestAttempt]) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-amber-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-amber-500">Lanjutkan Kuis</a>
                            @elseif($latestAttempt?->status === 'expired')
                                <a href="{{ route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $latestAttempt]) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-3 text-sm font-bold text-slate-200 transition hover:bg-slate-700">Lihat Hasil</a>
                            @elseif($submitted->isNotEmpty())
                                <a href="{{ route('student.quizzes.show', $quiz) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-indigo-500">{{ $hasPassed ? 'Buka Kuis' : 'Coba Lagi' }}</a>
                                <a href="{{ route('student.quizzes.result', ['quiz' => $quiz, 'attempt' => $submitted->last()]) }}" class="mt-3 block text-center text-sm font-semibold text-slate-400 hover:text-white">Lihat hasil terakhir</a>
                            @else
                                <a href="{{ route('student.quizzes.show', $quiz) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-indigo-500">Kerjakan Kuis</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div>{{ $quizzes->links() }}</div>
        @endif
    </section>
</div>
@endsection
