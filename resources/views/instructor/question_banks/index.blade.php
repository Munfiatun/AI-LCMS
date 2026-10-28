@extends('layouts.app')

@php
    $title = 'Bank Soal';
    $breadcrumb = 'Bank Soal';
@endphp

@section('content')
<div class="space-y-6" x-data="{ showCreateModal: false }" @keydown.escape.window="showCreateModal = false">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/75 p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-indigo-300">Langkah 1 dari Evaluasi</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Bank Soal</h1>
                <p class="mt-2 text-base leading-7 text-slate-400">Kumpulkan soal di sini terlebih dahulu. Setelah siap, lanjutkan ke Kuis untuk menyusun assessment yang akan dikerjakan siswa.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('instructor.quizzes.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">Lanjut ke Kuis →</a>
                <button type="button" @click="showCreateModal = true" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">
                    <span>+</span> Buat Bank Soal
                </button>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-3">
        <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/[0.06] p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-600 text-sm font-extrabold text-white">1</div>
            <h2 class="mt-3 font-bold text-white">Buat bank soal</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Pilih kursus agar soal mudah dipakai kembali.</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-800 text-sm font-extrabold text-slate-200">2</div>
            <h2 class="mt-3 font-bold text-white">Tambah soal</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Tulis manual atau unggah dokumen untuk ekstraksi AI.</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-800 text-sm font-extrabold text-slate-200">3</div>
            <h2 class="mt-3 font-bold text-white">Gunakan di kuis</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Soal terverifikasi dapat dipilih melalui Builder Kuis.</p>
        </div>
    </section>

    <details class="rounded-2xl border border-slate-800 bg-slate-900/60">
        <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-slate-300">Cari atau filter bank soal</summary>
        <form action="{{ route('instructor.question-banks.index') }}" method="GET" class="grid gap-4 border-t border-slate-800 p-5 md:grid-cols-[1fr_260px_auto] md:items-end">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-400">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Judul atau deskripsi..." class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-400">Kursus</label>
                <select name="course_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500">
                    <option value="">Semua kursus</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Terapkan</button>
                @if(request()->hasAny(['search', 'course_id']))
                    <a href="{{ route('instructor.question-banks.index') }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-400 hover:text-white">Reset</a>
                @endif
            </div>
        </form>
    </details>

    <section class="space-y-4">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold text-white">Koleksi Bank Soal</h2>
                <p class="mt-1 text-sm text-slate-500">Buka satu bank untuk menambah, mereview, atau menyiapkan soal.</p>
            </div>
            <span class="text-sm text-slate-500">{{ $questionBanks->total() }} bank</span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($questionBanks as $bank)
                <article class="flex min-h-[210px] flex-col rounded-2xl border border-slate-800 bg-slate-900/70 {{ $bank->status === 'archived' ? 'opacity-70' : '' }}">
                    <div class="flex-1 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="max-w-[70%] truncate rounded-full border border-indigo-500/20 bg-indigo-500/10 px-2.5 py-1 text-xs font-semibold text-indigo-300" title="{{ $bank->course?->title ?? 'Global' }}">
                                {{ $bank->course?->title ?? 'Global' }}
                            </span>
                            <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $bank->status === 'active' ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-slate-700 bg-slate-800 text-slate-400' }}">
                                {{ $bank->status === 'active' ? 'Aktif' : 'Arsip' }}
                            </span>
                        </div>

                        <h3 class="mt-4 text-lg font-bold leading-snug text-white">{{ $bank->title }}</h3>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-500">{{ $bank->description ?? 'Belum ada deskripsi.' }}</p>

                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-400">
                            <span><strong class="text-white">{{ $bank->questions_count }}</strong> soal</span>
                            <span><strong class="text-white">{{ $bank->documents_count }}</strong> dokumen</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 border-t border-slate-800 p-4">
                        <a href="{{ route('instructor.question-banks.show', $bank) }}" class="flex-1 rounded-xl bg-indigo-600 px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-indigo-500">Buka Bank Soal</a>
                        <details class="relative">
                            <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-xl border border-slate-700 text-slate-400 hover:bg-slate-800 hover:text-white" aria-label="Opsi lainnya">•••</summary>
                            <div class="absolute bottom-12 right-0 z-20 w-40 rounded-xl border border-slate-700 bg-slate-900 p-2 shadow-2xl">
                                <form action="{{ route('instructor.question-banks.destroy', $bank) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus bank soal ini? Semua pertanyaan di dalamnya juga akan terhapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-rose-300 hover:bg-rose-500/10">Hapus bank soal</button>
                                </form>
                            </div>
                        </details>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                    <h3 class="text-lg font-bold text-white">Belum ada bank soal</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">Buat satu bank soal untuk kursus, lalu tambahkan pertanyaan manual atau ekstrak dari dokumen.</p>
                    <button type="button" @click="showCreateModal = true" class="mt-5 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">+ Buat Bank Soal</button>
                </div>
            @endforelse
        </div>

        @if($questionBanks->hasPages())
            <div class="pt-2">{{ $questionBanks->links() }}</div>
        @endif
    </section>

    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm" @click="showCreateModal = false"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl">
                <form action="{{ route('instructor.question-banks.store') }}" method="POST">
                    @csrf
                    <div class="border-b border-slate-800 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-white">Buat Bank Soal</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">Sebaiknya pilih kursus agar soal lebih mudah dipakai untuk evaluasi adaptif.</p>
                            </div>
                            <button type="button" @click="showCreateModal = false" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-800 hover:text-white" aria-label="Tutup">✕</button>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <label class="block text-sm font-semibold text-slate-300">Judul Bank Soal
                            <input type="text" name="title" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500">
                        </label>

                        <label class="block text-sm font-semibold text-slate-300">Kursus
                            <select name="course_id" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500">
                                <option value="">Global / dapat digunakan lintas kursus</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                            <span class="mt-2 block text-xs leading-5 text-slate-500">Untuk alur adaptive learning, gunakan bank yang terhubung ke satu kursus.</span>
                        </label>

                        <label class="block text-sm font-semibold text-slate-300">Deskripsi <span class="font-normal text-slate-500">(opsional)</span>
                            <textarea name="description" rows="3" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500"></textarea>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-800 bg-slate-950/40 px-6 py-4">
                        <button type="button" @click="showCreateModal = false" class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Batal</button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">Buat & Tambah Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
