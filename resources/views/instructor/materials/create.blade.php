@extends('layouts.app')

@php
    $title = 'Tambah Materi Baru';
    $breadcrumb = 'Tambah Materi';
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="teacher-page-header flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm theme-body mb-1">
                <a href="{{ route('instructor.courses.edit', $section->course) }}" class="theme-heading-hover">&larr; {{ $section->course->title }}</a>
                <span>&bull;</span>
                <span>{{ $section->title }}</span>
            </div>
            <h1 class="teacher-page-title text-2xl font-extrabold theme-heading tracking-tight">Tambah Materi Baru</h1>
            <p class="mt-2 text-sm leading-6 theme-body">Mulai manual atau gunakan AI untuk menyiapkan draft materi yang tetap direview guru sebelum disimpan.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl theme-danger-surface border theme-danger-border theme-danger-text text-sm space-y-1">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <a href="{{ route('instructor.materials.ai-authoring.create', $section) }}" class="flex items-center justify-between gap-4 rounded-2xl border theme-accent-border theme-accent-surface p-5 transition theme-accent-surface-hover">
        <div>
            <p class="text-sm font-bold theme-accent-text">✨ Buat dengan AI</p>
            <p class="mt-1 text-sm leading-6 theme-body">Tulis topik dan instruksi, lalu review draft terstruktur sebelum menjadi materi.</p>
        </div>
        <span class="shrink-0 text-lg font-bold theme-accent-text">→</span>
    </a>

    <div class="p-6 rounded-2xl theme-surface border theme-border shadow">
        <div class="mb-5 border-b theme-border pb-4">
            <p class="text-sm font-bold theme-heading">Atau tulis manual</p>
            <p class="mt-1 text-sm theme-muted">Isi judul dan konten sendiri, lalu unggah dokumen jika diperlukan.</p>
        </div>

        <form action="{{ route('instructor.materials.store', $section) }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="title" class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1.5">Judul Materi <span class="theme-danger-text">*</span></label>
                <input type="text" id="title" name="title" required value="{{ old('title') }}"
                       class="w-full px-4 py-2.5 theme-surface border theme-border rounded-xl theme-heading placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 text-sm"
                       placeholder="Contoh: Arsitektur Service Layer dan Form Request">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="duration_minutes" class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1.5">Estimasi Durasi (Menit)</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" min="1" value="{{ old('duration_minutes', 15) }}"
                           class="w-full px-4 py-2.5 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <p class="text-sm theme-body">Publikasi dilakukan melalui action Publish setelah materi siap.</p>
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1.5">Ringkasan / Sinopsis Materi</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-4 py-2.5 theme-surface border theme-border rounded-xl theme-heading placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 text-sm"
                          placeholder="Jelaskan intisari materi ini...">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="content" class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1.5">Konten Teks / Catatan Tambahan (Opsional)</label>
                <textarea id="content" name="content" rows="6"
                          class="w-full px-4 py-2.5 theme-surface border theme-border rounded-xl theme-heading placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 text-sm font-mono"
                          placeholder="Tuliskan materi dalam format teks atau HTML...">{{ old('content') }}</textarea>
                <p class="text-sm theme-muted mt-1">Anda juga dapat mengunggah berkas PDF/DOCX setelah materi ini disimpan.</p>
            </div>

            <div class="pt-3 flex items-center justify-end gap-3 border-t theme-border">
                <a href="{{ route('instructor.courses.edit', $section->course) }}" class="px-4 py-2 text-sm font-semibold theme-body theme-heading-hover">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold teacher-on-solid theme-accent-fill theme-accent-fill-hover rounded-xl shadow-md shadow-indigo-600/25 transition-all">
                    Simpan Materi &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
