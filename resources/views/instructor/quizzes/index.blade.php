@extends('layouts.app')

@php
    $title = 'Kuis & Evaluasi';
    $breadcrumb = 'Kuis & Evaluasi';
@endphp

@section('content')
<div class="space-y-6" x-data="{ showModal: false }" @keydown.escape.window="showModal = false">
    @if($errors->any())
        <div role="alert" class="rounded-2xl border theme-danger-border theme-danger-surface p-4 text-sm theme-danger-text">
            <p class="font-bold">Ada yang perlu diperbaiki:</p>
            <div class="mt-2 space-y-1">
                @foreach($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold theme-accent-text">Evaluasi Pembelajaran</p>
                <h1 class="teacher-page-title mt-1 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Kuis & Evaluasi</h1>
                <p class="mt-2 text-base leading-7 theme-body">Buat kuis dari bank soal yang sudah Anda siapkan. Jika ingin bantuan AI, opsinya tersedia di bawah tanpa mengganggu alur utama.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('instructor.question-banks.index') }}" class="teacher-secondary-button inline-flex items-center justify-center rounded-xl border theme-border theme-muted-surface px-4 py-2.5 text-sm font-semibold theme-heading transition theme-muted-surface-hover">Bank Soal</a>
                <button @click="showModal = true" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid transition theme-accent-fill-hover">
                    <span>+</span> Buat Kuis
                </button>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-3">
        <div class="rounded-2xl border theme-accent-border theme-accent-surface p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl theme-accent-fill text-sm font-extrabold teacher-on-solid">1</div>
            <h2 class="mt-3 font-bold theme-heading">Siapkan soal</h2>
            <p class="mt-1 text-sm leading-6 theme-muted">Simpan atau ekstrak soal di Bank Soal.</p>
        </div>
        <div class="rounded-2xl border teacher-card p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl theme-muted-surface text-sm font-extrabold theme-heading">2</div>
            <h2 class="mt-3 font-bold theme-heading">Susun kuis</h2>
            <p class="mt-1 text-sm leading-6 theme-muted">Pilih soal melalui Builder dan cek kualitasnya.</p>
        </div>
        <div class="rounded-2xl border teacher-card p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl theme-muted-surface text-sm font-extrabold theme-heading">3</div>
            <h2 class="mt-3 font-bold theme-heading">Terbitkan</h2>
            <p class="mt-1 text-sm leading-6 theme-muted">Guru tetap memegang keputusan akhir sebelum siswa mengerjakan.</p>
        </div>
    </section>

    <details class="group overflow-hidden rounded-3xl border teacher-card">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-6">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-bold theme-heading">Buat Kuis dengan Bantuan AI</h2>
                    <span class="rounded-full border theme-accent-border theme-accent-surface px-2.5 py-1 text-sm font-semibold theme-accent-text">Opsional</span>
                </div>
                <p class="mt-1 text-sm leading-6 theme-muted">Gunakan jika Anda ingin AI membuat draft soal dari Slidebook. Hasil tetap harus direview guru.</p>
            </div>
            <span class="shrink-0 text-sm font-semibold theme-accent-text group-open:hidden">Buka ↓</span>
            <span class="hidden shrink-0 text-sm font-semibold theme-accent-text group-open:inline">Tutup ↑</span>
        </summary>

        <form action="{{ route('instructor.quizzes.generate-ai') }}" method="POST"
              class="border-t theme-border p-5 sm:p-6"
              x-data="{ generating: false, fillPrompt(text) { $refs.teacherPrompt.value = text; $refs.teacherPrompt.focus(); } }"
              @submit="generating = true">
            @csrf
            <input type="hidden" name="request_id" value="{{ old('request_id', (string) \Illuminate\Support\Str::uuid()) }}">

            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold theme-body">Sumber Slidebook</label>
                    <select name="slidebook_id" required class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border focus:ring-1 focus:ring-indigo-500">
                        <option value="">Pilih Slidebook</option>
                        @foreach($slidebooks as $slidebook)
                            <option value="{{ $slidebook->id }}" @selected(old('slidebook_id', request()->integer('slidebook_id')) == $slidebook->id)>{{ $slidebook->title }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm leading-5 theme-muted">AI hanya menggunakan Slidebook yang Anda pilih sebagai sumber.</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold theme-body">Instruksi Singkat</label>
                    <textarea name="custom_instructions" x-ref="teacherPrompt" required rows="4" maxlength="2000"
                              class="w-full resize-y rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-6 theme-heading theme-placeholder theme-focus-border focus:ring-1 focus:ring-indigo-500"
                              placeholder="Contoh: Buat 10 soal pilihan ganda tingkat sedang yang mengukur pemahaman konsep.">{{ old('custom_instructions') }}</textarea>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" @click="fillPrompt('Buatkan 10 soal pilihan ganda dengan tingkat kesulitan sedang.')" class="rounded-full border theme-border px-3 py-1.5 text-sm theme-body theme-accent-border-hover theme-accent-text-hover">10 soal sedang</button>
                        <button type="button" @click="fillPrompt('Buatkan soal yang berfokus pada pemahaman konsep, bukan hafalan.')" class="rounded-full border theme-border px-3 py-1.5 text-sm theme-body theme-accent-border-hover theme-accent-text-hover">Fokus pemahaman</button>
                    </div>
                </div>
            </div>

            <details class="mt-5 rounded-2xl border teacher-card">
                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold theme-body">Pengaturan tambahan</summary>
                <div class="grid gap-4 border-t theme-border p-4 sm:grid-cols-3">
                    <label class="text-sm font-semibold theme-body">Jumlah soal
                        <input type="number" name="total_questions" min="2" max="20" value="{{ old('total_questions', 10) }}" required class="mt-2 w-full rounded-lg border theme-border theme-surface px-3 py-2.5 text-sm theme-heading theme-focus-border">
                    </label>
                    <label class="text-sm font-semibold theme-body">Kesulitan
                        <select name="difficulty" class="mt-2 w-full rounded-lg border theme-border theme-surface px-3 py-2.5 text-sm theme-heading theme-focus-border">
                            @foreach(['easy', 'medium', 'hard', 'mixed'] as $difficulty)
                                <option value="{{ $difficulty }}" @selected(old('difficulty', 'medium') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-semibold theme-body">Jenis soal
                        <select name="type" class="mt-2 w-full rounded-lg border theme-border theme-surface px-3 py-2.5 text-sm theme-heading theme-focus-border">
                            <option value="multiple_choice" @selected(old('type') === 'multiple_choice')>Pilihan Ganda</option>
                            <option value="true_false" @selected(old('type') === 'true_false')>Benar / Salah</option>
                        </select>
                    </label>
                </div>
            </details>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm theme-muted">AI membuat draft. Guru tetap melakukan review dan publikasi.</p>
                <button type="submit" :disabled="generating" class="inline-flex min-w-40 items-center justify-center rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid transition theme-accent-fill-hover disabled:opacity-50">
                    <span x-text="generating ? 'Membuat draft...' : 'Buat Draft AI'">Buat Draft AI</span>
                </button>
            </div>
        </form>
    </details>

    <section class="space-y-4">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold theme-heading">Daftar Kuis</h2>
                <p class="mt-1 text-sm theme-muted">Buka satu kuis untuk melanjutkan review, publikasi, atau melihat hasil.</p>
            </div>
            <span class="text-sm theme-muted">{{ $quizzes->total() }} kuis</span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($quizzes as $quiz)
                <article class="flex min-h-[220px] flex-col rounded-2xl border teacher-card">
                    <div class="flex-1 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-sm font-semibold {{ $quiz->status === 'published' ? 'theme-success-border theme-success-surface theme-success-text' : 'theme-warning-border theme-warning-surface theme-warning-text' }}">{{ $quiz->status === 'published' ? 'Terbit' : 'Draft' }}</span>
                                <span class="rounded-full border theme-border theme-surface px-2.5 py-1 text-sm font-semibold theme-body">{{ ucfirst($quiz->assessment_type ?? 'formative') }}</span>
                            </div>
                            <span class="text-sm theme-muted">{{ $quiz->total_questions }} soal</span>
                        </div>

                        <h3 class="mt-4 text-lg font-bold leading-snug theme-heading">{{ $quiz->title }}</h3>
                        <p class="mt-1 text-sm font-semibold theme-accent-text">{{ $quiz->course->title }}</p>
                        <p class="mt-3 line-clamp-2 text-sm leading-6 theme-muted">{{ $quiz->description ?? 'Belum ada deskripsi.' }}</p>

                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm theme-body">
                            <span>{{ $quiz->duration_minutes ? $quiz->duration_minutes . ' menit' : 'Tanpa batas waktu' }}</span>
                            <span>Lulus {{ $quiz->passing_score }}%</span>
                        </div>
                    </div>

                    <div class="flex gap-2 border-t theme-border p-4">
                        <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="flex-1 rounded-xl theme-accent-fill px-4 py-2.5 text-center text-sm font-bold teacher-on-solid transition theme-accent-fill-hover">Kelola Kuis</a>
                        <a href="{{ route('instructor.quizzes.results', $quiz) }}" class="teacher-secondary-button rounded-xl border theme-border px-4 py-2.5 text-center text-sm font-semibold theme-body transition theme-muted-surface-hover theme-heading-hover">Hasil</a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed teacher-card p-10 text-center">
                    <h3 class="text-lg font-bold theme-heading">Belum ada kuis</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 theme-muted">Buat kuis pertama dari bank soal yang sudah tersedia, atau gunakan bantuan AI untuk menghasilkan draft.</p>
                    <button @click="showModal = true" type="button" class="mt-5 rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid theme-accent-fill-hover">+ Buat Kuis</button>
                </div>
            @endforelse
        </div>

        @if($quizzes->hasPages())
            <div class="pt-2">{{ $quizzes->links() }}</div>
        @endif
    </section>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 teacher-backdrop backdrop-blur-sm" @click="showModal = false"></div>

            <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border teacher-card shadow-2xl">
                <form action="{{ route('instructor.quizzes.store') }}" method="POST">
                    @csrf
                    <div class="border-b theme-border px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold theme-heading">Buat Kuis</h3>
                                <p class="mt-1 text-sm theme-muted">Buat kerangka kuis dulu. Setelah disimpan, pilih soal melalui Builder.</p>
                            </div>
                            <button type="button" @click="showModal = false" class="teacher-secondary-button rounded-lg p-1.5 theme-muted theme-muted-surface-hover theme-heading-hover" aria-label="Tutup">✕</button>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <label class="block text-sm font-semibold theme-body">Kursus
                            <select name="course_id" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-3 py-2.5 text-sm theme-heading theme-focus-border">
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block text-sm font-semibold theme-body">Judul Kuis
                            <input type="text" name="title" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-3 py-2.5 text-sm theme-heading theme-focus-border">
                        </label>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-semibold theme-body">Durasi (menit)
                                <input type="number" name="duration_minutes" min="1" class="mt-2 w-full rounded-xl border theme-border theme-surface px-3 py-2.5 text-sm theme-heading theme-focus-border" placeholder="Opsional">
                            </label>
                            <label class="block text-sm font-semibold theme-body">Nilai kelulusan
                                <input type="number" name="passing_score" required value="70" min="0" max="100" class="mt-2 w-full rounded-xl border theme-border theme-surface px-3 py-2.5 text-sm theme-heading theme-focus-border">
                            </label>
                        </div>

                        <details class="rounded-xl border teacher-card">
                            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold theme-body">Pengaturan tambahan</summary>
                            <div class="space-y-4 border-t theme-border p-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="block text-sm font-semibold theme-body">Jumlah soal
                                        <input type="number" name="total_questions" required value="10" min="1" class="mt-2 w-full rounded-xl border theme-border theme-surface px-3 py-2.5 text-sm theme-heading theme-focus-border">
                                    </label>
                                    <label class="block text-sm font-semibold theme-body">Maksimal percobaan
                                        <input type="number" name="max_attempts" required value="1" min="1" class="mt-2 w-full rounded-xl border theme-border theme-surface px-3 py-2.5 text-sm theme-heading theme-focus-border">
                                    </label>
                                </div>
                                <div class="flex flex-wrap gap-4">
                                    <label class="flex items-center gap-2 text-sm theme-body">
                                        <input type="checkbox" name="randomize_questions" value="1" class="rounded theme-border theme-surface theme-accent-text">
                                        Acak soal
                                    </label>
                                    <label class="flex items-center gap-2 text-sm theme-body">
                                        <input type="checkbox" name="randomize_options" value="1" class="rounded theme-border theme-surface theme-accent-text">
                                        Acak opsi
                                    </label>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div class="flex justify-end gap-3 border-t teacher-card px-6 py-4">
                        <button type="button" @click="showModal = false" class="teacher-secondary-button rounded-xl border theme-border px-4 py-2.5 text-sm font-semibold theme-body theme-muted-surface-hover">Batal</button>
                        <button type="submit" class="rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Buat & Pilih Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
