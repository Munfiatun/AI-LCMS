@extends('layouts.app')

@php
    $title = 'Buat Kursus Baru';
    $breadcrumb = 'Buat Kursus';
@endphp

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Buat Kursus Baru</h1>
            <p class="mt-1 text-sm text-slate-400">Lengkapi informasi umum kursus sebelum menambahkan bab dan modul pembelajaran.</p>
        </div>
        <a href="{{ route('instructor.courses.index') }}" class="text-xs font-semibold text-slate-400 transition-colors hover:text-white">&larr; Kembali</a>
    </div>

    @if($errors->any())
        <x-ui.alert tone="danger" title="Periksa kembali data kursus">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>&bull; {{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <x-ui.panel class="shadow">
        <form action="{{ route('instructor.courses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label for="title" class="ui-label">Judul Kursus <span class="text-rose-400">*</span></label>
                <input type="text" id="title" name="title" required value="{{ old('title') }}" class="ui-field" placeholder="Contoh: Pemrograman Web dengan Laravel 13">
            </div>

            <div>
                <label for="category_id" class="ui-label">Kategori Kursus</label>
                <select id="category_id" name="category_id" class="ui-field">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="description" class="ui-label">Deskripsi Singkat</label>
                <textarea id="description" name="description" rows="4" class="ui-field" placeholder="Jelaskan tujuan dan materi yang akan dipelajari siswa...">{{ old('description') }}</textarea>
                <p class="ui-help">Gunakan deskripsi singkat yang membantu siswa memahami fokus dan tujuan kursus.</p>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-800 pt-3">
                <x-ui.button :href="route('instructor.courses.index')" variant="ghost">Batal</x-ui.button>
                <x-ui.button type="submit">Simpan & Lanjutkan ke Silabus →</x-ui.button>
            </div>
        </form>
    </x-ui.panel>
</div>
@endsection
