@extends('layouts.app')

@php
    $title = 'Bank Soal';
    $breadcrumb = 'Bank Soal';
@endphp

@section('content')
<div class="space-y-6" x-data="{ showCreateModal: false }" @keydown.escape.window="showCreateModal = false">
    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold theme-accent-text">Langkah 1 dari Evaluasi</p>
                <h1 class="teacher-page-title mt-1 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Bank Soal</h1>
                <p class="mt-2 text-base leading-7 theme-body">Kumpulkan soal di sini terlebih dahulu. Setelah siap, lanjutkan ke Kuis untuk menyusun assessment yang akan dikerjakan siswa.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('instructor.quizzes.index') }}" class="teacher-secondary-button inline-flex items-center justify-center rounded-xl border theme-border theme-muted-surface px-4 py-2.5 text-sm font-semibold theme-heading transition theme-muted-surface-hover">Lanjut ke Kuis →</a>
                <button type="button" @click="showCreateModal = true" class="inline-flex items-center justify-center gap-2 rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid transition theme-accent-fill-hover">
                    <span>+</span> Buat Bank Soal
                </button>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-3">
        <div class="rounded-2xl border teacher-step-card teacher-step-active p-4" aria-current="step">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl teacher-step-number text-sm font-extrabold">1</div>
            <h2 class="mt-3 font-bold theme-heading">Buat bank soal</h2>
            <p class="mt-1 text-sm leading-6 theme-muted">Pilih kursus agar soal mudah dipakai kembali.</p>
        </div>
        <div class="rounded-2xl border teacher-step-card p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl teacher-step-number text-sm font-extrabold">2</div>
            <h2 class="mt-3 font-bold theme-heading">Tambah soal</h2>
            <p class="mt-1 text-sm leading-6 theme-muted">Tulis manual, buat dari prompt AI, atau unggah dokumen untuk ekstraksi.</p>
        </div>
        <div class="rounded-2xl border teacher-step-card p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl teacher-step-number text-sm font-extrabold">3</div>
            <h2 class="mt-3 font-bold theme-heading">Gunakan di kuis</h2>
            <p class="mt-1 text-sm leading-6 theme-muted">Soal terverifikasi dapat dipilih melalui Builder Kuis.</p>
        </div>
    </section>

    <details class="rounded-2xl border teacher-card">
        <summary class="cursor-pointer px-5 py-4 text-sm font-semibold theme-body">Cari atau filter bank soal</summary>
        <form action="{{ route('instructor.question-banks.index') }}" method="GET" class="grid gap-4 border-t theme-border p-5 md:grid-cols-[1fr_260px_auto] md:items-end">
            <div>
                <label class="mb-2 block text-sm font-semibold theme-body">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Judul atau deskripsi..." class="w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold theme-body">Kursus</label>
                <select name="course_id" class="w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                    <option value="">Semua kursus</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="teacher-secondary-button rounded-xl theme-muted-surface px-4 py-2.5 text-sm font-semibold theme-heading theme-muted-surface-hover">Terapkan</button>
                @if(request()->hasAny(['search', 'course_id']))
                    <a href="{{ route('instructor.question-banks.index') }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold theme-body theme-heading-hover">Reset</a>
                @endif
            </div>
        </form>
    </details>

    <section class="space-y-4">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold theme-heading">Koleksi Bank Soal</h2>
                <p class="mt-1 text-sm theme-muted">Buka satu bank untuk menambah, mereview, atau menyiapkan soal.</p>
            </div>
            <span class="text-sm theme-muted">{{ $questionBanks->total() }} bank</span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($questionBanks as $bank)
                <article class="flex min-h-[210px] flex-col rounded-2xl border teacher-card {{ $bank->status === 'archived' ? 'opacity-70' : '' }}">
                    <div class="flex-1 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="max-w-[70%] truncate rounded-full border theme-accent-border theme-accent-surface px-2.5 py-1 text-sm font-semibold theme-accent-text" title="{{ $bank->course?->title ?? 'Global' }}">
                                {{ $bank->course?->title ?? 'Global' }}
                            </span>
                            <span class="rounded-full border px-2.5 py-1 text-sm font-semibold {{ $bank->status === 'active' ? 'theme-success-border theme-success-surface theme-success-text' : 'theme-border theme-muted-surface theme-body' }}">
                                {{ $bank->status === 'active' ? 'Aktif' : 'Arsip' }}
                            </span>
                        </div>

                        <h3 class="mt-4 text-lg font-bold leading-snug theme-heading">{{ $bank->title }}</h3>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 theme-muted">{{ $bank->description ?? 'Belum ada deskripsi.' }}</p>

                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm theme-body">
                            <span><strong class="theme-heading">{{ $bank->questions_count }}</strong> soal</span>
                            <span><strong class="theme-heading">{{ $bank->documents_count }}</strong> dokumen</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 border-t theme-border p-4">
                        <a href="{{ route('instructor.question-banks.show', $bank) }}" class="flex-1 rounded-xl theme-accent-fill px-4 py-2.5 text-center text-sm font-bold teacher-on-solid transition theme-accent-fill-hover">Buka Bank Soal</a>
                        @if($bank->status === 'active')
                            <a href="{{ route('instructor.question-banks.ai-authoring.create', $bank) }}" class="teacher-secondary-button inline-flex h-10 items-center justify-center rounded-xl border theme-accent-border theme-accent-surface px-3 text-sm font-bold theme-accent-text transition theme-accent-surface-hover" title="Buat soal dari prompt AI">✨ AI</a>
                        @endif
                        <details class="relative">
                            <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-xl border theme-border theme-body theme-muted-surface-hover theme-heading-hover" aria-label="Opsi lainnya">•••</summary>
                            <div class="absolute bottom-12 right-0 z-20 w-40 rounded-xl border teacher-card p-2 shadow-2xl">
                                <form action="{{ route('instructor.question-banks.destroy', $bank) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus bank soal ini? Semua pertanyaan di dalamnya juga akan terhapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold theme-danger-text theme-danger-surface-hover">Hapus bank soal</button>
                                </form>
                            </div>
                        </details>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed teacher-card p-10 text-center">
                    <h3 class="text-lg font-bold theme-heading">Belum ada bank soal</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 theme-muted">Buat satu bank soal untuk kursus, lalu tambahkan pertanyaan manual, buat dari prompt AI, atau ekstrak dari dokumen.</p>
                    <button type="button" @click="showCreateModal = true" class="mt-5 rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid theme-accent-fill-hover">+ Buat Bank Soal</button>
                </div>
            @endforelse
        </div>

        @if($questionBanks->hasPages())
            <div class="pt-2">{{ $questionBanks->links() }}</div>
        @endif
    </section>

    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 teacher-backdrop backdrop-blur-sm" @click="showCreateModal = false"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border teacher-card shadow-2xl">
                <form action="{{ route('instructor.question-banks.store') }}" method="POST">
                    @csrf
                    <div class="border-b theme-border px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold theme-heading">Buat Bank Soal</h3>
                                <p class="mt-1 text-sm leading-6 theme-muted">Sebaiknya pilih kursus agar soal lebih mudah dipakai untuk evaluasi adaptif.</p>
                            </div>
                            <button type="button" @click="showCreateModal = false" class="teacher-secondary-button rounded-lg p-1.5 theme-muted theme-muted-surface-hover theme-heading-hover" aria-label="Tutup">✕</button>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <label class="block text-sm font-semibold theme-body">Judul Bank Soal
                            <input type="text" name="title" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                        </label>

                        <label class="block text-sm font-semibold theme-body">Kursus
                            <select name="course_id" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                                <option value="">Global / dapat digunakan lintas kursus</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                            <span class="mt-2 block text-sm leading-5 theme-muted">Untuk alur adaptive learning, gunakan bank yang terhubung ke satu kursus.</span>
                        </label>

                        <label class="block text-sm font-semibold theme-body">Deskripsi <span class="font-normal theme-muted">(opsional)</span>
                            <textarea name="description" rows="3" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border"></textarea>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 border-t teacher-card px-6 py-4">
                        <button type="button" @click="showCreateModal = false" class="teacher-secondary-button rounded-xl border theme-border px-4 py-2.5 text-sm font-semibold theme-body theme-muted-surface-hover">Batal</button>
                        <button type="submit" class="rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Buat & Tambah Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection