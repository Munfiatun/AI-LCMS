@extends('layouts.app')

@php
    $title = 'Learning Objectives & Alignment';
    $breadcrumb = 'Research Foundation';
@endphp

@section('content')
<div class="space-y-8">
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 shadow-xl">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">R3.1 Research Foundation</span>
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-slate-800 text-slate-300 border border-slate-700">No Mastery / No Adaptive Rule</span>
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Learning Objectives & Alignment</h1>
                <p class="text-sm text-slate-400 mt-2 max-w-3xl">Definisikan tujuan pembelajaran per kursus, lalu petakan materi dan butir soal ke tujuan yang benar. Alignment ini menjadi fondasi evidence pada phase berikutnya, bukan penilaian mastery.</p>
            </div>

            @if($selectedCourse)
                <a href="{{ route('instructor.courses.edit', $selectedCourse) }}" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">Kelola Kursus</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-200 text-sm">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/50 border border-rose-500/30 text-rose-200 text-sm space-y-1">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
        <form method="GET" action="{{ route('instructor.learning-objectives.index') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div class="flex-1">
                <label for="course_id" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Kursus</label>
                <select id="course_id" name="course_id" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white">
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-colors">Buka Workspace</button>
        </form>
    </div>

    @if(!$selectedCourse)
        <div class="p-8 text-center rounded-2xl bg-slate-900/70 border border-slate-800">
            <p class="text-white font-semibold">Belum ada kursus.</p>
            <p class="text-sm text-slate-400 mt-1">Buat kursus terlebih dahulu sebelum mendefinisikan Learning Objective.</p>
            <a href="{{ route('instructor.courses.create') }}" class="inline-block mt-4 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold">Buat Kursus</a>
        </div>
    @else
        @php
            $objectives = $selectedCourse->learningObjectives;
        @endphp

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <div class="space-y-6">
                <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
                    <h2 class="text-base font-bold text-white">Tambah Learning Objective</h2>
                    <p class="text-xs text-slate-400 mt-1 mb-5">Gunakan kode stabil seperti LO-01, LO-02, atau CP-01.</p>

                    <form action="{{ route('instructor.learning-objectives.store', $selectedCourse) }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="is_active" value="1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kode</label>
                            <input type="text" name="code" value="{{ old('code') }}" required maxlength="50" placeholder="LO-01" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white uppercase">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tujuan Pembelajaran</label>
                            <input type="text" name="title" value="{{ old('title') }}" required maxlength="255" placeholder="Siswa mampu menjelaskan konsep ..." class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Deskripsi / batas konsep</label>
                            <textarea name="description" rows="3" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white">{{ old('description') }}</textarea>
                        </div>
                        <button class="w-full px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">Tambah Objective</button>
                    </form>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow space-y-4">
                    <div>
                        <h2 class="text-base font-bold text-white">Daftar Objective</h2>
                        <p class="text-xs text-slate-400 mt-1">{{ $objectives->count() }} objective pada {{ $selectedCourse->title }}</p>
                    </div>

                    @forelse($objectives as $objective)
                        <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-3">
                            <form action="{{ route('instructor.learning-objectives.update', $objective) }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-3 gap-2">
                                    <input name="code" required maxlength="50" value="{{ $objective->code }}" class="col-span-2 px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-xs font-bold text-indigo-300 uppercase">
                                    <input name="order" type="number" min="0" value="{{ $objective->order }}" class="px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white" title="Urutan">
                                </div>
                                <input name="title" required maxlength="255" value="{{ $objective->title }}" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white">
                                <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-xs text-slate-300">{{ $objective->description }}</textarea>
                                <div class="flex items-center justify-between gap-3">
                                    <label class="flex items-center gap-2 text-xs text-slate-300">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($objective->is_active) class="rounded border-slate-700 bg-slate-900 text-indigo-500">
                                        Aktif
                                    </label>
                                    <button class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white">Simpan</button>
                                </div>
                            </form>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-[11px] text-slate-500">
                                <span>{{ $objective->materials->count() }} materi &bull; {{ $objective->questions->count() }} soal</span>
                                <form action="{{ route('instructor.learning-objectives.destroy', $objective) }}" method="POST" onsubmit="return confirm('Hapus objective ini dan seluruh alignment-nya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-rose-400 hover:text-rose-300">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 rounded-xl bg-slate-950/50 border border-dashed border-slate-800 text-xs text-slate-500">Belum ada Learning Objective. Tambahkan objective pertama agar materi dan soal dapat dipetakan.</div>
                    @endforelse
                </div>
            </div>

            <div class="xl:col-span-2 space-y-6">
                <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
                        <div>
                            <h2 class="text-base font-bold text-white">Material ↔ Learning Objective</h2>
                            <p class="text-xs text-slate-400 mt-1">Tentukan tujuan pembelajaran yang benar-benar diajarkan oleh setiap materi.</p>
                        </div>
                        <span class="text-[10px] uppercase font-bold text-slate-500">Alignment only</span>
                    </div>

                    <div class="space-y-5">
                        @forelse($selectedCourse->sections as $section)
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-300 mb-2">{{ $section->title }}</h3>
                                <div class="space-y-3">
                                    @forelse($section->materials as $material)
                                        @php $materialObjectiveIds = $material->learningObjectives->pluck('id')->all(); @endphp
                                        <form action="{{ route('instructor.materials.learning-objectives.sync', $material) }}" method="POST" class="p-4 rounded-xl bg-slate-950/50 border border-slate-800">
                                            @csrf
                                            @method('PUT')
                                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                                <div class="lg:w-1/3">
                                                    <p class="text-sm font-bold text-white">{{ $material->title }}</p>
                                                    <p class="text-[11px] text-slate-500 mt-1">{{ ucfirst($material->status) }}</p>
                                                </div>
                                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    @forelse($objectives as $objective)
                                                        <label class="flex items-start gap-2 p-2 rounded-lg border border-slate-800 bg-slate-900/60 text-xs text-slate-300">
                                                            <input type="checkbox" name="learning_objective_ids[]" value="{{ $objective->id }}" @checked(in_array($objective->id, $materialObjectiveIds, true)) class="mt-0.5 rounded border-slate-700 bg-slate-950 text-indigo-500">
                                                            <span><strong class="text-indigo-300">{{ $objective->code }}</strong> {{ $objective->title }} @if(!$objective->is_active)<em class="text-slate-500">(inactive)</em>@endif</span>
                                                        </label>
                                                    @empty
                                                        <p class="text-xs text-slate-500">Tambahkan Learning Objective terlebih dahulu.</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                            @if($objectives->isNotEmpty())
                                                <div class="mt-3 flex justify-end"><button class="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 border border-indigo-500/30 text-indigo-300 text-xs font-bold">Simpan Alignment Materi</button></div>
                                            @endif
                                        </form>
                                    @empty
                                        <p class="text-xs text-slate-500">Belum ada materi pada bab ini.</p>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">Kursus belum memiliki bab/materi.</p>
                        @endforelse
                    </div>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
                    <div class="mb-5">
                        <h2 class="text-base font-bold text-white">Question ↔ Learning Objective</h2>
                        <p class="text-xs text-slate-400 mt-1">Tentukan tujuan pembelajaran yang diukur oleh setiap butir soal. Bank soal global tidak muncul karena tidak memiliki konteks kursus.</p>
                    </div>

                    <div class="space-y-5">
                        @forelse($selectedCourse->questionBanks as $bank)
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-cyan-300">{{ $bank->title }}</h3>
                                    <a href="{{ route('instructor.question-banks.show', $bank) }}" class="text-[11px] text-slate-400 hover:text-white">Buka Bank Soal</a>
                                </div>
                                @forelse($bank->questions as $question)
                                    @php $questionObjectiveIds = $question->learningObjectives->pluck('id')->all(); @endphp
                                    <form action="{{ route('instructor.questions.learning-objectives.sync', $question) }}" method="POST" class="p-4 rounded-xl bg-slate-950/50 border border-slate-800">
                                        @csrf
                                        @method('PUT')
                                        <div class="flex flex-col gap-3">
                                            <div>
                                                <p class="text-sm text-slate-200"><span class="text-slate-500 mr-2">#{{ $question->order }}</span>{{ $question->question_text }}</p>
                                                <p class="text-[11px] text-slate-500 mt-1">{{ ucfirst($question->difficulty ?? 'unknown') }} &bull; {{ $question->topic ?? 'Tanpa topik' }}</p>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                @forelse($objectives as $objective)
                                                    <label class="flex items-start gap-2 p-2 rounded-lg border border-slate-800 bg-slate-900/60 text-xs text-slate-300">
                                                        <input type="checkbox" name="learning_objective_ids[]" value="{{ $objective->id }}" @checked(in_array($objective->id, $questionObjectiveIds, true)) class="mt-0.5 rounded border-slate-700 bg-slate-950 text-cyan-500">
                                                        <span><strong class="text-cyan-300">{{ $objective->code }}</strong> {{ $objective->title }} @if(!$objective->is_active)<em class="text-slate-500">(inactive)</em>@endif</span>
                                                    </label>
                                                @empty
                                                    <p class="text-xs text-slate-500">Tambahkan Learning Objective terlebih dahulu.</p>
                                                @endforelse
                                            </div>
                                            @if($objectives->isNotEmpty())
                                                <div class="flex justify-end"><button class="px-3 py-1.5 rounded-lg bg-cyan-600/20 hover:bg-cyan-600/30 border border-cyan-500/30 text-cyan-300 text-xs font-bold">Simpan Alignment Soal</button></div>
                                            @endif
                                        </div>
                                    </form>
                                @empty
                                    <p class="text-xs text-slate-500">Bank soal ini belum memiliki pertanyaan.</p>
                                @endforelse
                            </div>
                        @empty
                            <div class="p-4 rounded-xl bg-slate-950/50 border border-dashed border-slate-800 text-xs text-slate-500">Belum ada bank soal yang terhubung ke kursus ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
