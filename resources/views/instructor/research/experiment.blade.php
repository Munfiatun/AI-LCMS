@extends('layouts.app')

@php
    $title = 'Pengaturan Eksperimen';
    $breadcrumb = 'Pengaturan Adaptif';
@endphp

@section('content')
<div class="space-y-7">
    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-4xl">

                <h1 class="teacher-page-title mt-3 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Pengaturan Kondisi Eksperimen</h1>
                <p class="mt-3 text-base leading-7 theme-body">Tetapkan kondisi Adaptive atau Non-Adaptive untuk setiap siswa yang terdaftar pada kursus <strong>{{ $course->title }}</strong>.</p>
                <p class="mt-2 text-sm leading-6 theme-warning-text">Kondisi ini saat ini hanya dicatat sebagai metadata penelitian. Belum ada perbedaan perilaku pembelajaran atau rekomendasi adaptif.</p>
                <span class="teacher-research-badge">R3.2 — Instrumentasi Penelitian</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('instructor.courses.edit', $course) }}" class="teacher-secondary-button inline-flex items-center justify-center rounded-xl border theme-border theme-muted-surface px-4 py-2.5 text-sm font-bold theme-heading theme-muted-surface-hover">Kelola Kursus</a>
                <a href="{{ route('instructor.learning-objectives.index', ['course_id' => $course->id]) }}" class="inline-flex items-center justify-center rounded-xl border theme-accent-border theme-accent-surface px-4 py-2.5 text-sm font-bold theme-accent-text theme-accent-surface-hover">Tujuan Pembelajaran</a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border theme-success-border theme-success-surface p-4 text-base theme-success-text">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border theme-danger-border theme-danger-surface p-4 text-base theme-danger-text">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="rounded-2xl border teacher-card p-5 sm:p-6">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-extrabold theme-heading">Peserta Kursus</h2>
                <p class="mt-1 text-sm theme-body">{{ $course->enrollments->count() }} siswa terdaftar. Assignment dapat diperbarui sebelum versi eksperimen dibekukan.</p>
            </div>
            <div class="rounded-xl border teacher-card px-3 py-2 text-sm theme-body">
                Adaptive = treatment &nbsp;•&nbsp; Non-Adaptive = comparison
            </div>
        </div>

        <div class="space-y-4">
            @forelse($course->enrollments->sortBy(fn ($enrollment) => $enrollment->student?->name) as $enrollment)
                @php
                    $assignment = $assignments->get($enrollment->student_id);
                @endphp
                <form method="POST" action="{{ route('instructor.courses.research.assignment', $course) }}" class="rounded-2xl border theme-border theme-surface p-4 sm:p-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="student_id" value="{{ $enrollment->student_id }}">

                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(180px,.7fr)_minmax(160px,.6fr)_minmax(160px,.6fr)_auto] xl:items-end">
                        <div>
                            <p class="text-base font-bold theme-heading">{{ $enrollment->student?->name ?? 'Siswa' }}</p>
                            <p class="mt-1 text-sm theme-body">{{ $enrollment->student?->email }}</p>
                            @if($assignment)
                                <p class="mt-2 text-sm theme-muted">Terakhir ditetapkan: {{ $assignment->assigned_at?->format('d M Y H:i') }}</p>
                            @else
                                <p class="mt-2 text-sm font-semibold theme-warning-text">Belum memiliki assignment eksperimen</p>
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold theme-body">Kondisi</label>
                            <select name="condition" required class="w-full rounded-xl border theme-border theme-surface px-3 py-3 text-base theme-heading">
                                <option value="adaptive" @selected($assignment?->condition === 'adaptive')>Adaptive</option>
                                <option value="non_adaptive" @selected($assignment?->condition === 'non_adaptive')>Non-Adaptive</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold theme-body">Kelas / Cluster</label>
                            <input name="class_cluster" value="{{ $assignment?->class_cluster }}" placeholder="X PPLG 1" class="w-full rounded-xl border theme-border theme-surface px-3 py-3 text-base theme-heading">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold theme-body">Versi Assignment</label>
                            <input name="assignment_version" required value="{{ $assignment?->assignment_version ?? 'v1' }}" class="w-full rounded-xl border theme-border theme-surface px-3 py-3 text-base theme-heading">
                        </div>

                        <button type="submit" class="rounded-xl theme-accent-fill px-5 py-3 text-sm font-bold teacher-on-solid transition-colors theme-accent-fill-hover">Simpan</button>
                    </div>
                </form>
            @empty
                <div class="rounded-2xl border border-dashed teacher-card p-8 text-center">
                    <p class="text-lg font-bold theme-heading">Belum ada siswa terdaftar.</p>
                    <p class="mt-2 text-sm theme-body">Assignment eksperimen baru dapat dibuat setelah siswa terdaftar pada kursus.</p>
                </div>
            @endforelse
        </div>
    </section>

    <details class="rounded-2xl border teacher-card p-5 text-sm leading-6 theme-body">
        <summary class="cursor-pointer font-semibold">Detail teknis penelitian (R3.2)</summary>
        <p class="mt-3"><strong>Guardrail R3.2:</strong> halaman ini tidak mengaktifkan adaptive engine. Nilai kondisi hanya disimpan untuk kebutuhan treatment tracking dan dicopy sebagai snapshot ke research event saat aktivitas belajar terjadi.</p>
    </details>
</div>
@endsection
