@extends('layouts.app')

@php
    $title = 'Review Draft Materi AI';
    $breadcrumb = 'Materi';
    $material = old('material', $draft['material']);
    $settings = $draft['settings'] ?? [];
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    @if($errors->any())
        <div role="alert" class="rounded-2xl border theme-danger-border theme-danger-surface p-4 text-sm theme-danger-text">
            <p class="font-bold">Ada bagian draft yang perlu diperbaiki:</p>
            <div class="mt-2 space-y-1">
                @foreach($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <a href="{{ route('instructor.materials.ai-authoring.create', $section) }}" class="text-sm font-semibold theme-muted transition theme-accent-text-hover">← Ubah Prompt</a>
                <p class="mt-4 text-sm font-semibold theme-accent-text">AI Draft · Belum tersimpan</p>
                <h1 class="teacher-page-title mt-1 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Review Draft Materi AI</h1>
                <p class="mt-2 text-base leading-7 theme-body">Periksa isi materi sebelum disimpan. Setelah disimpan, materi tetap berstatus draft dan belum terlihat oleh siswa.</p>
            </div>
            <div class="rounded-2xl border teacher-card px-4 py-3 text-sm">
                <p class="font-semibold theme-heading">{{ count($material['sections'] ?? []) }} bagian</p>
                <p class="mt-1 theme-muted">{{ strtoupper($draft['provider'] ?? 'AI') }}{{ !empty($draft['model']) ? ' · '.$draft['model'] : '' }}</p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border theme-accent-border theme-accent-surface p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold theme-accent-text">Prompt aktif</p>
                <p class="mt-1 text-sm leading-6 theme-body"><strong>{{ $settings['topic'] ?? '-' }}</strong> · {{ $settings['audience'] ?? '-' }} · {{ ucfirst($settings['depth'] ?? 'standard') }}</p>
            </div>
            <form action="{{ route('instructor.materials.ai-authoring.generate', $section) }}" method="POST">
                @csrf
                <input type="hidden" name="topic" value="{{ $settings['topic'] ?? '' }}">
                <input type="hidden" name="audience" value="{{ $settings['audience'] ?? '' }}">
                <input type="hidden" name="depth" value="{{ $settings['depth'] ?? 'standard' }}">
                <input type="hidden" name="instructions" value="{{ $settings['instructions'] ?? '' }}">
                <input type="hidden" name="force_regenerate" value="1">
                <button type="submit" class="teacher-secondary-button rounded-xl border theme-accent-border px-4 py-2.5 text-sm font-bold theme-accent-text theme-accent-surface-hover">↻ Generate Ulang</button>
            </form>
        </div>
    </section>

    <form action="{{ route('instructor.materials.ai-authoring.store', $section) }}" method="POST" class="space-y-5">
        @csrf

        <section class="rounded-3xl border teacher-card p-5 sm:p-6">
            <div class="grid gap-5 sm:grid-cols-[1fr_180px]">
                <label class="block text-sm font-semibold theme-body">Judul Materi
                    <input type="text" name="material[title]" value="{{ $material['title'] ?? '' }}" maxlength="255" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                </label>
                <label class="block text-sm font-semibold theme-body">Durasi (menit)
                    <input type="number" name="material[duration_minutes]" value="{{ $material['duration_minutes'] ?? 30 }}" min="1" max="240" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                </label>
            </div>
            <label class="mt-5 block text-sm font-semibold theme-body">Ringkasan
                <textarea name="material[description]" rows="3" maxlength="2000" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-6 theme-heading theme-focus-border">{{ $material['description'] ?? '' }}</textarea>
            </label>
        </section>

        <section class="rounded-3xl border teacher-card p-5 sm:p-6">
            <h2 class="text-lg font-extrabold theme-heading">Tujuan Pembelajaran</h2>
            <div class="mt-4 space-y-3">
                @foreach(($material['learning_objectives'] ?? []) as $index => $objective)
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg theme-accent-surface text-sm font-bold theme-accent-text">{{ $index + 1 }}</span>
                        <input type="text" name="material[learning_objectives][{{ $index }}]" value="{{ $objective }}" maxlength="300" required class="min-w-0 flex-1 rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                    </div>
                @endforeach
            </div>
        </section>

        @foreach(($material['sections'] ?? []) as $sectionIndex => $contentSection)
            <article class="overflow-hidden rounded-3xl border teacher-card">
                <div class="border-b theme-border px-5 py-4 sm:px-6">
                    <p class="text-sm font-semibold theme-accent-text">Bagian {{ $loop->iteration }}</p>
                </div>
                <div class="space-y-4 p-5 sm:p-6">
                    <label class="block text-sm font-semibold theme-body">Judul Bagian
                        <input type="text" name="material[sections][{{ $sectionIndex }}][heading]" value="{{ $contentSection['heading'] ?? '' }}" maxlength="200" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                    </label>
                    <label class="block text-sm font-semibold theme-body">Isi
                        <textarea name="material[sections][{{ $sectionIndex }}][content]" rows="7" maxlength="8000" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-7 theme-heading theme-focus-border">{{ $contentSection['content'] ?? '' }}</textarea>
                    </label>
                    <label class="block text-sm font-semibold theme-body">Contoh / Kode <span class="font-normal theme-muted">(opsional)</span>
                        <textarea name="material[sections][{{ $sectionIndex }}][example]" rows="5" maxlength="5000" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 font-mono text-sm leading-6 theme-heading theme-focus-border">{{ $contentSection['example'] ?? '' }}</textarea>
                    </label>
                </div>
            </article>
        @endforeach

        <section class="rounded-3xl border teacher-card p-5 sm:p-6">
            <label class="block text-sm font-semibold theme-body">Rangkuman
                <textarea name="material[summary]" rows="5" maxlength="4000" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-7 theme-heading theme-focus-border">{{ $material['summary'] ?? '' }}</textarea>
            </label>

            <div class="mt-5">
                <h2 class="text-sm font-semibold theme-body">Latihan Singkat</h2>
                <div class="mt-3 space-y-3">
                    @foreach(($material['practice'] ?? []) as $index => $practice)
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg theme-muted-surface text-sm font-bold theme-body">{{ $index + 1 }}</span>
                            <input type="text" name="material[practice][{{ $index }}]" value="{{ $practice }}" maxlength="700" required class="min-w-0 flex-1 rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="sticky bottom-4 rounded-2xl border teacher-card p-4 shadow-2xl">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm leading-6 theme-muted">Simpan hanya setelah Anda memeriksa isi dan contoh. Materi tetap draft setelah disimpan.</p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <button type="submit" form="discard-ai-material" class="teacher-secondary-button rounded-xl border theme-border px-4 py-2.5 text-sm font-semibold theme-danger-text theme-danger-surface-hover">Batalkan Draft</button>
                    <button type="submit" class="rounded-xl theme-success-fill px-5 py-2.5 text-sm font-extrabold teacher-on-solid theme-success-fill-hover">✓ Simpan Draft Materi</button>
                </div>
            </div>
        </div>
    </form>

    <form id="discard-ai-material" action="{{ route('instructor.materials.ai-authoring.destroy', $section) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection
