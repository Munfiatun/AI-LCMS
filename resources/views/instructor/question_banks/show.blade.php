@extends('layouts.app')

@php
    $title = $questionBank->title;
    $breadcrumb = 'Bank Soal';
    $reviewCount = $questions->where('needs_review', true)->count();
    $verifiedCount = $questions->filter(fn ($question) => ! $question->needs_review && $question->status === 'approved')->count();
@endphp

@section('content')
<div class="space-y-6" x-data="{ showEditModal: false, showAddModal: false }" @keydown.escape.window="showEditModal = false; showAddModal = false">
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
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 max-w-3xl">
                <a href="{{ route('instructor.question-banks.index') }}" class="text-sm font-semibold text-slate-500 transition hover:text-indigo-300">← Kembali ke Bank Soal</a>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-xs font-semibold text-indigo-300">{{ $questionBank->course?->title ?? 'Global' }}</span>
                    <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $questionBank->status === 'active' ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' : 'border-slate-700 bg-slate-800 text-slate-400' }}">{{ $questionBank->status === 'active' ? 'Aktif' : 'Arsip' }}</span>
                </div>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $questionBank->title }}</h1>
                <p class="mt-2 text-base leading-7 text-slate-400">{{ $questionBank->description ?? 'Tambahkan soal, review hasil ekstraksi, lalu gunakan bank ini untuk membuat formative atau kuis.' }}</p>

                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-400">
                    <span><strong class="text-white">{{ $questions->count() }}</strong> soal</span>
                    <span><strong class="text-emerald-300">{{ $verifiedCount }}</strong> terverifikasi</span>
                    @if($reviewCount > 0)
                        <span><strong class="text-amber-300">{{ $reviewCount }}</strong> perlu review</span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="showEditModal = true" class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">Edit Info</button>
                <a href="{{ route('instructor.quizzes.index') }}" class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white">Kuis →</a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-3">
        <article class="rounded-3xl border border-indigo-500/20 bg-indigo-500/[0.05] p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-extrabold text-white">1</div>
                <div>
                    <h2 class="text-lg font-extrabold text-white">Tambah Soal</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Pilih cara yang paling nyaman. Manual untuk satu-dua soal, dokumen untuk jumlah banyak.</p>
                </div>
            </div>

            <button type="button" @click="showAddModal = true" class="mt-5 w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">+ Tambah Soal Manual</button>

            <details class="mt-3 rounded-xl border border-slate-800 bg-slate-950/40">
                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-300">Unggah dokumen & ekstrak AI</summary>
                <div class="space-y-4 border-t border-slate-800 p-4">
                    <form action="{{ route('instructor.question-banks.upload-document', $questionBank) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <label class="block text-sm font-semibold text-slate-400">PDF / DOCX
                            <input type="file" name="document" accept=".pdf,.doc,.docx" required class="mt-2 block w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
                        </label>
                        <button type="submit" class="w-full rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2.5 text-sm font-bold text-indigo-200 transition hover:bg-indigo-500/20">Unggah Dokumen</button>
                    </form>

                    @if($documents->count() > 0)
                        <div class="space-y-2 border-t border-slate-800 pt-4">
                            <p class="text-xs font-semibold text-slate-500">Dokumen yang dapat diekstrak</p>
                            @foreach($documents as $doc)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-300">{{ $doc->original_name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-600">{{ number_format($doc->size / 1024, 1) }} KB</p>
                                    </div>
                                    <form action="{{ route('instructor.question-banks.extract', $questionBank) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="document_id" value="{{ $doc->id }}">
                                        <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-500">Ekstrak AI</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </details>
        </article>

        <article class="rounded-3xl border {{ $reviewCount > 0 ? 'border-amber-500/20 bg-amber-500/[0.04]' : 'border-emerald-500/20 bg-emerald-500/[0.04]' }} p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $reviewCount > 0 ? 'bg-amber-500 text-slate-950' : 'bg-emerald-500 text-slate-950' }} text-sm font-extrabold">2</div>
                <div>
                    <h2 class="text-lg font-extrabold text-white">Review Soal</h2>
                    @if($reviewCount > 0)
                        <p class="mt-1 text-sm leading-6 text-slate-500">Ada {{ $reviewCount }} soal yang masih membutuhkan keputusan guru sebelum dapat digunakan.</p>
                    @else
                        <p class="mt-1 text-sm leading-6 text-slate-500">Semua soal saat ini sudah terverifikasi dan siap digunakan.</p>
                    @endif
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-800 bg-slate-950/45 p-4">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm text-slate-400">Terverifikasi</span>
                    <strong class="text-xl text-emerald-300">{{ $verifiedCount }}</strong>
                </div>
                <div class="mt-2 flex items-center justify-between gap-3">
                    <span class="text-sm text-slate-400">Perlu review</span>
                    <strong class="text-xl {{ $reviewCount > 0 ? 'text-amber-300' : 'text-slate-500' }}">{{ $reviewCount }}</strong>
                </div>
            </div>

            @if($reviewCount > 0)
                <a href="{{ route('instructor.question-banks.review', $questionBank) }}" class="mt-4 block rounded-xl bg-amber-400 px-4 py-2.5 text-center text-sm font-extrabold text-slate-950 transition hover:bg-amber-300">Review {{ $reviewCount }} Soal</a>
            @else
                <div class="mt-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-center text-sm font-semibold text-emerald-200">✓ Siap digunakan</div>
            @endif
        </article>

        <article class="rounded-3xl border border-slate-800 bg-slate-900/60 p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-sm font-extrabold text-white">3</div>
                <div>
                    <h2 class="text-lg font-extrabold text-white">Siapkan Formative</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Untuk adaptive learning, pilih concept sekali. Sistem akan mapping soal dan membuat draft formative otomatis.</p>
                </div>
            </div>

            @if(!$questionBank->course)
                <div class="mt-5 rounded-xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm leading-6 text-amber-100/80">
                    Hubungkan bank soal ini ke satu kursus melalui <button type="button" @click="showEditModal = true" class="font-bold text-amber-200 underline">Edit Info</button> terlebih dahulu.
                </div>
            @elseif($concepts->isEmpty())
                <div class="mt-5 rounded-xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm leading-6 text-amber-100/80">
                    Kursus ini belum memiliki Learning Concept. Buka <a href="{{ route('instructor.learning-concepts.index', ['course_id' => $questionBank->course_id]) }}" class="font-bold text-amber-200 underline">Konsep & Mapping</a> untuk menambahkannya.
                </div>
            @else
                <form action="{{ route('instructor.question-banks.create-formative', $questionBank) }}" method="POST" class="mt-5 space-y-3">
                    @csrf
                    <label class="block text-sm font-semibold text-slate-400">Target Concept
                        <select name="learning_concept_id" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white focus:border-indigo-500">
                            <option value="">Pilih concept</option>
                            @foreach($concepts as $concept)
                                <option value="{{ $concept->id }}">{{ $concept->code }} — {{ $concept->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" @disabled($questions->isEmpty() || $reviewCount > 0) class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-40">Siapkan Formative Otomatis</button>
                    @if($questions->isEmpty())
                        <p class="text-xs leading-5 text-slate-600">Tambahkan soal terlebih dahulu.</p>
                    @elseif($reviewCount > 0)
                        <p class="text-xs leading-5 text-slate-600">Selesaikan review sebelum formative dibuat.</p>
                    @else
                        <p class="text-xs leading-5 text-slate-600">Draft quiz akan dibuat otomatis; publikasi tetap dilakukan oleh guru.</p>
                    @endif
                </form>
            @endif
        </article>
    </section>

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-white">Daftar Soal</h2>
                <p class="mt-1 text-sm text-slate-500">Pertanyaan diringkas agar halaman tidak terlalu panjang. Klik satu soal untuk melihat pilihan jawabannya.</p>
            </div>
            <span class="text-sm text-slate-500">{{ $questions->count() }} soal</span>
        </div>

        <div class="space-y-3">
            @forelse($questions as $question)
                <details class="group overflow-hidden rounded-2xl border {{ $question->needs_review ? 'border-amber-500/20' : 'border-slate-800' }} bg-slate-900/65">
                    <summary class="flex cursor-pointer list-none items-start gap-4 p-4 sm:p-5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-sm font-extrabold text-slate-300">{{ $loop->iteration }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-semibold leading-6 text-slate-100">{{ $question->question_text }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-slate-700 bg-slate-950/60 px-2.5 py-1 text-xs text-slate-400">{{ ucfirst($question->difficulty) }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $question->needs_review ? 'border-amber-500/20 bg-amber-500/10 text-amber-300' : 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300' }}">{{ $question->needs_review ? 'Perlu review' : 'Terverifikasi' }}</span>
                            </div>
                        </div>
                        <span class="mt-1 text-sm font-semibold text-indigo-300 group-open:hidden">Lihat ↓</span>
                        <span class="mt-1 hidden text-sm font-semibold text-indigo-300 group-open:inline">Tutup ↑</span>
                    </summary>

                    <div class="border-t border-slate-800 px-4 pb-5 pt-4 sm:px-5 sm:pl-[4.5rem]">
                        <div class="grid gap-2">
                            @foreach($question->options as $index => $option)
                                <div class="flex items-start gap-3 rounded-xl border {{ $option->is_correct ? 'border-emerald-500/20 bg-emerald-500/[0.06]' : 'border-slate-800 bg-slate-950/40' }} p-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border {{ $option->is_correct ? 'border-emerald-500/30 text-emerald-300' : 'border-slate-700 text-slate-500' }} text-xs font-bold">{{ chr(65 + $index) }}</span>
                                    <span class="text-sm leading-6 {{ $option->is_correct ? 'font-semibold text-emerald-100' : 'text-slate-400' }}">{{ $option->option_text }}</span>
                                    @if($option->is_correct)
                                        <span class="ml-auto shrink-0 text-xs font-semibold text-emerald-300">Kunci</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if(filled($question->explanation))
                            <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                                <p class="text-xs font-semibold text-slate-500">Pembahasan</p>
                                <p class="mt-1 text-sm leading-6 text-slate-400">{{ $question->explanation }}</p>
                            </div>
                        @endif

                        <div class="mt-4 flex justify-end">
                            <form action="{{ route('instructor.questions.destroy', $question) }}" method="POST" onsubmit="return confirm('Hapus pertanyaan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-rose-300 hover:text-rose-200">Hapus soal</button>
                            </form>
                        </div>
                    </div>
                </details>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                    <h3 class="text-lg font-bold text-white">Belum ada soal</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">Gunakan tombol Tambah Soal Manual atau unggah dokumen pada langkah pertama.</p>
                    <button type="button" @click="showAddModal = true" class="mt-5 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">+ Tambah Soal</button>
                </div>
            @endforelse
        </div>
    </section>

    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm" @click="showEditModal = false"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl">
                <form action="{{ route('instructor.question-banks.update', $questionBank) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="border-b border-slate-800 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-white">Edit Bank Soal</h3>
                                <p class="mt-1 text-sm text-slate-500">Atur identitas dan course bank soal.</p>
                            </div>
                            <button type="button" @click="showEditModal = false" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-800 hover:text-white">✕</button>
                        </div>
                    </div>
                    <div class="space-y-4 p-6">
                        <label class="block text-sm font-semibold text-slate-300">Judul
                            <input type="text" name="title" value="{{ old('title', $questionBank->title) }}" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500">
                        </label>
                        <label class="block text-sm font-semibold text-slate-300">Kursus
                            <select name="course_id" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500">
                                <option value="">Global / lintas kursus</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" @selected((string) old('course_id', $questionBank->course_id) === (string) $course->id)>{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-slate-300">Status
                            <select name="status" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500">
                                <option value="active" @selected(old('status', $questionBank->status) === 'active')>Aktif</option>
                                <option value="archived" @selected(old('status', $questionBank->status) === 'archived')>Arsip</option>
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-slate-300">Deskripsi
                            <textarea name="description" rows="3" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500">{{ old('description', $questionBank->description) }}</textarea>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-800 bg-slate-950/40 px-6 py-4">
                        <button type="button" @click="showEditModal = false" class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Batal</button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm" @click="showAddModal = false"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl">
                <form action="{{ route('instructor.question-banks.questions.store', $questionBank) }}" method="POST">
                    @csrf
                    <div class="border-b border-slate-800 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-white">Tambah Soal Manual</h3>
                                <p class="mt-1 text-sm text-slate-500">Isi pertanyaan dan tandai satu jawaban yang benar.</p>
                            </div>
                            <button type="button" @click="showAddModal = false" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-800 hover:text-white">✕</button>
                        </div>
                    </div>

                    <div class="max-h-[70vh] space-y-5 overflow-y-auto p-6">
                        <label class="block text-sm font-semibold text-slate-300">Pertanyaan
                            <textarea name="question_text" rows="3" required class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white focus:border-indigo-500" placeholder="Tuliskan pertanyaan..."></textarea>
                        </label>

                        <fieldset>
                            <legend class="text-sm font-semibold text-slate-300">Pilihan jawaban</legend>
                            <p class="mt-1 text-xs text-slate-500">Pilih radio button pada jawaban yang benar.</p>
                            <div class="mt-3 space-y-3">
                                @foreach(['A', 'B', 'C', 'D'] as $idx => $label)
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="correct_option" value="{{ $idx }}" @checked($idx === 0) class="h-4 w-4 border-slate-600 bg-slate-950 text-emerald-500">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-sm font-bold text-slate-300">{{ $label }}</span>
                                        <input type="text" name="options[{{ $idx }}]" required class="min-w-0 flex-1 rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500" placeholder="Opsi {{ $label }}">
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>

                        <details class="rounded-xl border border-slate-800 bg-slate-950/40">
                            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-300">Pengaturan tambahan</summary>
                            <div class="grid gap-4 border-t border-slate-800 p-4 sm:grid-cols-2">
                                <label class="text-sm font-semibold text-slate-400">Kesulitan
                                    <select name="difficulty" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white">
                                        <option value="easy">Mudah</option>
                                        <option value="medium" selected>Sedang</option>
                                        <option value="hard">Sulit</option>
                                    </select>
                                </label>
                                <label class="text-sm font-semibold text-slate-400">Bobot
                                    <input type="number" name="points" value="10" min="1" max="100" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white">
                                </label>
                            </div>
                        </details>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-800 bg-slate-950/40 px-6 py-4">
                        <button type="button" @click="showAddModal = false" class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Batal</button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">Simpan Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
