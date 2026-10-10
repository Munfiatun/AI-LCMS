@extends('layouts.app')

@php
    $title = 'Detail Kuis';
    $breadcrumb = 'Detail Kuis';
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <section class="relative overflow-hidden rounded-3xl border border-indigo-500/20 bg-slate-900/80 shadow-2xl shadow-black/10">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-indigo-400/70 to-transparent"></div>
        <div class="p-6 sm:p-8 lg:p-10">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[0.18em] text-indigo-300">Assessment</span>
                    <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $quiz->title }}</h1>
                    <p class="mt-2 text-sm font-semibold text-indigo-300/90">{{ $quiz->course->title }}</p>
                    <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-400">{{ $quiz->description ?? 'Tidak ada deskripsi.' }}</p>
                </div>

                <a href="{{ route('student.quizzes.index') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-slate-600 hover:text-white">Kembali ke Kuis & Nilai</a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Durasi</p>
                    <p class="mt-2 text-xl font-extrabold text-white">{{ $quiz->duration_minutes ? $quiz->duration_minutes . ' Menit' : 'Tanpa batas' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Total Soal</p>
                    <p class="mt-2 text-xl font-extrabold text-white">{{ $quiz->total_questions }}</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Nilai Lulus</p>
                    <p class="mt-2 text-xl font-extrabold text-indigo-300">{{ $quiz->passing_score }}%</p>
                </div>
                <div class="rounded-2xl border {{ $attemptsCount >= $quiz->max_attempts ? 'border-rose-500/20 bg-rose-500/[0.04]' : 'border-slate-800 bg-slate-950/50' }} p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Percobaan</p>
                    <p class="mt-2 text-xl font-extrabold {{ $attemptsCount >= $quiz->max_attempts ? 'text-rose-300' : 'text-white' }}">{{ $attemptsCount }} / {{ $quiz->max_attempts }}</p>
                </div>
            </div>
        </div>
    </section>

    @if($quiz->instructions)
        <section class="rounded-2xl border border-slate-800 bg-slate-900/65 p-5 sm:p-6">
            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">Instruksi</p>
            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-300">{{ $quiz->instructions }}</p>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6">
        @if($activeAttempt)
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="inline-flex rounded-full border border-amber-500/20 bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-300">Sedang Berjalan</span>
                    <h2 class="mt-3 text-lg font-bold text-white">Attempt Anda belum selesai</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-400">Lanjutkan dari attempt yang sama agar jawaban dan timer tetap konsisten.</p>
                </div>
                <a href="{{ route('student.quizzes.take', ['quiz' => $quiz->id, 'attempt' => $activeAttempt->id]) }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-950/25 transition hover:bg-indigo-500">Lanjutkan Ujian (Sedang Berjalan)</a>
            </div>
        @elseif($attemptsCount >= $quiz->max_attempts)
            @php
                $lastAttempt = App\Models\QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('student_id', auth()->id())
                    ->latest()
                    ->first();
            @endphp
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="inline-flex rounded-full border border-rose-500/20 bg-rose-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-rose-300">Attempt Selesai</span>
                    <h2 class="mt-3 text-lg font-bold text-white">Batas maksimal percobaan telah tercapai</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-400">Anda telah mencapai batas maksimal percobaan ujian ini.</p>
                </div>
                @if($lastAttempt)
                    <a href="{{ route('student.quizzes.result', ['quiz' => $quiz->id, 'attempt' => $lastAttempt->id]) }}" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-5 py-3 text-sm font-bold text-indigo-200 transition hover:bg-indigo-500/20">Lihat Hasil Terakhir</a>
                @endif
            </div>
        @else
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-300">Siap Dikerjakan</span>
                    <h2 class="mt-3 text-lg font-bold text-white">Mulai saat Anda sudah siap</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-400">Setelah ujian dimulai, timer akan berjalan. Pastikan koneksi dan waktu Anda cukup sebelum memulai.</p>
                </div>
                <form action="{{ route('student.quizzes.start', $quiz) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-950/25 transition hover:bg-indigo-500 sm:w-auto" onclick="return confirm('Apakah Anda yakin ingin memulai ujian sekarang? Timer akan mulai berjalan setelah Anda menekan OK.')">Mulai Ujian Sekarang</button>
                </form>
            </div>
        @endif
    </section>
</div>
@endsection