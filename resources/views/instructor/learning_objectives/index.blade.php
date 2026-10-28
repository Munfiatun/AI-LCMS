@extends('layouts.app')

@php
    $title = 'Learning Objectives & Alignment';
    $breadcrumb = 'Research Foundation';
@endphp

@section('content')
<div class="space-y-7">
    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-4xl">
                <span class="inline-flex rounded-full border theme-accent-border theme-accent-surface px-3 py-1 text-sm font-semibold theme-accent-text">Fondasi Pembelajaran Adaptif</span>
                <h1 class="teacher-page-title mt-3 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Tujuan Pembelajaran</h1>
                <p class="mt-3 text-base leading-7 theme-body">Susun tujuan pembelajaran, lalu hubungkan setiap materi dan butir soal ke tujuan yang sesuai. Ikuti empat langkah di bawah secara berurutan.</p>
                <p class="mt-2 text-sm leading-6 theme-body">Catatan: tahap ini hanya membuat pemetaan. Sistem belum menghitung mastery atau memberikan rekomendasi adaptif.</p>
            </div>

            @if($selectedCourse)
                <a href="{{ route('instructor.courses.edit', $selectedCourse) }}" class="teacher-secondary-button inline-flex items-center justify-center rounded-xl border theme-border theme-muted-surface px-4 py-2.5 text-sm font-bold theme-heading transition-colors theme-muted-surface-hover">Kelola Kursus</a>
            @endif
        </div>
    </section>

    <section aria-label="Alur alignment" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['number' => '1', 'title' => 'Pilih Kursus', 'desc' => 'Tentukan kursus yang sedang dikerjakan.'],
            ['number' => '2', 'title' => 'Buat Objective', 'desc' => 'Definisikan tujuan belajar yang terukur.'],
            ['number' => '3', 'title' => 'Hubungkan Materi', 'desc' => 'Tentukan objective yang diajarkan materi.'],
            ['number' => '4', 'title' => 'Hubungkan Soal', 'desc' => 'Tentukan objective yang diukur soal.'],
        ] as $step)
            <div class="rounded-2xl border teacher-card p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl theme-accent-fill text-base font-extrabold teacher-on-solid">{{ $step['number'] }}</div>
                    <div>
                        <h2 class="text-base font-bold theme-heading">{{ $step['title'] }}</h2>
                        <p class="mt-1 text-sm leading-5 theme-body">{{ $step['desc'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    @if(session('success'))
        <div class="rounded-xl border theme-success-border theme-success-surface p-4 text-base theme-success-text">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="space-y-1 rounded-xl border theme-danger-border theme-danger-surface p-4 text-base theme-danger-text">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="rounded-2xl border teacher-card p-5 sm:p-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl theme-accent-fill text-lg font-extrabold teacher-on-solid">1</div>
            <div>
                <h2 class="text-xl font-extrabold theme-heading">Pilih Kursus</h2>
                <p class="mt-0.5 text-sm theme-body">Semua objective, materi, dan soal di bawah akan mengikuti kursus ini.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('instructor.learning-objectives.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="course_id" class="mb-2 block text-sm font-semibold theme-body">Kursus yang dikerjakan</label>
                <select id="course_id" name="course_id" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base theme-heading">
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl theme-accent-fill px-5 py-3 text-sm font-bold teacher-on-solid transition-colors theme-accent-fill-hover">Buka Kursus</button>
        </form>
    </section>

    @if(!$selectedCourse)
        <section class="rounded-2xl border teacher-card p-8 text-center">
            <p class="text-lg font-bold theme-heading">Belum ada kursus.</p>
            <p class="mt-2 text-base theme-body">Buat kursus terlebih dahulu sebelum mendefinisikan Learning Objective.</p>
            <a href="{{ route('instructor.courses.create') }}" class="mt-5 inline-flex rounded-xl theme-accent-fill px-5 py-3 text-sm font-bold teacher-on-solid">Buat Kursus</a>
        </section>
    @else
        @php
            $objectives = $selectedCourse->learningObjectives;
        @endphp

        <section class="rounded-2xl border teacher-card p-5 sm:p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl theme-accent-fill text-lg font-extrabold teacher-on-solid">2</div>
                <div>
                    <h2 class="text-xl font-extrabold theme-heading">Buat Learning Objective</h2>
                    <p class="mt-0.5 text-sm theme-body">Tulis apa yang harus mampu dilakukan siswa setelah belajar.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border teacher-card p-5">
                    <h3 class="text-lg font-bold theme-heading">Tambah Objective Baru</h3>
                    <p class="mt-1 text-sm leading-6 theme-body">Contoh: <strong class="theme-body">LO-01 — Siswa mampu menjelaskan konsep algoritma.</strong></p>

                    <form action="{{ route('instructor.learning-objectives.store', $selectedCourse) }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="is_active" value="1">
                        <div>
                            <label class="mb-2 block text-sm font-semibold theme-body">Kode Objective</label>
                            <input type="text" name="code" value="{{ old('code') }}" required maxlength="50" placeholder="LO-01" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base theme-heading uppercase">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold theme-body">Tujuan Pembelajaran</label>
                            <input type="text" name="title" value="{{ old('title') }}" required maxlength="255" placeholder="Siswa mampu menjelaskan konsep ..." class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base theme-heading">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold theme-body">Deskripsi / Batas Konsep</label>
                            <textarea name="description" rows="4" placeholder="Jelaskan ruang lingkup objective agar pemetaan materi dan soal lebih konsisten." class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base leading-6 theme-heading">{{ old('description') }}</textarea>
                        </div>
                        <button class="w-full rounded-xl theme-accent-fill px-4 py-3 text-sm font-bold teacher-on-solid transition-colors theme-accent-fill-hover">+ Tambah Objective</button>
                    </form>
                </div>

                <div class="rounded-2xl border teacher-card p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold theme-heading">Objective pada Kursus Ini</h3>
                            <p class="mt-1 text-sm theme-body">{{ $objectives->count() }} objective pada {{ $selectedCourse->title }}</p>
                        </div>
                        <span class="text-sm theme-muted">Klik objective untuk mengedit</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse($objectives as $objective)
                            <details class="group rounded-xl border teacher-card theme-accent-border-open">
                                <summary class="flex cursor-pointer list-none items-start justify-between gap-4 p-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-lg theme-accent-surface px-2.5 py-1 text-base font-extrabold theme-accent-text">{{ $objective->code }}</span>
                                            @if($objective->is_active)
                                                <span class="rounded-full border theme-success-border theme-success-surface px-2.5 py-1 text-sm font-semibold theme-success-text">Aktif</span>
                                            @else
                                                <span class="rounded-full border theme-border theme-muted-surface px-2.5 py-1 text-sm font-semibold theme-body">Nonaktif</span>
                                            @endif
                                        </div>
                                        <p class="mt-3 text-base font-semibold leading-6 theme-heading">{{ $objective->title }}</p>
                                        <p class="mt-2 text-sm theme-body">{{ $objective->materials->count() }} materi terhubung &bull; {{ $objective->questions->count() }} soal terhubung</p>
                                    </div>
                                    <span class="mt-1 text-sm font-semibold theme-accent-text group-open:hidden">Edit ↓</span>
                                    <span class="mt-1 hidden text-sm font-semibold theme-accent-text group-open:inline">Tutup ↑</span>
                                </summary>

                                <div class="border-t theme-border p-4">
                                    <form action="{{ route('instructor.learning-objectives.update', $objective) }}" method="POST" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                            <div class="sm:col-span-2">
                                                <label class="mb-2 block text-sm font-semibold theme-body">Kode</label>
                                                <input name="code" required maxlength="50" value="{{ $objective->code }}" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base font-bold theme-accent-text uppercase">
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-semibold theme-body">Urutan</label>
                                                <input name="order" type="number" min="0" value="{{ $objective->order }}" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base theme-heading">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold theme-body">Tujuan Pembelajaran</label>
                                            <input name="title" required maxlength="255" value="{{ $objective->title }}" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base theme-heading">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold theme-body">Deskripsi / Batas Konsep</label>
                                            <textarea name="description" rows="3" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base leading-6 theme-heading">{{ $objective->description }}</textarea>
                                        </div>
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <label class="flex items-center gap-3 text-base theme-heading">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" @checked($objective->is_active) class="h-5 w-5 rounded theme-border theme-surface theme-accent-text">
                                                Objective aktif
                                            </label>
                                            <button class="teacher-secondary-button rounded-xl theme-muted-surface px-4 py-2.5 text-sm font-bold theme-heading theme-muted-surface-hover">Simpan Perubahan</button>
                                        </div>
                                    </form>

                                    <div class="mt-4 flex justify-end border-t theme-border pt-4">
                                        <form action="{{ route('instructor.learning-objectives.destroy', $objective) }}" method="POST" onsubmit="return confirm('Hapus objective ini dan seluruh alignment-nya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm font-semibold theme-danger-text theme-danger-text-hover">Hapus Objective</button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        @empty
                            <div class="rounded-xl border border-dashed teacher-card p-5 text-base theme-body">Belum ada Learning Objective. Tambahkan objective pertama menggunakan form di sebelah kiri.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border teacher-card p-5 sm:p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl theme-accent-fill text-lg font-extrabold teacher-on-solid">3</div>
                <div>
                    <h2 class="text-xl font-extrabold theme-heading">Hubungkan Materi ke Learning Objective</h2>
                    <p class="mt-0.5 text-sm theme-body">Untuk setiap materi, centang objective yang benar-benar diajarkan oleh materi tersebut.</p>
                </div>
            </div>

            <div class="space-y-6">
                @forelse($selectedCourse->sections as $section)
                    <div>
                        <h3 class="mb-3 text-base font-bold theme-accent-text">{{ $section->title }}</h3>
                        <div class="space-y-4">
                            @forelse($section->materials as $material)
                                @php $materialObjectiveIds = $material->learningObjectives->pluck('id')->all(); @endphp
                                <form action="{{ route('instructor.materials.learning-objectives.sync', $material) }}" method="POST" class="rounded-2xl border theme-border theme-surface p-5">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)]">
                                        <div>
                                            <p class="text-lg font-bold theme-heading">{{ $material->title }}</p>
                                            <p class="mt-1 text-sm theme-muted">Status: {{ ucfirst($material->status) }}</p>
                                            <p class="mt-3 text-sm leading-6 theme-body">Pilih satu atau lebih objective yang dicakup oleh materi ini.</p>
                                        </div>
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            @forelse($objectives as $objective)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border theme-border theme-surface p-3 text-base theme-heading transition theme-accent-border-hover theme-surface-hover">
                                                    <input type="checkbox" name="learning_objective_ids[]" value="{{ $objective->id }}" @checked(in_array($objective->id, $materialObjectiveIds, true)) class="mt-1 h-5 w-5 rounded theme-border theme-surface theme-accent-text">
                                                    <span class="leading-6"><strong class="theme-accent-text">{{ $objective->code }}</strong><br>{{ $objective->title }} @if(!$objective->is_active)<em class="theme-muted">(nonaktif)</em>@endif</span>
                                                </label>
                                            @empty
                                                <p class="text-base theme-muted">Tambahkan Learning Objective terlebih dahulu.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                    @if($objectives->isNotEmpty())
                                        <div class="mt-5 flex justify-end border-t theme-border pt-4">
                                            <button class="rounded-xl border theme-accent-border theme-accent-surface px-4 py-2.5 text-sm font-bold theme-accent-text theme-accent-surface-hover">Simpan Pemetaan Materi</button>
                                        </div>
                                    @endif
                                </form>
                            @empty
                                <div class="rounded-xl border border-dashed theme-border p-5 text-base theme-body">Belum ada materi pada bab ini.</div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed theme-border p-5 text-base theme-body">Kursus belum memiliki bab atau materi.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border teacher-card p-5 sm:p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl theme-accent-fill text-lg font-extrabold teacher-on-solid">4</div>
                <div>
                    <h2 class="text-xl font-extrabold theme-heading">Hubungkan Soal ke Learning Objective</h2>
                    <p class="mt-0.5 text-sm theme-body">Untuk setiap butir soal, centang objective yang memang diukur oleh soal tersebut.</p>
                </div>
            </div>

            <div class="space-y-7">
                @forelse($selectedCourse->questionBanks as $bank)
                    <div>
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-base font-bold theme-accent-text">{{ $bank->title }}</h3>
                            <a href="{{ route('instructor.question-banks.show', $bank) }}" class="text-sm font-semibold theme-body theme-heading-hover">Buka Bank Soal →</a>
                        </div>

                        <div class="space-y-4">
                            @forelse($bank->questions as $question)
                                @php $questionObjectiveIds = $question->learningObjectives->pluck('id')->all(); @endphp
                                <form action="{{ route('instructor.questions.learning-objectives.sync', $question) }}" method="POST" class="rounded-2xl border theme-border theme-surface p-5">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
                                        <div>
                                            <p class="text-base leading-7 theme-heading"><span class="mr-2 font-bold theme-accent-text">#{{ $question->order }}</span>{{ $question->question_text }}</p>
                                            <p class="mt-2 text-sm theme-muted">Kesulitan: {{ ucfirst($question->difficulty ?? 'unknown') }} &bull; Topik: {{ $question->topic ?? 'Tanpa topik' }}</p>
                                        </div>
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            @forelse($objectives as $objective)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border theme-border theme-surface p-3 text-base theme-heading transition theme-accent-border-hover theme-surface-hover">
                                                    <input type="checkbox" name="learning_objective_ids[]" value="{{ $objective->id }}" @checked(in_array($objective->id, $questionObjectiveIds, true)) class="mt-1 h-5 w-5 rounded theme-border theme-surface theme-accent-text">
                                                    <span class="leading-6"><strong class="theme-accent-text">{{ $objective->code }}</strong><br>{{ $objective->title }} @if(!$objective->is_active)<em class="theme-muted">(nonaktif)</em>@endif</span>
                                                </label>
                                            @empty
                                                <p class="text-base theme-muted">Tambahkan Learning Objective terlebih dahulu.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                    @if($objectives->isNotEmpty())
                                        <div class="mt-5 flex justify-end border-t theme-border pt-4">
                                            <button class="rounded-xl border theme-accent-border theme-accent-surface px-4 py-2.5 text-sm font-bold theme-accent-text theme-accent-surface-hover">Simpan Pemetaan Soal</button>
                                        </div>
                                    @endif
                                </form>
                            @empty
                                <div class="rounded-xl border border-dashed theme-border p-5 text-base theme-body">Bank soal ini belum memiliki pertanyaan.</div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed teacher-card p-5 text-base theme-body">Belum ada bank soal yang terhubung ke kursus ini. Buat atau hubungkan bank soal ke kursus terlebih dahulu.</div>
                @endforelse
            </div>
        </section>
    @endif
</div>
@endsection
