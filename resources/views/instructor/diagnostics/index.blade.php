@extends('layouts.app')

@php
    $title = 'Diagnostic Assessment';
    $breadcrumb = 'R2 Learner Model';
@endphp

@section('content')
<div class="space-y-7">
    <section class="rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-indigo-950/30 to-slate-900 p-6 shadow-xl sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-4xl">
                <span class="inline-flex rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-sm font-semibold text-indigo-200">R2.2 — Diagnostic Assessment</span>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Diagnostic Assessment Workspace</h1>
                <p class="mt-3 text-base leading-7 text-slate-300">Tentukan assessment yang menjadi sumber evidence awal Learner Model. Hanya quiz bertipe <strong>Diagnostic</strong> yang memperbarui concept mastery pada tahap R2 ini.</p>
                <p class="mt-2 text-sm leading-6 text-slate-400">Sebelum diterbitkan, setiap soal diagnostic wajib memiliki Concept Mapping pada course yang sama agar mastery dapat ditelusuri dan dijelaskan.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('instructor.learning-concepts.index', $selectedCourse ? ['course_id' => $selectedCourse->id] : []) }}" class="inline-flex items-center justify-center rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-4 py-2.5 text-sm font-bold text-cyan-100 transition hover:bg-cyan-500/20">Domain Model</a>
                <a href="{{ route('instructor.quizzes.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-bold text-slate-100 transition hover:bg-slate-700">Kelola Quiz</a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-950/40 p-4 text-sm text-emerald-100">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="space-y-1 rounded-xl border border-rose-500/30 bg-rose-950/50 p-4 text-sm text-rose-100">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <form method="GET" action="{{ route('instructor.diagnostics.index') }}" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="course_id" class="mb-2 block text-sm font-semibold text-slate-300">Course penelitian</label>
                    <select id="course_id" name="course_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-500">Buka Course</button>
            </form>
        </div>
    </section>

    @if(!$selectedCourse)
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-8 text-center">
            <h2 class="text-lg font-bold text-white">Belum ada course.</h2>
            <p class="mt-2 text-sm text-slate-400">Buat course terlebih dahulu sebelum mengonfigurasi diagnostic assessment.</p>
        </section>
    @else
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="text-2xl font-extrabold text-white">{{ $rows->count() }}</div>
                <div class="mt-1 text-sm text-slate-400">Total Quiz</div>
            </div>
            <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-4">
                <div class="text-2xl font-extrabold text-indigo-200">{{ $rows->filter(fn ($row) => $row['quiz']->isDiagnostic())->count() }}</div>
                <div class="mt-1 text-sm text-slate-400">Diagnostic Quiz</div>
            </div>
            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-4">
                <div class="text-2xl font-extrabold text-emerald-200">{{ $rows->filter(fn ($row) => $row['concept_ready'])->count() }}</div>
                <div class="mt-1 text-sm text-slate-400">Concept Coverage Siap</div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-extrabold text-white">Assessment pada {{ $selectedCourse->title }}</h2>
                <p class="mt-1 text-sm text-slate-400">Pilih tipe assessment. Diagnostic adalah satu-satunya tipe yang menulis evidence ke Learner Mastery pada R2.1.</p>
            </div>

            @forelse($rows as $row)
                @php
                    $quiz = $row['quiz'];
                    $typeTone = match($quiz->assessment_type) {
                        \App\Models\Quiz::ASSESSMENT_DIAGNOSTIC => 'border-indigo-500/30 bg-indigo-500/10 text-indigo-200',
                        \App\Models\Quiz::ASSESSMENT_SUMMATIVE => 'border-amber-500/30 bg-amber-500/10 text-amber-200',
                        default => 'border-slate-700 bg-slate-800 text-slate-300',
                    };
                @endphp
                <article class="rounded-2xl border {{ $quiz->isDiagnostic() ? 'border-indigo-500/30' : 'border-slate-800' }} bg-slate-900/80 p-5 sm:p-6">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-xs font-bold uppercase tracking-wide {{ $typeTone }}">{{ $quiz->assessment_type }}</span>
                                <span class="rounded-full border {{ $quiz->status === 'published' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'border-amber-500/30 bg-amber-500/10 text-amber-200' }} px-2.5 py-1 text-xs font-bold uppercase tracking-wide">{{ $quiz->status }}</span>
                                @if(!$row['editable'])
                                    <span class="rounded-full border border-slate-700 bg-slate-950 px-2.5 py-1 text-xs font-semibold text-slate-400">Tipe terkunci</span>
                                @endif
                            </div>
                            <h3 class="mt-3 text-lg font-bold text-white">{{ $quiz->title }}</h3>
                            <p class="mt-1 text-sm text-slate-400">{{ $quiz->description ?: 'Tidak ada deskripsi.' }}</p>

                            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                    <div class="text-xl font-extrabold text-white">{{ $row['question_count'] }}</div>
                                    <div class="mt-1 text-xs text-slate-500">Soal</div>
                                </div>
                                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3">
                                    <div class="text-xl font-extrabold text-emerald-200">{{ $row['mapped_count'] }}</div>
                                    <div class="mt-1 text-xs text-slate-500">Terpetakan</div>
                                </div>
                                <div class="rounded-xl border {{ $row['unmapped_count'] > 0 ? 'border-rose-500/20 bg-rose-500/5' : 'border-emerald-500/20 bg-emerald-500/5' }} p-3">
                                    <div class="text-xl font-extrabold {{ $row['unmapped_count'] > 0 ? 'text-rose-200' : 'text-emerald-200' }}">{{ $row['unmapped_count'] }}</div>
                                    <div class="mt-1 text-xs text-slate-500">Belum Mapping</div>
                                </div>
                                <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                    <div class="text-xl font-extrabold text-white">{{ $quiz->attempts_count }}</div>
                                    <div class="mt-1 text-xs text-slate-500">Attempt</div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border {{ $row['concept_ready'] ? 'border-emerald-500/20 bg-emerald-500/5 text-emerald-100' : 'border-amber-500/20 bg-amber-500/5 text-amber-100' }} p-3 text-sm">
                                @if($row['concept_ready'])
                                    ✓ Concept coverage siap: semua soal dapat ditelusuri ke Learning Concept.
                                @elseif($row['question_count'] === 0)
                                    Belum ada soal. Tambahkan soal melalui Quiz Builder terlebih dahulu.
                                @else
                                    {{ $row['unmapped_count'] }} soal belum memiliki Concept Mapping. Lengkapi di Domain Model sebelum menerbitkan sebagai diagnostic.
                                @endif
                            </div>
                        </div>

                        <div class="w-full xl:w-80">
                            <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                                <h4 class="text-sm font-bold text-white">Tipe Assessment</h4>
                                @if($row['editable'])
                                    <form action="{{ route('instructor.diagnostics.type.update', $quiz) }}" method="POST" class="mt-3 space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <select name="assessment_type" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white">
                                            <option value="formative" @selected($quiz->assessment_type === 'formative')>Formative</option>
                                            <option value="diagnostic" @selected($quiz->assessment_type === 'diagnostic')>Diagnostic</option>
                                            <option value="summative" @selected($quiz->assessment_type === 'summative')>Summative</option>
                                        </select>
                                        <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">Simpan Tipe</button>
                                    </form>
                                @else
                                    <p class="mt-2 text-xs leading-5 text-slate-500">Tipe tidak dapat diubah karena quiz sudah published atau sudah memiliki attempt. Histori penelitian harus tetap konsisten.</p>
                                @endif

                                <div class="mt-4 flex flex-col gap-2 border-t border-slate-800 pt-4">
                                    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-center text-xs font-semibold text-slate-200 transition hover:bg-slate-800">Review Quiz</a>
                                    <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-3 py-2 text-center text-xs font-semibold text-indigo-200 transition hover:bg-indigo-500/20">Quiz Builder</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-8 text-center">
                    <h3 class="text-base font-bold text-white">Belum ada quiz pada course ini.</h3>
                    <p class="mt-2 text-sm text-slate-400">Buat quiz terlebih dahulu, lalu kembali ke workspace ini untuk menetapkan tipe Diagnostic.</p>
                    <a href="{{ route('instructor.quizzes.index') }}" class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">Buat Quiz</a>
                </div>
            @endforelse
        </section>
    @endif
</div>
@endsection
