@extends('layouts.app')

@php
    $title = 'Status Ekstraksi Soal AI';
    $breadcrumb = 'Bank Soal';

    $status = match ($state) {
        'queued' => [
            'tone' => 'amber',
            'title' => 'Menunggu Queue Worker',
            'message' => 'Dokumen sudah diterima dan job ekstraksi sudah masuk antrean, tetapi proses AI belum mulai dijalankan.',
        ],
        'processing' => [
            'tone' => 'indigo',
            'title' => 'AI Sedang Mengekstrak Soal',
            'message' => 'AI sedang membaca dokumen dan menyusun butir soal. Halaman ini akan diperbarui otomatis.',
        ],
        'failed' => [
            'tone' => 'rose',
            'title' => 'Ekstraksi AI Gagal',
            'message' => 'Proses ekstraksi tidak berhasil diselesaikan. Kembali ke bank soal untuk mencoba lagi setelah memeriksa konfigurasi AI/queue.',
        ],
        'empty' => [
            'tone' => 'amber',
            'title' => 'Tidak Ada Soal yang Dapat Disimpan',
            'message' => 'AI selesai memproses dokumen, tetapi tidak menghasilkan butir soal valid yang dapat disimpan ke bank soal.',
        ],
        default => [
            'tone' => 'slate',
            'title' => 'Belum Ada Hasil Ekstraksi',
            'message' => 'Belum ada soal hasil ekstraksi AI pada bank soal ini.',
        ],
    };

    $isPolling = in_array($state, ['queued', 'processing'], true);
    $toneClasses = match ($status['tone']) {
        'amber' => 'border-amber-500/30 bg-amber-500/10 text-amber-200',
        'indigo' => 'border-indigo-500/30 bg-indigo-500/10 text-indigo-200',
        'rose' => 'border-rose-500/30 bg-rose-500/10 text-rose-200',
        default => 'border-slate-700 bg-slate-900 text-slate-200',
    };
@endphp

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('instructor.question-banks.show', $questionBank) }}" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300">&larr; Kembali ke Bank Soal</a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">Status Ekstraksi Soal AI</h1>
        <p class="mt-2 text-base text-slate-400">{{ $questionBank->title }}</p>
    </div>

    <section class="rounded-2xl border {{ $toneClasses }} p-6 sm:p-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-950/40 text-xl font-black">
                @if($state === 'failed')
                    !
                @elseif($state === 'empty')
                    0
                @elseif($isPolling)
                    …
                @else
                    i
                @endif
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-extrabold text-white">{{ $status['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-base leading-7 text-slate-300">{{ $status['message'] }}</p>

                @if($state === 'queued')
                    <div class="mt-4 rounded-xl border border-slate-700 bg-slate-950/50 p-4 text-sm leading-6 text-slate-300">
                        Pada lingkungan lokal, status ini biasanya berarti queue worker belum aktif. Jalankan queue worker di terminal terpisah lalu biarkan halaman ini terbuka.
                    </div>
                @endif

                @if($totalQuestionCount > 0)
                    <p class="mt-4 text-sm text-slate-300">Total soal yang sudah tersedia pada bank soal: <strong class="text-white">{{ $totalQuestionCount }}</strong>.</p>
                @endif
            </div>
        </div>
    </section>

    <div class="flex flex-wrap gap-3">
        @if($isPolling)
            <a href="{{ route('instructor.question-banks.review', ['question_bank' => $questionBank, 'processing' => 1, 'started_at' => $startedAt]) }}" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-500">Refresh Sekarang</a>
        @endif
        <a href="{{ route('instructor.question-banks.show', $questionBank) }}" class="rounded-xl bg-slate-800 px-5 py-3 text-sm font-bold text-white hover:bg-slate-700">Kembali ke Bank Soal</a>
    </div>
</div>

@if($isPolling)
    <script>
        window.setTimeout(() => window.location.reload(), 3000);
    </script>
@endif
@endsection
