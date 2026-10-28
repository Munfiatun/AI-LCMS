@extends('layouts.app')

@php
    $title = 'Detail Kuis';
    $breadcrumb = 'Kuis & Nilai';
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('student.quizzes.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-slate-400 hover:text-white">← Kembali ke Kuis & Nilai</a>

    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl sm:p-8">
        <p class="text-sm font-semibold text-indigo-300">{{ $quiz->course->title }}</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $quiz->title }}</h1>
        @if($quiz->description)
            <p class="mt-3 max-w-2xl text-base leading-7 text-slate-400">{{ $quiz->description }}</p>
        @endif

        <div class="mt-6 grid grid-cols-3 gap-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/45 p-4 text-center">
                <p class="text-xs text-slate-500">Soal</p>
                <p class="mt-1 text-xl font-extrabold text-white">{{ $quiz->total_questions }}</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/45 p-4 text-center">
                <p class="text-xs text-slate-500">Durasi</p>
                <p class="mt-1 text-xl font-extrabold text-white">{{ $quiz->duration_minutes ? $quiz->duration_minutes.' mnt' : '∞' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/45 p-4 text-center">
                <p class="text-xs text-slate-500">Nilai Lulus</p>
                <p class="mt-1 text-xl font-extrabold text-indigo-300">{{ $quiz->passing_score }}%</p>
            </div>
        </div>

        <p class="mt-4 text-sm text-slate-500">Percobaan: {{ $attemptsCount }} dari {{ $quiz->max_attempts }}</p>
    </section>

    @if($quiz->instructions)
        <details class="rounded-2xl border border-slate-800 bg-slate-900/60">
            <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-slate-300">Baca instruksi kuis</summary>
            <div class="border-t border-slate-800 px-5 py-4">
                <p class="whitespace-pre-line text-sm leading-6 text-slate-400">{{ $quiz->instructions }}</p>
            </div>
        </details>
    @endif

    <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6">
        @if($activeAttempt)
            <div class="grid gap-5 sm:grid-cols-[1fr_auto] sm:items-center">
                <div>
                    <span class="inline-flex rounded-full border border-amber-500/20 bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-300">Sedang Berjalan</span>
                    <h2 class="mt-3 text-lg font-bold text-white">Kuis ini belum selesai</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-400">Lanjutkan percobaan yang sama agar jawaban dan waktu tetap tersimpan.</p>
                </div>
                <a href="{{ route('student.quizzes.take', ['quiz' => $quiz->id, 'attempt' => $activeAttempt->id]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-amber-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-amber-500">Lanjutkan Kuis</a>
            </div>
        @elseif($attemptsCount >= $quiz->max_attempts)
            @php
                $lastAttempt = App\Models\QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('student_id', auth()->id())
                    ->latest()
                    ->first();
            @endphp
            <div class="grid gap-5 sm:grid-cols-[1fr_auto] sm:items-center">
                <div>
                    <span class="inline-flex rounded-full border border-rose-500/20 bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-300">Percobaan Selesai</span>
                    <h2 class="mt-3 text-lg font-bold text-white">Batas percobaan sudah tercapai</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-400">Kamu tidak dapat memulai percobaan baru untuk kuis ini.</p>
                </div>
                @if($lastAttempt)
                    <a href="{{ route('student.quizzes.result', ['quiz' => $quiz->id, 'attempt' => $lastAttempt->id]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 text-sm font-bold text-slate-200 transition hover:bg-slate-700">Lihat Hasil Terakhir</a>
                @endif
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-[1fr_auto] sm:items-center">
                <div>
                    <span class="inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-300">Siap Dikerjakan</span>
                    <h2 class="mt-3 text-lg font-bold text-white">Mulai ketika kamu siap</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-400">Timer berjalan setelah kuis dimulai. Pastikan kamu punya waktu yang cukup untuk menyelesaikannya.</p>
                </div>
                <form action="{{ route('student.quizzes.start', $quiz) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-indigo-500 sm:w-auto" onclick="return confirm('Mulai kuis sekarang? Timer akan langsung berjalan.')">Mulai Ujian Sekarang</button>
                </form>
            </div>
        @endif
    </section>
</div>
@endsection
