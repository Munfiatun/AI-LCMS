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
        'amber' => 'theme-warning-border theme-warning-surface theme-warning-text',
        'indigo' => 'theme-accent-border theme-accent-surface theme-accent-text',
        'rose' => 'theme-danger-border theme-danger-surface theme-danger-text',
        default => 'theme-border theme-surface theme-heading',
    };
@endphp

@section('content')
<div class="space-y-6">
    <div class="teacher-page-header">
        <a href="{{ route('instructor.question-banks.show', $questionBank) }}" class="text-sm font-semibold theme-accent-text theme-accent-text-hover">&larr; Kembali ke Bank Soal</a>
        <h1 class="teacher-page-title mt-3 text-3xl font-extrabold tracking-tight theme-heading">Status Ekstraksi Soal AI</h1>
        <p class="mt-2 text-base theme-body">{{ $questionBank->title }}</p>
    </div>

    <section class="rounded-2xl border {{ $toneClasses }} p-6 sm:p-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full theme-surface text-xl font-black">
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
                <h2 class="text-xl font-extrabold theme-heading">{{ $status['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-base leading-7 theme-body">{{ $status['message'] }}</p>

                @if($state === 'queued')
                    <div class="mt-4 rounded-xl border teacher-card p-4 text-sm leading-6 theme-body">
                        Pada lingkungan lokal, status ini biasanya berarti queue worker belum aktif. Jalankan queue worker di terminal terpisah lalu biarkan halaman ini terbuka.
                    </div>
                @endif

                @if($totalQuestionCount > 0)
                    <p class="mt-4 text-sm theme-body">Total soal yang sudah tersedia pada bank soal: <strong class="theme-heading">{{ $totalQuestionCount }}</strong>.</p>
                @endif
            </div>
        </div>
    </section>

    <div class="flex flex-wrap gap-3">
        @if($isPolling)
            <a href="{{ route('instructor.question-banks.review', ['question_bank' => $questionBank, 'processing' => 1, 'started_at' => $startedAt]) }}" class="rounded-xl theme-accent-fill px-5 py-3 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Refresh Sekarang</a>
        @endif
        <a href="{{ route('instructor.question-banks.show', $questionBank) }}" class="teacher-secondary-button rounded-xl theme-muted-surface px-5 py-3 text-sm font-bold theme-heading theme-muted-surface-hover">Kembali ke Bank Soal</a>
    </div>
</div>

@if($isPolling)
    <script>
        window.setTimeout(() => window.location.reload(), 3000);
    </script>
@endif
@endsection
