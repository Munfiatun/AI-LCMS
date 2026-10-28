@extends('layouts.app')

@php
    $title = 'Diagnostik Awal';
    $breadcrumb = 'Pengaturan Adaptif';
@endphp

@section('content')
<div class="space-y-7">
    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-4xl">

                <h1 class="teacher-page-title mt-3 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Diagnostik Awal</h1>
                <p class="mt-3 text-base leading-7 theme-body">Tentukan asesmen diagnostik yang digunakan untuk memperoleh gambaran awal pemahaman siswa. Hanya kuis bertipe <strong>Diagnostic</strong> yang memperbarui penguasaan konsep siswa.</p>
                <p class="mt-2 text-sm leading-6 theme-body">Sebelum diterbitkan, setiap soal diagnostic wajib memiliki Concept Mapping pada course yang sama agar mastery dapat ditelusuri dan dijelaskan.</p>
                <span class="teacher-research-badge">R2.2 — Diagnostic Assessment</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('instructor.learning-concepts.index', $selectedCourse ? ['course_id' => $selectedCourse->id] : []) }}" class="inline-flex items-center justify-center rounded-xl border theme-accent-border theme-accent-surface px-4 py-2.5 text-sm font-bold theme-accent-text transition theme-accent-surface-hover">Konsep & Mapping</a>
                <a href="{{ route('instructor.quizzes.index') }}" class="teacher-secondary-button inline-flex items-center justify-center rounded-xl border theme-border theme-muted-surface px-4 py-2.5 text-sm font-bold theme-heading transition theme-muted-surface-hover">Kelola Kuis</a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border theme-success-border theme-success-surface p-4 text-sm theme-success-text">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="space-y-1 rounded-xl border theme-danger-border theme-danger-surface p-4 text-sm theme-danger-text">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="rounded-2xl border teacher-card p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <form method="GET" action="{{ route('instructor.diagnostics.index') }}" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="course_id" class="mb-2 block text-sm font-semibold theme-body">Course penelitian</label>
                    <select id="course_id" name="course_id" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-base theme-heading">
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-xl theme-accent-fill px-5 py-3 text-sm font-bold teacher-on-solid transition theme-accent-fill-hover">Buka Course</button>
            </form>
        </div>
    </section>

    @if(!$selectedCourse)
        <section class="rounded-2xl border teacher-card p-8 text-center">
            <h2 class="text-lg font-bold theme-heading">Belum ada course.</h2>
            <p class="mt-2 text-sm theme-body">Buat course terlebih dahulu sebelum mengonfigurasi diagnostic assessment.</p>
        </section>
    @else
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border teacher-card p-4">
                <div class="text-2xl font-extrabold theme-heading">{{ $rows->count() }}</div>
                <div class="mt-1 text-sm theme-body">Total Quiz</div>
            </div>
            <div class="rounded-2xl border theme-accent-border theme-accent-surface p-4">
                <div class="text-2xl font-extrabold theme-accent-text">{{ $rows->filter(fn ($row) => $row['quiz']->isDiagnostic())->count() }}</div>
                <div class="mt-1 text-sm theme-body">Diagnostic Quiz</div>
            </div>
            <div class="rounded-2xl border theme-success-border theme-success-surface p-4">
                <div class="text-2xl font-extrabold theme-success-text">{{ $rows->filter(fn ($row) => $row['concept_ready'])->count() }}</div>
                <div class="mt-1 text-sm theme-body">Concept Coverage Siap</div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-extrabold theme-heading">Assessment pada {{ $selectedCourse->title }}</h2>
                <p class="mt-1 text-sm theme-body">Pilih tipe assessment. Diagnostic adalah satu-satunya tipe yang menulis evidence ke Learner Mastery pada R2.1.</p>
            </div>

            @forelse($rows as $row)
                @php
                    $quiz = $row['quiz'];
                    $typeTone = match($quiz->assessment_type) {
                        \App\Models\Quiz::ASSESSMENT_DIAGNOSTIC => 'theme-accent-border theme-accent-surface theme-accent-text',
                        \App\Models\Quiz::ASSESSMENT_SUMMATIVE => 'theme-warning-border theme-warning-surface theme-warning-text',
                        default => 'theme-border theme-muted-surface theme-body',
                    };
                @endphp
                <article class="teacher-secondary-button rounded-2xl border {{ $quiz->isDiagnostic() ? 'theme-accent-border' : 'theme-border' }} theme-surface p-5 sm:p-6">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-sm font-bold uppercase tracking-wide {{ $typeTone }}">{{ $quiz->assessment_type }}</span>
                                <span class="rounded-full border {{ $quiz->status === 'published' ? 'theme-success-border theme-success-surface theme-success-text' : 'theme-warning-border theme-warning-surface theme-warning-text' }} px-2.5 py-1 text-sm font-bold uppercase tracking-wide">{{ $quiz->status }}</span>
                                @if(!$row['editable'])
                                    <span class="rounded-full border theme-border theme-surface px-2.5 py-1 text-sm font-semibold theme-body">Tipe terkunci</span>
                                @endif
                            </div>
                            <h3 class="mt-3 text-lg font-bold theme-heading">{{ $quiz->title }}</h3>
                            <p class="mt-1 text-sm theme-body">{{ $quiz->description ?: 'Tidak ada deskripsi.' }}</p>

                            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-xl border teacher-card p-3">
                                    <div class="text-xl font-extrabold theme-heading">{{ $row['question_count'] }}</div>
                                    <div class="mt-1 text-sm theme-muted">Soal</div>
                                </div>
                                <div class="rounded-xl border theme-success-border theme-success-surface p-3">
                                    <div class="text-xl font-extrabold theme-success-text">{{ $row['mapped_count'] }}</div>
                                    <div class="mt-1 text-sm theme-muted">Terpetakan</div>
                                </div>
                                <div class="rounded-xl border {{ $row['unmapped_count'] > 0 ? 'theme-danger-border theme-danger-surface' : 'theme-success-border theme-success-surface' }} p-3">
                                    <div class="text-xl font-extrabold {{ $row['unmapped_count'] > 0 ? 'theme-danger-text' : 'theme-success-text' }}">{{ $row['unmapped_count'] }}</div>
                                    <div class="mt-1 text-sm theme-muted">Belum Mapping</div>
                                </div>
                                <div class="rounded-xl border teacher-card p-3">
                                    <div class="text-xl font-extrabold theme-heading">{{ $quiz->attempts_count }}</div>
                                    <div class="mt-1 text-sm theme-muted">Attempt</div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border {{ $row['concept_ready'] ? 'theme-success-border theme-success-surface theme-success-text' : 'theme-warning-border theme-warning-surface theme-warning-text' }} p-3 text-sm">
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
                            <div class="rounded-2xl border teacher-card p-4">
                                <h4 class="text-sm font-bold theme-heading">Tipe Assessment</h4>
                                @if($row['editable'])
                                    <form action="{{ route('instructor.diagnostics.type.update', $quiz) }}" method="POST" class="mt-3 space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <select name="assessment_type" class="w-full rounded-xl border theme-border theme-surface px-3 py-2.5 text-sm theme-heading">
                                            <option value="formative" @selected($quiz->assessment_type === 'formative')>Formative</option>
                                            <option value="diagnostic" @selected($quiz->assessment_type === 'diagnostic')>Diagnostic</option>
                                            <option value="summative" @selected($quiz->assessment_type === 'summative')>Summative</option>
                                        </select>
                                        <button type="submit" class="w-full rounded-xl theme-accent-fill px-4 py-2.5 text-sm font-bold teacher-on-solid transition theme-accent-fill-hover">Simpan Tipe</button>
                                    </form>
                                @else
                                    <p class="mt-2 text-sm leading-5 theme-muted">Tipe tidak dapat diubah karena quiz sudah published atau sudah memiliki attempt. Histori penelitian harus tetap konsisten.</p>
                                @endif

                                <div class="mt-4 flex flex-col gap-2 border-t theme-border pt-4">
                                    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="teacher-secondary-button rounded-xl border theme-border theme-surface px-3 py-2 text-center text-sm font-semibold theme-heading transition theme-muted-surface-hover">Review Quiz</a>
                                    <a href="{{ route('instructor.quizzes.builder', $quiz) }}" class="rounded-xl border theme-accent-border theme-accent-surface px-3 py-2 text-center text-sm font-semibold theme-accent-text transition theme-accent-surface-hover">Quiz Builder</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border teacher-card p-8 text-center">
                    <h3 class="text-base font-bold theme-heading">Belum ada quiz pada course ini.</h3>
                    <p class="mt-2 text-sm theme-body">Buat quiz terlebih dahulu, lalu kembali ke workspace ini untuk menetapkan tipe Diagnostic.</p>
                    <a href="{{ route('instructor.quizzes.index') }}" class="mt-4 inline-flex rounded-xl theme-accent-fill px-4 py-2.5 text-sm font-bold teacher-on-solid">Buat Quiz</a>
                </div>
            @endforelse
        </section>
    @endif
</div>
@endsection
