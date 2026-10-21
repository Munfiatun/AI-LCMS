@extends('layouts.app')

@php
    $title = 'Learning Objectives & Alignment';
    $breadcrumb = 'Research Foundation';
@endphp

@section('content')
<div class="space-y-7">
    <section class="rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 p-6 shadow-xl sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-4xl">
                <span class="inline-flex rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-sm font-semibold text-indigo-200">Fondasi Pembelajaran Adaptif</span>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Learning Objectives &amp; Alignment</h1>
                <p class="mt-3 text-base leading-7 text-slate-300">Susun tujuan pembelajaran, lalu hubungkan setiap materi dan butir soal ke tujuan yang sesuai. Ikuti empat langkah di bawah secara berurutan.</p>
                <p class="mt-2 text-sm leading-6 text-slate-400">Catatan: tahap ini hanya membuat pemetaan. Sistem belum menghitung mastery atau memberikan rekomendasi adaptif.</p>
            </div>

            @if($selectedCourse)
                <a href="{{ route('instructor.courses.edit', $selectedCourse) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-bold text-slate-100 transition-colors hover:bg-slate-700">Kelola Kursus</a>
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
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-base font-extrabold text-white">{{ $step['number'] }}</div>
                    <div>
                        <h2 class="text-base font-bold text-white">{{ $step['title'] }}</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-400">{{ $step['desc'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-950/40 p-4 text-base text-emerald-100">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="space-y-1 rounded-xl border border-rose-500/30 bg-rose-950/50 p-4 text-base text-rose-100">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-extrabold text-white">1</div>
            <div>
                <h2 class="text-xl font-extrabold text-white">Pilih Kursus</h2>
                <p class="mt-0.5 text-sm text-slate-400">Semua objective, materi, dan soal di bawah akan mengikuti kursus ini.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('instructor.learning-objectives.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="course_id" class="mb-2 block text-sm font-semibold text-slate-300">Kursus yang dikerjakan</label>
                <select id="course_id" name="course_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-indigo-500">Buka Kursus</button>
        </form>
    </section>

    @if(!$selectedCourse)
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-8 text-center">
            <p class="text-lg font-bold text-white">Belum ada kursus.</p>
            <p class="mt-2 text-base text-slate-400">Buat kursus terlebih dahulu sebelum mendefinisikan Learning Objective.</p>
            <a href="{{ route('instructor.courses.create') }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white">Buat Kursus</a>
        </section>
    @else
        @php
            $objectives = $selectedCourse->learningObjectives;
        @endphp

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-extrabold text-white">2</div>
                <div>
                    <h2 class="text-xl font-extrabold text-white">Buat Learning Objective</h2>
                    <p class="mt-0.5 text-sm text-slate-400">Tulis apa yang harus mampu dilakukan siswa setelah belajar.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-5">
                    <h3 class="text-lg font-bold text-white">Tambah Objective Baru</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-400">Contoh: <strong class="text-slate-300">LO-01 — Siswa mampu menjelaskan konsep algoritma.</strong></p>

                    <form action="{{ route('instructor.learning-objectives.store', $selectedCourse) }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="is_active" value="1">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Kode Objective</label>
                            <input type="text" name="code" value="{{ old('code') }}" required maxlength="50" placeholder="LO-01" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white uppercase">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Tujuan Pembelajaran</label>
                            <input type="text" name="title" value="{{ old('title') }}" required maxlength="255" placeholder="Siswa mampu menjelaskan konsep ..." class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Deskripsi / Batas Konsep</label>
                            <textarea name="description" rows="4" placeholder="Jelaskan ruang lingkup objective agar pemetaan materi dan soal lebih konsisten." class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base leading-6 text-white">{{ old('description') }}</textarea>
                        </div>
                        <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-indigo-500">+ Tambah Objective</button>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white">Objective pada Kursus Ini</h3>
                            <p class="mt-1 text-sm text-slate-400">{{ $objectives->count() }} objective pada {{ $selectedCourse->title }}</p>
                        </div>
                        <span class="text-sm text-slate-500">Klik objective untuk mengedit</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse($objectives as $objective)
                            <details class="group rounded-xl border border-slate-800 bg-slate-900/70 open:border-indigo-500/30">
                                <summary class="flex cursor-pointer list-none items-start justify-between gap-4 p-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-lg bg-indigo-500/10 px-2.5 py-1 text-base font-extrabold text-indigo-300">{{ $objective->code }}</span>
                                            @if($objective->is_active)
                                                <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-sm font-semibold text-emerald-300">Aktif</span>
                                            @else
                                                <span class="rounded-full border border-slate-700 bg-slate-800 px-2.5 py-1 text-sm font-semibold text-slate-400">Nonaktif</span>
                                            @endif
                                        </div>
                                        <p class="mt-3 text-base font-semibold leading-6 text-white">{{ $objective->title }}</p>
                                        <p class="mt-2 text-sm text-slate-400">{{ $objective->materials->count() }} materi terhubung &bull; {{ $objective->questions->count() }} soal terhubung</p>
                                    </div>
                                    <span class="mt-1 text-sm font-semibold text-indigo-300 group-open:hidden">Edit ↓</span>
                                    <span class="mt-1 hidden text-sm font-semibold text-indigo-300 group-open:inline">Tutup ↑</span>
                                </summary>

                                <div class="border-t border-slate-800 p-4">
                                    <form action="{{ route('instructor.learning-objectives.update', $objective) }}" method="POST" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                            <div class="sm:col-span-2">
                                                <label class="mb-2 block text-sm font-semibold text-slate-300">Kode</label>
                                                <input name="code" required maxlength="50" value="{{ $objective->code }}" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base font-bold text-indigo-300 uppercase">
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-300">Urutan</label>
                                                <input name="order" type="number" min="0" value="{{ $objective->order }}" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-300">Tujuan Pembelajaran</label>
                                            <input name="title" required maxlength="255" value="{{ $objective->title }}" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-300">Deskripsi / Batas Konsep</label>
                                            <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base leading-6 text-slate-200">{{ $objective->description }}</textarea>
                                        </div>
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <label class="flex items-center gap-3 text-base text-slate-200">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" @checked($objective->is_active) class="h-5 w-5 rounded border-slate-700 bg-slate-900 text-indigo-500">
                                                Objective aktif
                                            </label>
                                            <button class="rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700">Simpan Perubahan</button>
                                        </div>
                                    </form>

                                    <div class="mt-4 flex justify-end border-t border-slate-800 pt-4">
                                        <form action="{{ route('instructor.learning-objectives.destroy', $objective) }}" method="POST" onsubmit="return confirm('Hapus objective ini dan seluruh alignment-nya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm font-semibold text-rose-400 hover:text-rose-300">Hapus Objective</button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-700 bg-slate-950/50 p-5 text-base text-slate-400">Belum ada Learning Objective. Tambahkan objective pertama menggunakan form di sebelah kiri.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-extrabold text-white">3</div>
                <div>
                    <h2 class="text-xl font-extrabold text-white">Hubungkan Materi ke Learning Objective</h2>
                    <p class="mt-0.5 text-sm text-slate-400">Untuk setiap materi, centang objective yang benar-benar diajarkan oleh materi tersebut.</p>
                </div>
            </div>

            <div class="space-y-6">
                @forelse($selectedCourse->sections as $section)
                    <div>
                        <h3 class="mb-3 text-base font-bold text-indigo-300">{{ $section->title }}</h3>
                        <div class="space-y-4">
                            @forelse($section->materials as $material)
                                @php $materialObjectiveIds = $material->learningObjectives->pluck('id')->all(); @endphp
                                <form action="{{ route('instructor.materials.learning-objectives.sync', $material) }}" method="POST" class="rounded-2xl border border-slate-800 bg-slate-950/50 p-5">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)]">
                                        <div>
                                            <p class="text-lg font-bold text-white">{{ $material->title }}</p>
                                            <p class="mt-1 text-sm text-slate-500">Status: {{ ucfirst($material->status) }}</p>
                                            <p class="mt-3 text-sm leading-6 text-slate-400">Pilih satu atau lebih objective yang dicakup oleh materi ini.</p>
                                        </div>
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            @forelse($objectives as $objective)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-700 bg-slate-900/70 p-3 text-base text-slate-200 transition hover:border-indigo-500/40 hover:bg-slate-900">
                                                    <input type="checkbox" name="learning_objective_ids[]" value="{{ $objective->id }}" @checked(in_array($objective->id, $materialObjectiveIds, true)) class="mt-1 h-5 w-5 rounded border-slate-600 bg-slate-950 text-indigo-500">
                                                    <span class="leading-6"><strong class="text-indigo-300">{{ $objective->code }}</strong><br>{{ $objective->title }} @if(!$objective->is_active)<em class="text-slate-500">(nonaktif)</em>@endif</span>
                                                </label>
                                            @empty
                                                <p class="text-base text-slate-500">Tambahkan Learning Objective terlebih dahulu.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                    @if($objectives->isNotEmpty())
                                        <div class="mt-5 flex justify-end border-t border-slate-800 pt-4">
                                            <button class="rounded-xl border border-indigo-500/30 bg-indigo-600/20 px-4 py-2.5 text-sm font-bold text-indigo-200 hover:bg-indigo-600/30">Simpan Pemetaan Materi</button>
                                        </div>
                                    @endif
                                </form>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-700 p-5 text-base text-slate-400">Belum ada materi pada bab ini.</div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-700 p-5 text-base text-slate-400">Kursus belum memiliki bab atau materi.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-600 text-lg font-extrabold text-white">4</div>
                <div>
                    <h2 class="text-xl font-extrabold text-white">Hubungkan Soal ke Learning Objective</h2>
                    <p class="mt-0.5 text-sm text-slate-400">Untuk setiap butir soal, centang objective yang memang diukur oleh soal tersebut.</p>
                </div>
            </div>

            <div class="space-y-7">
                @forelse($selectedCourse->questionBanks as $bank)
                    <div>
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-base font-bold text-cyan-300">{{ $bank->title }}</h3>
                            <a href="{{ route('instructor.question-banks.show', $bank) }}" class="text-sm font-semibold text-slate-400 hover:text-white">Buka Bank Soal →</a>
                        </div>

                        <div class="space-y-4">
                            @forelse($bank->questions as $question)
                                @php $questionObjectiveIds = $question->learningObjectives->pluck('id')->all(); @endphp
                                <form action="{{ route('instructor.questions.learning-objectives.sync', $question) }}" method="POST" class="rounded-2xl border border-slate-800 bg-slate-950/50 p-5">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
                                        <div>
                                            <p class="text-base leading-7 text-slate-100"><span class="mr-2 font-bold text-cyan-300">#{{ $question->order }}</span>{{ $question->question_text }}</p>
                                            <p class="mt-2 text-sm text-slate-500">Kesulitan: {{ ucfirst($question->difficulty ?? 'unknown') }} &bull; Topik: {{ $question->topic ?? 'Tanpa topik' }}</p>
                                        </div>
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            @forelse($objectives as $objective)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-700 bg-slate-900/70 p-3 text-base text-slate-200 transition hover:border-cyan-500/40 hover:bg-slate-900">
                                                    <input type="checkbox" name="learning_objective_ids[]" value="{{ $objective->id }}" @checked(in_array($objective->id, $questionObjectiveIds, true)) class="mt-1 h-5 w-5 rounded border-slate-600 bg-slate-950 text-cyan-500">
                                                    <span class="leading-6"><strong class="text-cyan-300">{{ $objective->code }}</strong><br>{{ $objective->title }} @if(!$objective->is_active)<em class="text-slate-500">(nonaktif)</em>@endif</span>
                                                </label>
                                            @empty
                                                <p class="text-base text-slate-500">Tambahkan Learning Objective terlebih dahulu.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                    @if($objectives->isNotEmpty())
                                        <div class="mt-5 flex justify-end border-t border-slate-800 pt-4">
                                            <button class="rounded-xl border border-cyan-500/30 bg-cyan-600/20 px-4 py-2.5 text-sm font-bold text-cyan-200 hover:bg-cyan-600/30">Simpan Pemetaan Soal</button>
                                        </div>
                                    @endif
                                </form>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-700 p-5 text-base text-slate-400">Bank soal ini belum memiliki pertanyaan.</div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-700 bg-slate-950/50 p-5 text-base text-slate-400">Belum ada bank soal yang terhubung ke kursus ini. Buat atau hubungkan bank soal ke kursus terlebih dahulu.</div>
                @endforelse
            </div>
        </section>
    @endif
</div>
@endsection
