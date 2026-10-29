@extends('layouts.app')

@php
    $title = 'Buat Soal dengan AI';
    $breadcrumb = 'Bank Soal';
@endphp

@section('content')
<div class="space-y-6">
    @if($errors->any())
        <div role="alert" class="rounded-2xl border theme-danger-border theme-danger-surface p-4 text-sm theme-danger-text">
            <p class="font-bold">AI belum dapat memproses permintaan:</p>
            <div class="mt-2 space-y-1">
                @foreach($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <a href="{{ route('instructor.question-banks.show', $questionBank) }}" class="text-sm font-semibold theme-muted transition theme-accent-text-hover">← Kembali ke {{ $questionBank->title }}</a>
                <p class="mt-4 text-sm font-semibold theme-accent-text">AI Authoring Assistant</p>
                <h1 class="teacher-page-title mt-1 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Buat Soal dengan AI</h1>
                <p class="mt-2 text-base leading-7 theme-body">Gunakan prompt saja atau pilih materi dari kursus sebagai sumber. AI membuat draft, lalu Anda tetap meninjau redaksi dan kunci sebelum soal masuk ke bank.</p>
            </div>
            <div class="rounded-2xl border teacher-card px-4 py-3 text-sm">
                <p class="font-semibold theme-heading">Provider aktif</p>
                <p class="mt-1 theme-muted">{{ strtoupper($provider) }}{{ $model ? ' · '.$model : '' }}</p>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <section class="rounded-3xl border teacher-card p-5 sm:p-6">
            <div class="mb-6">
                <h2 class="text-xl font-extrabold theme-heading">Instruksi Pembuatan Soal</h2>
                <p class="mt-1 text-sm leading-6 theme-muted">Versi ini mendukung pilihan ganda 4 opsi dan grounding dari materi yang sudah tersimpan di kursus.</p>
            </div>

            <form action="{{ route('instructor.question-banks.ai-authoring.generate', $questionBank) }}" method="POST" class="space-y-5">
                @csrf

                <label class="block text-sm font-semibold theme-body">Topik utama
                    <input type="text" name="topic" value="{{ old('topic') }}" required maxlength="255" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border" placeholder="Contoh: Percabangan Java">
                    <span class="mt-2 block text-sm leading-5 theme-muted">Gunakan topik yang cukup spesifik agar hasil AI lebih terarah.</span>
                </label>

                <div class="rounded-2xl border theme-accent-border theme-accent-surface p-4">
                    <label class="block text-sm font-semibold theme-accent-text">Sumber materi <span class="font-normal theme-muted">(opsional)</span>
                        <select name="source_material_id" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                            <option value="">Prompt saja · gunakan pengetahuan model</option>
                            @foreach($sourceMaterials as $material)
                                <option value="{{ $material->id }}" @selected((string) old('source_material_id') === (string) $material->id)>
                                    {{ $material->section?->title ? $material->section->title.' · ' : '' }}{{ $material->title }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @if($questionBank->course && $sourceMaterials->isNotEmpty())
                        <p class="mt-2 text-sm leading-5 theme-body">Jika materi dipilih, fakta dan kunci jawaban akan dibatasi pada konten materi tersebut, termasuk hasil ekstraksi dokumen yang sudah tersedia.</p>
                    @elseif($questionBank->course)
                        <p class="mt-2 text-sm leading-5 theme-muted">Belum ada materi pada kursus ini yang dapat dipilih. Anda tetap bisa memakai mode Prompt saja.</p>
                    @else
                        <p class="mt-2 text-sm leading-5 theme-muted">Bank soal ini bersifat global, jadi sumber materi kursus belum tersedia.</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-semibold theme-body">Jumlah soal
                        <select name="total_questions" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                            @foreach([2, 3, 5, 10, 15, 20] as $count)
                                <option value="{{ $count }}" @selected((int) old('total_questions', 5) === $count)>{{ $count }} soal</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block text-sm font-semibold theme-body">Kesulitan
                        <select name="difficulty" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                            <option value="easy" @selected(old('difficulty') === 'easy')>Mudah</option>
                            <option value="medium" @selected(old('difficulty', 'medium') === 'medium')>Sedang</option>
                            <option value="hard" @selected(old('difficulty') === 'hard')>Sulit</option>
                            <option value="mixed" @selected(old('difficulty') === 'mixed')>Campuran</option>
                        </select>
                    </label>
                </div>

                <label class="block text-sm font-semibold theme-body">Instruksi tambahan <span class="font-normal theme-muted">(opsional)</span>
                    <textarea name="instructions" rows="5" maxlength="2000" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-6 theme-heading theme-focus-border" placeholder="Contoh: Buat 10 soal penerapan. Fokus pada kemampuan siswa menentukan output dan memilih struktur if-else yang tepat.">{{ old('instructions') }}</textarea>
                </label>

                <div class="rounded-2xl border theme-warning-border theme-warning-surface p-4 text-sm leading-6 theme-warning-text">
                    <strong>Teacher-in-the-loop:</strong> materi sumber membantu grounding, tetapi hasil AI tetap berupa draft. Periksa redaksi, kecocokan dengan materi, dan kunci jawaban sebelum menyimpan.
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('instructor.question-banks.show', $questionBank) }}" class="teacher-secondary-button rounded-xl border theme-border px-5 py-3 text-center text-sm font-semibold theme-body theme-muted-surface-hover">Batal</a>
                    <button type="submit" class="rounded-xl theme-accent-fill px-6 py-3 text-sm font-extrabold teacher-on-solid transition theme-accent-fill-hover">✨ Buat Draft Soal</button>
                </div>
            </form>
        </section>

        <aside class="space-y-4">
            <div class="rounded-2xl border teacher-card p-5">
                <p class="text-sm font-semibold theme-accent-text">Bank Soal</p>
                <h2 class="mt-1 font-extrabold theme-heading">{{ $questionBank->title }}</h2>
                <p class="mt-2 text-sm leading-6 theme-muted">{{ $questionBank->course?->title ?? 'Global / lintas kursus' }}</p>
            </div>

            <div class="rounded-2xl border teacher-card p-5">
                <h2 class="font-extrabold theme-heading">Contoh instruksi</h2>
                <p class="mt-3 text-sm leading-6 theme-body">Pilih materi “Percabangan Java”, lalu tulis: “Buat soal penerapan untuk kelas X RPL. Gunakan kasus sederhana dan jangan keluar dari materi sumber.”</p>
                <p class="mt-3 text-sm leading-6 theme-muted">Jika tidak memilih sumber, mode Prompt saja tetap tersedia seperti sebelumnya.</p>
            </div>

            <div class="rounded-2xl border theme-accent-border theme-accent-surface p-5">
                <p class="text-sm font-bold theme-accent-text">Alur aman</p>
                <p class="mt-2 text-sm leading-6 theme-body">Prompt + Materi → Draft AI → Review & edit guru → Simpan ke Bank Soal.</p>
            </div>
        </aside>
    </div>
</div>
@endsection
