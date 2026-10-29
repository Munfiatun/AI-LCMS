@extends('layouts.app')

@php
    $title = 'Buat Soal dengan AI';
    $breadcrumb = 'Bank Soal';
    $initialSourceMode = old('source_material_id') ? 'material' : (old('source_document_id') ? 'document' : 'prompt');
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
                <p class="mt-2 text-base leading-7 theme-body">Gunakan prompt saja, materi dalam sistem, atau PDF/Word sebagai sumber. AI membuat draft dan guru tetap melakukan review sebelum soal disimpan.</p>
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
                <p class="mt-1 text-sm leading-6 theme-muted">Pilihan ganda 4 opsi. Pilih satu sumber agar grounding AI tetap jelas dan dapat ditelusuri.</p>
            </div>

            <form action="{{ route('instructor.question-banks.ai-authoring.generate', $questionBank) }}" method="POST" enctype="multipart/form-data" class="space-y-5" x-data="{ sourceMode: @js($initialSourceMode) }">
                @csrf

                <label class="block text-sm font-semibold theme-body">Topik utama
                    <input type="text" name="topic" value="{{ old('topic') }}" required maxlength="255" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border" placeholder="Contoh: Percabangan Java">
                    <span class="mt-2 block text-sm leading-5 theme-muted">Gunakan topik yang cukup spesifik agar hasil AI lebih terarah.</span>
                </label>

                <fieldset class="rounded-2xl border theme-accent-border theme-accent-surface p-4">
                    <legend class="px-1 text-sm font-bold theme-accent-text">Sumber AI</legend>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="cursor-pointer rounded-xl border p-3 transition" :class="sourceMode === 'prompt' ? 'theme-accent-border theme-surface' : 'theme-border theme-muted-surface'">
                            <input type="radio" value="prompt" x-model="sourceMode" class="mr-2"> <span class="text-sm font-semibold theme-heading">Prompt saja</span>
                            <span class="mt-1 block text-sm leading-5 theme-muted">Gunakan arahan guru dan pengetahuan model.</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-3 transition" :class="sourceMode === 'material' ? 'theme-accent-border theme-surface' : 'theme-border theme-muted-surface'">
                            <input type="radio" value="material" x-model="sourceMode" class="mr-2"> <span class="text-sm font-semibold theme-heading">Materi sistem</span>
                            <span class="mt-1 block text-sm leading-5 theme-muted">Grounding dari materi kursus yang sudah ada.</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-3 transition" :class="sourceMode === 'document' ? 'theme-accent-border theme-surface' : 'theme-border theme-muted-surface'">
                            <input type="radio" value="document" x-model="sourceMode" class="mr-2"> <span class="text-sm font-semibold theme-heading">PDF / Word</span>
                            <span class="mt-1 block text-sm leading-5 theme-muted">Upload atau gunakan dokumen bank soal.</span>
                        </label>
                    </div>

                    <div x-show="sourceMode === 'material'" x-cloak class="mt-4">
                        <label class="block text-sm font-semibold theme-body">Pilih materi
                            <select name="source_material_id" :disabled="sourceMode !== 'material'" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                                <option value="">Pilih materi dari kursus</option>
                                @foreach($sourceMaterials as $material)
                                    <option value="{{ $material->id }}" @selected((string) old('source_material_id') === (string) $material->id)>
                                        {{ $material->section?->title ? $material->section->title.' · ' : '' }}{{ $material->title }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        @if($questionBank->course && $sourceMaterials->isNotEmpty())
                            <p class="mt-2 text-sm leading-5 theme-body">Fakta dan kunci jawaban akan dibatasi pada konten materi tersebut, termasuk hasil ekstraksi dokumen yang sudah tersedia.</p>
                        @elseif($questionBank->course)
                            <p class="mt-2 text-sm leading-5 theme-muted">Belum ada materi pada kursus ini. Gunakan Prompt saja atau PDF/Word.</p>
                        @else
                            <p class="mt-2 text-sm leading-5 theme-muted">Bank soal global belum memiliki sumber materi kursus.</p>
                        @endif
                    </div>

                    <div x-show="sourceMode === 'document'" x-cloak class="mt-4 space-y-4">
                        @if($sourceDocuments->isNotEmpty())
                            <label class="block text-sm font-semibold theme-body">Dokumen yang sudah diunggah <span class="font-normal theme-muted">(opsional)</span>
                                <select name="source_document_id" :disabled="sourceMode !== 'document'" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                                    <option value="">Pilih dokumen lama atau upload baru di bawah</option>
                                    @foreach($sourceDocuments as $document)
                                        <option value="{{ $document->id }}" @selected((string) old('source_document_id') === (string) $document->id)>{{ $document->original_name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        <label class="block text-sm font-semibold theme-body">Upload PDF / DOCX baru <span class="font-normal theme-muted">(opsional jika memilih dokumen lama)</span>
                            <input type="file" name="source_document" :disabled="sourceMode !== 'document'" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="mt-2 block w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-body file:mr-4 file:rounded-lg file:border-0 file:px-3 file:py-2 file:text-sm file:font-semibold">
                            <span class="mt-2 block text-sm leading-5 theme-muted">Maksimal 10 MB. Gunakan PDF berbasis teks atau DOCX agar isi dapat diekstrak.</span>
                        </label>

                        <p class="rounded-xl border theme-warning-border theme-warning-surface p-3 text-sm leading-5 theme-warning-text">Pilih dokumen lama <strong>atau</strong> upload dokumen baru, jangan keduanya sekaligus.</p>
                    </div>
                </fieldset>

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
                    <textarea name="instructions" rows="5" maxlength="2000" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-6 theme-heading theme-focus-border" placeholder="Contoh: Buat soal penerapan untuk kelas X RPL. Jika menanyakan output program, sertakan kode lengkap.">{{ old('instructions') }}</textarea>
                </label>

                <div class="rounded-2xl border theme-warning-border theme-warning-surface p-4 text-sm leading-6 theme-warning-text">
                    <strong>Teacher-in-the-loop:</strong> sumber membantu grounding, tetapi hasil AI tetap berupa draft. Periksa redaksi, kecocokan dengan sumber, dan kunci jawaban sebelum menyimpan.
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
                <h2 class="font-extrabold theme-heading">Contoh penggunaan dokumen</h2>
                <p class="mt-3 text-sm leading-6 theme-body">Upload modul PDF atau DOCX, lalu tulis: “Buat 10 soal tingkat sedang berdasarkan sumber ini. Fokus pada penerapan, bukan hafalan.”</p>
                <p class="mt-3 text-sm leading-6 theme-muted">Saat dokumen dipilih, AI diarahkan untuk tidak menambahkan fakta di luar teks sumber.</p>
            </div>

            <div class="rounded-2xl border theme-accent-border theme-accent-surface p-5">
                <p class="text-sm font-bold theme-accent-text">Alur aman</p>
                <p class="mt-2 text-sm leading-6 theme-body">Prompt + Sumber → Draft AI → Review & edit guru → Simpan ke Bank Soal.</p>
            </div>
        </aside>
    </div>
</div>
@endsection
