@extends('layouts.app')

@php
    $title = 'Kuis & Evaluasi';
    $breadcrumb = 'Kuis & Evaluasi';
@endphp

@section('content')
<div class="space-y-6" x-data="{ showModal: false }" @keydown.escape.window="showModal = false">
    @if($errors->any())
        <div role="alert" class="rounded-2xl border border-rose-500/25 bg-rose-500/10 p-4 text-sm text-rose-200">
            <p class="font-bold">Ada yang perlu diperbaiki:</p>
            <div class="mt-2 space-y-1">
                @foreach($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <section class="rounded-3xl border border-slate-800 bg-slate-900/75 p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-indigo-300">Evaluasi Pembelajaran</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Kuis & Evaluasi</h1>
                <p class="mt-2 text-base leading-7 text-slate-400">Buat kuis dari bank soal yang sudah Anda siapkan. Jika ingin bantuan AI, opsinya tersedia di bawah tanpa mengganggu alur utama.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('instructor.question-banks.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">Bank Soal</a>
                <button @click="showModal = true" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">
                    <span>+</span> Buat Kuis
                </button>
            </div>
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-3">
        <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/[0.06] p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-600 text-sm font-extrabold text-white">1</div>
            <h2 class="mt-3 font-bold text-white">Siapkan soal</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Simpan atau ekstrak soal di Bank Soal.</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-800 text-sm font-extrabold text-slate-200">2</div>
            <h2 class="mt-3 font-bold text-white">Susun kuis</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Pilih soal melalui Builder dan cek kualitasnya.</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-800 text-sm font-extrabold text-slate-200">3</div>
            <h2 class="mt-3 font-bold text-white">Terbitkan</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Guru tetap memegang keputusan akhir sebelum siswa mengerjakan.</p>
        </div>
    </section>

    <details class="group overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/60">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-6">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-bold text-white">Buat Kuis dengan Bantuan AI</h2>
                    <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-2.5 py-1 text-xs font-semibold text-indigo-300">Opsional</span>
                </div>
                <p class="mt-1 text-sm leading-6 text-slate-500">Gunakan jika Anda ingin AI membuat draft soal dari Slidebook. Hasil tetap harus direview guru.</p>
            </div>
            <span class="shrink-0 text-sm font-semibold text-indigo-300 group-open:hidden">Buka ↓</span>
            <span class="hidden shrink-0 text-sm font-semibold text-indigo-300 group-open:inline">Tutup ↑</span>
        </summary>

        <form action="{{ route('instructor.quizzes.generate-ai') }}" method="POST"
              class="border-t border-slate-800 p-5 sm:p-6"
              x-data="{ generating: false, fillPrompt(text) { $refs.teacherPrompt.value = text; $refs.teacherPrompt.focus(); } }"
              @submit="generating = true">
            @csrf
            <input type="hidden" name="request_id" value="{{ old('request_id', (string) \Illuminate\Support\Str::uuid()) }}">

            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Sumber Slidebook</label>
                    <select name="slidebook_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">Pilih Slidebook</option>
                        @foreach($slidebooks as $slidebook)
                            <option value="{{ $slidebook->id }}" @selected(old('slidebook_id', request()->integer('slidebook_id')) == $slidebook->id)>{{ $slidebook->title }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs leading-5 text-slate-500">AI hanya menggunakan Slidebook yang Anda pilih sebagai sumber.</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Instruksi Singkat</label>
                    <textarea name="custom_instructions" x-ref="teacherPrompt" required rows="4" maxlength="2000"
                              class="w-full resize-y rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm leading-6 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                              placeholder="Contoh: Buat 10 soal pilihan ganda tingkat sedang yang mengukur pemahaman konsep.">{{ old('custom_instructions') }}</textarea>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" @click="fillPrompt('Buatkan 10 soal pilihan ganda dengan tingkat kesulitan sedang.')" class="rounded-full border border-slate-700 px-3 py-1.5 text-xs text-slate-400 hover:border-indigo-500/40 hover:text-indigo-300">10 soal sedang</button>
                        <button type="button" @click="fillPrompt('Buatkan soal yang berfokus pada pemahaman konsep, bukan hafalan.')" class="rounded-full border border-slate-700 px-3 py-1.5 text-xs text-slate-400 hover:border-indigo-500/40 hover:text-indigo-300">Fokus pemahaman</button>
                    </div>
                </div>
            </div>

            <details class="mt-5 rounded-2xl border border-slate-800 bg-slate-950/40">
                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-300">Pengaturan tambahan</summary>
                <div class="grid gap-4 border-t border-slate-800 p-4 sm:grid-cols-3">
                    <label class="text-sm font-semibold text-slate-400">Jumlah soal
                        <input type="number" name="total_questions" min="2" max="20" value="{{ old('total_questions', 10) }}" required class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500">
                    </label>
                    <label class="text-sm font-semibold text-slate-400">Kesulitan
                        <select name="difficulty" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500">
                            @foreach(['easy', 'medium', 'hard', 'mixed'] as $difficulty)
                                <option value="{{ $difficulty }}" @selected(old('difficulty', 'medium') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-slate-400">Jenis soal
                        <select name="type" class="mt-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500">
                            <option value="multiple_choice" @selected(old('type') === 'multiple_choice')>Pilihan Ganda</option>
                            <option value="true_false" @selected(old('type') === 'true_false')>Benar / Salah</option>
                        </select>
                    </label>
                </div>
            </details>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">AI membuat draft. Guru tetap melakukan review dan publikasi.</p>
                <button type="submit" :disabled="generating" class="inline-flex min-w-40 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500 disabled:opacity-50">
                    <span x-text="generating ? 'Membuat draft...' : 'Buat Draft AI'">Buat Draft AI</span>
                </button>
            </div>
        </form>
    </details>

    <section class="space-y-4">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold text-white">Daftar Kuis</h2>
                <p class="mt-1 text-sm text-slate-500">Buka satu kuis untuk melanjutkan review, publikasi, atau melihat hasil.</p>
            </div>
            <span class="text-sm text-slate-500">{{ $quizzes->total() }} kuis</span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($quizzes as $quiz)
                <article class="flex min-h-[220px] flex-col rounded-2xl border border-slate-800 bg-slate-900/70">
                    <div class="flex-1 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $quiz->status === 'published' ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/25 bg-amber-500/10 text-amber-300' }}">{{ $quiz->status === 'published' ? 'Terbit' : 'Draft' }}</span>
                                <span class="rounded-full border border-slate-700 bg-slate-950/60 px-2.5 py-1 text-xs font-semibold text-slate-400">{{ ucfirst($quiz->assessment_type ?? 'formative') }}</span>
                            </div>
                            <span class="text-sm text-slate-500">{{ $quiz->total_questions }} soal</span>
                        </div>

                        <h3 class="mt-4 text-lg font-bold leading-snug text-white">{{ $quiz->title }}</h3>
                        <p class="mt-1 text-sm font-semibold text-indigo-300">{{ $quiz->course->title }}</p>
                        <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-500">{{ $quiz->description ?? 'Belum ada deskripsi.' }}</p>

                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-400">
                            <span>{{ $quiz->duration_minutes ? $quiz->duration_minutes . ' menit' : 'Tanpa batas waktu' }}</span>
                            <span>Lulus {{ $quiz->passing_score }}%</span>
                        </div>
                    </div>

                    <div class="flex gap-2 border-t border-slate-800 p-4">
                        <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="flex-1 rounded-xl bg-indigo-600 px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-indigo-500">Kelola Kuis</a>
                        <a href="{{ route('instructor.quizzes.results', $quiz) }}" class="rounded-xl border border-slate-700 px-4 py-2.5 text-center text-sm font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white">Hasil</a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                    <h3 class="text-lg font-bold text-white">Belum ada kuis</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">Buat kuis pertama dari bank soal yang sudah tersedia, atau gunakan bantuan AI untuk menghasilkan draft.</p>
                    <button @click="showModal = true" type="button" class="mt-5 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">+ Buat Kuis</button>
                </div>
            @endforelse
        </div>

        @if($quizzes->hasPages())
            <div class="pt-2">{{ $quizzes->links() }}</div>
        @endif
    </section>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm" @click="showModal = false"></div>

            <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl">
                <form action="{{ route('instructor.quizzes.store') }}" method="POST">
                    @csrf
                    <div class="border-b border-slate-800 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-white">Buat Kuis</h3>
                                <p class="mt-1 text-sm text-slate-500">Buat kerangka kuis dulu. Setelah disimpan, pilih soal melalui Builder.</p>
                            </div>
                            <button type="button" @click="showModal = false" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-800 hover:text-white" aria-label="Tutup">✕</button>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <label class="block text-sm font-semibold text-slate-300">Kursus
                            <select name="course_id" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500">
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block text-sm font-semibold text-slate-300">Judul Kuis
                            <input type="text" name="title" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500">
                        </label>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-semibold text-slate-300">Durasi (menit)
                                <input type="number" name="duration_minutes" min="1" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500" placeholder="Opsional">
                            </label>
                            <label class="block text-sm font-semibold text-slate-300">Nilai kelulusan
                                <input type="number" name="passing_score" required value="70" min="0" max="100" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500">
                            </label>
                        </div>

                        <details class="rounded-xl border border-slate-800 bg-slate-950/40">
                            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-300">Pengaturan tambahan</summary>
                            <div class="space-y-4 border-t border-slate-800 p-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="block text-sm font-semibold text-slate-400">Jumlah soal
                                        <input type="number" name="total_questions" required value="10" min="1" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500">
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-400">Maksimal percobaan
                                        <input type="number" name="max_attempts" required value="1" min="1" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-indigo-500">
                                    </label>
                                </div>
                                <div class="flex flex-wrap gap-4">
                                    <label class="flex items-center gap-2 text-sm text-slate-300">
                                        <input type="checkbox" name="randomize_questions" value="1" class="rounded border-slate-600 bg-slate-900 text-indigo-600">
                                        Acak soal
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-slate-300">
                                        <input type="checkbox" name="randomize_options" value="1" class="rounded border-slate-600 bg-slate-900 text-indigo-600">
                                        Acak opsi
                                    </label>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-800 bg-slate-950/40 px-6 py-4">
                        <button type="button" @click="showModal = false" class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Batal</button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">Buat & Pilih Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
