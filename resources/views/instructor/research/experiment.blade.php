@extends('layouts.app')

@php
    $title = 'Research Experiment Setup';
    $breadcrumb = 'Research Instrumentation';
@endphp

@section('content')
<div class="space-y-7">
    <section class="rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 p-6 shadow-xl sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-4xl">
                <span class="inline-flex rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-sm font-semibold text-indigo-200">R3.2 — Instrumentasi Penelitian</span>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Pengaturan Kondisi Eksperimen</h1>
                <p class="mt-3 text-base leading-7 text-slate-300">Tetapkan kondisi Adaptive atau Non-Adaptive untuk setiap siswa yang terdaftar pada kursus <strong>{{ $course->title }}</strong>.</p>
                <p class="mt-2 text-sm leading-6 text-amber-200">Kondisi ini saat ini hanya dicatat sebagai metadata penelitian. Belum ada perbedaan perilaku pembelajaran atau rekomendasi adaptif.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('instructor.courses.edit', $course) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-bold text-slate-100 hover:bg-slate-700">Kelola Kursus</a>
                <a href="{{ route('instructor.learning-objectives.index', ['course_id' => $course->id]) }}" class="inline-flex items-center justify-center rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2.5 text-sm font-bold text-indigo-200 hover:bg-indigo-500/20">Learning Objectives</a>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-950/40 p-4 text-base text-emerald-100">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-500/30 bg-rose-950/50 p-4 text-base text-rose-100">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-white">Peserta Kursus</h2>
                <p class="mt-1 text-sm text-slate-400">{{ $course->enrollments->count() }} siswa terdaftar. Assignment dapat diperbarui sebelum versi eksperimen dibekukan.</p>
            </div>
            <div class="rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-300">
                Adaptive = treatment &nbsp;•&nbsp; Non-Adaptive = comparison
            </div>
        </div>

        <div class="space-y-4">
            @forelse($course->enrollments->sortBy(fn ($enrollment) => $enrollment->student?->name) as $enrollment)
                @php
                    $assignment = $assignments->get($enrollment->student_id);
                @endphp
                <form method="POST" action="{{ route('instructor.courses.research.assignment', $course) }}" class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4 sm:p-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="student_id" value="{{ $enrollment->student_id }}">

                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(180px,.7fr)_minmax(160px,.6fr)_minmax(160px,.6fr)_auto] xl:items-end">
                        <div>
                            <p class="text-base font-bold text-white">{{ $enrollment->student?->name ?? 'Siswa' }}</p>
                            <p class="mt-1 text-sm text-slate-400">{{ $enrollment->student?->email }}</p>
                            @if($assignment)
                                <p class="mt-2 text-xs text-slate-500">Terakhir ditetapkan: {{ $assignment->assigned_at?->format('d M Y H:i') }}</p>
                            @else
                                <p class="mt-2 text-xs font-semibold text-amber-300">Belum memiliki assignment eksperimen</p>
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Kondisi</label>
                            <select name="condition" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-3 text-base text-white">
                                <option value="adaptive" @selected($assignment?->condition === 'adaptive')>Adaptive</option>
                                <option value="non_adaptive" @selected($assignment?->condition === 'non_adaptive')>Non-Adaptive</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Kelas / Cluster</label>
                            <input name="class_cluster" value="{{ $assignment?->class_cluster }}" placeholder="X PPLG 1" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-3 text-base text-white">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Versi Assignment</label>
                            <input name="assignment_version" required value="{{ $assignment?->assignment_version ?? 'v1' }}" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-3 text-base text-white">
                        </div>

                        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-indigo-500">Simpan</button>
                    </div>
                </form>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/40 p-8 text-center">
                    <p class="text-lg font-bold text-white">Belum ada siswa terdaftar.</p>
                    <p class="mt-2 text-sm text-slate-400">Assignment eksperimen baru dapat dibuat setelah siswa terdaftar pada kursus.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-amber-500/20 bg-amber-950/20 p-5 text-sm leading-6 text-amber-100">
        <strong>Guardrail R3.2:</strong> halaman ini tidak mengaktifkan adaptive engine. Nilai kondisi hanya disimpan untuk kebutuhan treatment tracking dan dicopy sebagai snapshot ke research event saat aktivitas belajar terjadi.
    </section>
</div>
@endsection
