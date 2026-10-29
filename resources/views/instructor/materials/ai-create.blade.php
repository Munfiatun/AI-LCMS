@extends('layouts.app')

@php
    $title = 'Buat Materi dengan AI';
    $breadcrumb = 'Materi';
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    @if($errors->any())
        <div role="alert" class="rounded-2xl border theme-danger-border theme-danger-surface p-4 text-sm theme-danger-text">
            <p class="font-bold">AI belum dapat membuat draft:</p>
            <div class="mt-2 space-y-1">
                @foreach($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <section class="teacher-page-header border p-6 sm:p-7">
        <a href="{{ route('instructor.materials.create', $section) }}" class="text-sm font-semibold theme-muted transition theme-accent-text-hover">← Kembali ke Tambah Materi</a>
        <p class="mt-4 text-sm font-semibold theme-accent-text">AI Authoring Assistant</p>
        <h1 class="teacher-page-title mt-1 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Buat Materi dengan AI</h1>
        <p class="mt-2 max-w-3xl text-base leading-7 theme-body">Tulis topik dan konteks pembelajaran. AI akan menyiapkan draft terstruktur untuk Anda review sebelum disimpan sebagai materi.</p>
        <div class="mt-4 flex flex-wrap gap-2 text-sm">
            <span class="rounded-full border theme-accent-border theme-accent-surface px-3 py-1 font-semibold theme-accent-text">{{ $section->course->title }}</span>
            <span class="rounded-full border theme-border theme-surface px-3 py-1 font-semibold theme-body">{{ $section->title }}</span>
        </div>
    </section>

    <section class="rounded-3xl border teacher-card p-5 sm:p-6">
        <form action="{{ route('instructor.materials.ai-authoring.generate', $section) }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="topic" class="block text-sm font-semibold theme-body">Topik materi <span class="theme-danger-text">*</span></label>
                <input id="topic" name="topic" type="text" required maxlength="255" value="{{ old('topic') }}" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border" placeholder="Contoh: Percabangan if-else pada Java">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="audience" class="block text-sm font-semibold theme-body">Target peserta <span class="theme-danger-text">*</span></label>
                    <input id="audience" name="audience" type="text" required maxlength="255" value="{{ old('audience', 'Siswa kelas X RPL SMK') }}" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border" placeholder="Siswa kelas X RPL SMK">
                </div>
                <div>
                    <label for="depth" class="block text-sm font-semibold theme-body">Kedalaman materi</label>
                    <select id="depth" name="depth" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                        <option value="brief" @selected(old('depth') === 'brief')>Ringkas · 10–20 menit</option>
                        <option value="standard" @selected(old('depth', 'standard') === 'standard')>Sedang · 25–45 menit</option>
                        <option value="deep" @selected(old('depth') === 'deep')>Mendalam · 45–90 menit</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="instructions" class="block text-sm font-semibold theme-body">Instruksi tambahan <span class="font-normal theme-muted">(opsional)</span></label>
                <textarea id="instructions" name="instructions" rows="5" maxlength="2000" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-6 theme-heading theme-focus-border" placeholder="Contoh: Gunakan bahasa sederhana, sertakan contoh kode Java lengkap dan contoh kasus sehari-hari. Hindari istilah yang terlalu teknis tanpa penjelasan.">{{ old('instructions') }}</textarea>
                <p class="mt-2 text-sm leading-6 theme-muted">AI hanya membuat draft. Materi tidak akan langsung dipublikasikan.</p>
            </div>

            <div class="rounded-2xl border theme-accent-border theme-accent-surface p-4">
                <p class="text-sm font-bold theme-accent-text">Alur aman</p>
                <p class="mt-1 text-sm leading-6 theme-body">Prompt → Draft AI → Review/Edit Guru → Simpan sebagai Draft Materi → Publish manual ketika siap.</p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t theme-border pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm theme-muted">Provider aktif: <strong class="theme-body">{{ strtoupper($provider) }}{{ $model ? ' · '.$model : '' }}</strong></p>
                <button type="submit" class="rounded-xl theme-accent-fill px-5 py-3 text-sm font-extrabold teacher-on-solid transition theme-accent-fill-hover">✨ Buat Draft Materi</button>
            </div>
        </form>
    </section>
</div>
@endsection
