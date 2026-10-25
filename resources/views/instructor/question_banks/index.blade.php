@extends('layouts.app')

@php
    $title = 'Bank Soal';
    $breadcrumb = 'Bank Soal';
@endphp

@section('content')
<div class="space-y-6" x-data="{ showCreateModal: false }" @keydown.escape.window="showCreateModal = false">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Manajemen Bank Soal</h1>
            <p class="text-sm text-slate-400 mt-1">Kelola dan ekstrak soal berbasis AI untuk kuis dan evaluasi.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('instructor.quizzes.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-indigo-200 bg-indigo-500/10 border border-indigo-500/30 hover:bg-indigo-500/20 hover:text-white transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                Kelola Kuis
            </a>
            <button type="button" @click.stop="showCreateModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-lg shadow-indigo-600/25 transition-all">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Buat Bank Soal
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-800">
        <form action="{{ route('instructor.question-banks.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-400 mb-1">Cari Bank Soal</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Judul atau deskripsi..." class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
            </div>
            <div class="w-full sm:w-64">
                <label class="block text-xs font-medium text-slate-400 mb-1">Filter Kursus</label>
                <select name="course_id" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="">Semua Kursus (Global & Spesifik)</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium rounded-lg transition-colors">Filter</button>
            @if(request()->hasAny(['search', 'course_id']))
                <a href="{{ route('instructor.question-banks.index') }}" class="px-4 py-2 text-slate-400 hover:text-white text-sm font-medium transition-colors">Reset</a>
            @endif
        </form>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($questionBanks as $bank)
            <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow hover:border-slate-700 transition-all flex flex-col justify-between overflow-hidden relative">
                @if($bank->status === 'archived')
                    <div class="absolute inset-0 bg-slate-950/40 z-10 pointer-events-none"></div>
                @endif
                <div class="p-5 space-y-3 relative z-20">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 max-w-[150px] truncate" title="{{ $bank->course?->title ?? 'Global (Semua Kursus)' }}">
                            {{ $bank->course?->title ?? 'Global (Semua Kursus)' }}
                        </span>
                        @if($bank->status === 'active')
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Active</span>
                        @else
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-slate-500/20 text-slate-400 border border-slate-500/30">Archived</span>
                        @endif
                    </div>

                    <h3 class="text-base font-bold text-white line-clamp-1 hover:text-indigo-400 transition-colors">
                        <a href="{{ route('instructor.question-banks.show', $bank) }}">{{ $bank->title }}</a>
                    </h3>

                    <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed min-h-[2.5rem]">
                        {{ $bank->description ?? 'Tidak ada deskripsi.' }}
                    </p>

                    <div class="flex items-center gap-4 text-xs text-slate-500 pt-2 border-t border-slate-800/80">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ $bank->questions_count }} Pertanyaan
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            {{ $bank->documents_count }} Dokumen
                        </span>
                    </div>
                </div>

                <div class="px-5 py-3.5 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between relative z-20">
                    <a href="{{ route('instructor.question-banks.show', $bank) }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1">
                        <span>Buka Bank Soal</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>

                    <form action="{{ route('instructor.question-banks.destroy', $bank) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus bank soal ini? Semua pertanyaan di dalamnya juga akan terhapus.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 transition-colors">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center rounded-2xl bg-slate-900/60 border border-slate-800">
                <div class="w-12 h-12 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                </div>
                <h3 class="text-base font-bold text-white">Belum Ada Bank Soal</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Mulai buat bank soal untuk menampung pertanyaan kuis dan evaluasi.</p>
                <button type="button" @click.stop="showCreateModal = true" class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors">
                    + Buat Bank Soal Pertama
                </button>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($questionBanks->hasPages())
        <div class="pt-4">
            {{ $questionBanks->links() }}
        </div>
    @endif

    <!-- Create Modal (AlpineJS) -->
    <div x-show="showCreateModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <button type="button" x-show="showCreateModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showCreateModal = false" class="fixed inset-0 w-full h-full bg-slate-950/80 backdrop-blur-sm transition-opacity cursor-default" aria-label="Tutup modal"></button>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div @click.stop x-show="showCreateModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative inline-block align-bottom bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-800">
                <form action="{{ route('instructor.question-banks.store') }}" method="POST">
                    @csrf
                    <div class="px-6 py-5 border-b border-slate-800">
                        <h3 class="text-lg font-bold text-white" id="modal-title">Buat Bank Soal Baru</h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Judul Bank Soal <span class="text-rose-500">*</span></label>
                            <input type="text" name="title" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Kaitkan dengan Kursus (Opsional)</label>
                            <select name="course_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">Bank Soal Global</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1">Pilih "Global" jika bank soal ini akan digunakan di berbagai kursus berbeda.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Deskripsi Singkat</label>
                            <textarea name="description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-950/50 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors">Simpan & Lanjutkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
