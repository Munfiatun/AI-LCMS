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
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 max-w-3xl">
                <a href="{{ route('instructor.question-banks.index') }}" class="text-sm font-semibold theme-muted transition theme-accent-text-hover">← Kembali ke Bank Soal</a>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border theme-accent-border theme-accent-surface px-3 py-1 text-sm font-semibold theme-accent-text">{{ $questionBank->course?->title ?? 'Global' }}</span>
                    <span class="rounded-full border px-3 py-1 text-sm font-semibold {{ $questionBank->status === 'active' ? 'theme-success-border theme-success-surface theme-success-text' : 'theme-border theme-muted-surface theme-body' }}">{{ $questionBank->status === 'active' ? 'Aktif' : 'Arsip' }}</span>
                </div>
                <h1 class="teacher-page-title mt-3 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">{{ $questionBank->title }}</h1>
                <p class="mt-2 text-base leading-7 theme-body">{{ $questionBank->description ?? 'Tambahkan soal, review hasil ekstraksi, lalu gunakan bank ini untuk membuat formative atau kuis.' }}</p>

                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm theme-body">
                    <span><strong class="theme-heading">{{ $questions->count() }}</strong> soal</span>
                    <span><strong class="theme-success-text">{{ $verifiedCount }}</strong> terverifikasi</span>
                    @if($reviewCount > 0)
                        <span><strong class="theme-warning-text">{{ $reviewCount }}</strong> perlu review</span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="showEditModal = true" class="teacher-secondary-button rounded-xl border theme-border theme-muted-surface px-4 py-2.5 text-sm font-semibold theme-heading transition theme-muted-surface-hover">Edit Info</button>
                <a href="{{ route('instructor.quizzes.index') }}" class="teacher-secondary-button rounded-xl border theme-border px-4 py-2.5 text-sm font-semibold theme-body transition theme-muted-surface-hover theme-heading-hover">Kuis →</a>
            </div>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-3">
        <article class="rounded-3xl border teacher-step-card teacher-step-active p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl theme-accent-fill text-sm font-extrabold teacher-on-solid">1</div>
                <div>
                    <h2 class="text-lg font-extrabold theme-heading">Tambah Soal</h2>
                    <p class="mt-1 text-sm leading-6 theme-muted">Buat draft dari prompt AI, tambah manual, atau ekstrak banyak soal dari dokumen.</p>
                </div>
            </div>

            <a href="{{ route('instructor.question-banks.ai-authoring.create', $questionBank) }}" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl theme-accent-fill px-4 py-2.5 text-sm font-extrabold teacher-on-solid transition theme-accent-fill-hover">
                <span aria-hidden="true">✨</span>
                <span>Buat Soal dengan AI</span>
            </a>

            <button type="button" @click="showAddModal = true" class="mt-3 w-full rounded-xl border theme-accent-border theme-accent-surface px-4 py-2.5 text-sm font-bold theme-accent-text transition theme-accent-surface-hover">+ Tambah Soal Manual</button>

            <details class="mt-3 rounded-xl border teacher-card">
                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold theme-body">Unggah dokumen & ekstrak AI</summary>
                <div class="space-y-4 border-t theme-border p-4">
                    <form action="{{ route('instructor.question-banks.upload-document', $questionBank) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <label class="block text-sm font-semibold theme-body">PDF / DOCX
                            <input type="file" name="document" accept=".pdf,.doc,.docx" required class="mt-2 block w-full rounded-xl border theme-border theme-surface px-3 py-2 text-sm theme-body file:mr-3 file:rounded-lg file:border-0 theme-muted-surface-file file:px-3 file:py-1.5 file:text-xs file:font-semibold theme-heading-file">
                        </label>
                        <button type="submit" class="w-full rounded-xl border theme-accent-border theme-accent-surface px-4 py-2.5 text-sm font-bold theme-accent-text transition theme-accent-surface-hover">Unggah Dokumen</button>
                    </form>

                    @if($documents->count() > 0)
                        <div class="space-y-2 border-t theme-border pt-4">
                            <p class="text-sm font-semibold theme-muted">Dokumen yang dapat diekstrak</p>
                            @foreach($documents as $doc)
                                <div class="flex items-center justify-between gap-3 rounded-xl border teacher-card p-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold theme-body">{{ $doc->original_name }}</p>
                                        <p class="mt-0.5 text-sm theme-muted">{{ number_format($doc->size / 1024, 1) }} KB</p>
                                    </div>
                                    <form action="{{ route('instructor.question-banks.extract', $questionBank) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="document_id" value="{{ $doc->id }}">
                                        <button type="submit" class="rounded-lg theme-accent-fill px-3 py-2 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Ekstrak AI</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </details>
        </article>

        <article class="rounded-3xl border teacher-step-card p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $reviewCount > 0 ? 'theme-warning-fill teacher-on-solid' : 'theme-success-fill teacher-on-solid' }} text-sm font-extrabold">2</div>
                <div>
                    <h2 class="text-lg font-extrabold theme-heading">Review Soal</h2>
                    @if($reviewCount > 0)
                        <p class="mt-1 text-sm leading-6 theme-muted">Ada {{ $reviewCount }} soal yang masih membutuhkan keputusan guru sebelum dapat digunakan.</p>
                    @else
                        <p class="mt-1 text-sm leading-6 theme-muted">Semua soal saat ini sudah terverifikasi dan siap digunakan.</p>
                    @endif
                </div>
            </div>

            <div class="mt-5 rounded-2xl border teacher-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm theme-body">Terverifikasi</span>
                    <strong class="text-xl theme-success-text">{{ $verifiedCount }}</strong>
                </div>
                <div class="mt-2 flex items-center justify-between gap-3">
                    <span class="text-sm theme-body">Perlu review</span>
                    <strong class="text-xl {{ $reviewCount > 0 ? 'theme-warning-text' : 'theme-muted' }}">{{ $reviewCount }}</strong>
                </div>
            </div>

            @if($reviewCount > 0)
                <a href="{{ route('instructor.question-banks.review', $questionBank) }}" class="mt-4 block rounded-xl theme-warning-fill px-4 py-2.5 text-center text-sm font-extrabold teacher-on-solid transition theme-warning-fill-hover">Review {{ $reviewCount }} Soal</a>
            @else
                <div class="mt-4 rounded-xl border theme-success-border theme-success-surface px-4 py-3 text-center text-sm font-semibold theme-success-text">✓ Siap digunakan</div>
            @endif
        </article>

        <article class="rounded-3xl border teacher-card p-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl theme-muted-surface text-sm font-extrabold theme-heading">3</div>
                <div>
                    <h2 class="text-lg font-extrabold theme-heading">Siapkan Formative</h2>
                    <p class="mt-1 text-sm leading-6 theme-muted">Untuk adaptive learning, pilih concept sekali. Sistem akan mapping soal dan membuat draft formative otomatis.</p>
                </div>
            </div>

            @if(!$questionBank->course)
                <div class="mt-5 rounded-xl border theme-warning-border theme-warning-surface p-4 text-sm leading-6 theme-warning-text">
                    Hubungkan bank soal ini ke satu kursus melalui <button type="button" @click="showEditModal = true" class="font-bold theme-warning-text underline">Edit Info</button> terlebih dahulu.
                </div>
            @elseif($concepts->isEmpty())
                <div class="mt-5 rounded-xl border theme-warning-border theme-warning-surface p-4 text-sm leading-6 theme-warning-text">
                    Kursus ini belum memiliki Learning Concept. Buka <a href="{{ route('instructor.learning-concepts.index', ['course_id' => $questionBank->course_id]) }}" class="font-bold theme-warning-text underline">Konsep & Mapping</a> untuk menambahkannya.
                </div>
            @else
                <form action="{{ route('instructor.question-banks.create-formative', $questionBank) }}" method="POST" class="mt-5 space-y-3">
                    @csrf
                    <label class="block text-sm font-semibold theme-body">Target Concept
                        <select name="learning_concept_id" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border">
                            <option value="">Pilih concept</option>
                            @foreach($concepts as $concept)
                                <option value="{{ $concept->id }}">{{ $concept->code }} — {{ $concept->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" @disabled($questions->isEmpty() || $reviewCount > 0) class="w-full rounded-xl theme-accent-fill px-4 py-2.5 text-sm font-bold teacher-on-solid transition theme-accent-fill-hover disabled:cursor-not-allowed disabled:opacity-40">Siapkan Formative Otomatis</button>
                    @if($questions->isEmpty())
                        <p class="text-sm leading-5 theme-muted">Tambahkan soal terlebih dahulu.</p>
                    @elseif($reviewCount > 0)
                        <p class="text-sm leading-5 theme-muted">Selesaikan review sebelum formative dibuat.</p>
                    @else
                        <p class="text-sm leading-5 theme-muted">Draft quiz akan dibuat otomatis; publikasi tetap dilakukan oleh guru.</p>
                    @endif
                </form>
            @endif
        </article>
    </section>

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-extrabold theme-heading">Daftar Soal</h2>
                <p class="mt-1 text-sm theme-muted">Pertanyaan diringkas agar halaman tidak terlalu panjang. Klik satu soal untuk melihat pilihan jawabannya.</p>
            </div>
            <span class="text-sm theme-muted">{{ $questions->count() }} soal</span>
        </div>

        <div class="space-y-3">
            @forelse($questions as $question)
                <details class="group overflow-hidden rounded-2xl border {{ $question->needs_review ? 'theme-warning-border' : 'theme-border' }} theme-surface">
                    <summary class="flex cursor-pointer list-none items-start gap-4 p-4 sm:p-5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl theme-muted-surface text-sm font-extrabold theme-body">{{ $loop->iteration }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-semibold leading-6 theme-heading">{{ $question->question_text }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full border theme-border theme-surface px-2.5 py-1 text-sm theme-body">{{ ucfirst($question->difficulty) }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-sm font-semibold {{ $question->needs_review ? 'theme-warning-border theme-warning-surface theme-warning-text' : 'theme-success-border theme-success-surface theme-success-text' }}">{{ $question->needs_review ? 'Perlu review' : 'Terverifikasi' }}</span>
                            </div>
                        </div>
                        <span class="mt-1 text-sm font-semibold theme-accent-text group-open:hidden">Lihat ↓</span>
                        <span class="mt-1 hidden text-sm font-semibold theme-accent-text group-open:inline">Tutup ↑</span>
                    </summary>

                    <div class="border-t theme-border px-4 pb-5 pt-4 sm:px-5 sm:pl-[4.5rem]">
                        <div class="grid gap-2">
                            @foreach($question->options as $index => $option)
                                <div class="flex items-start gap-3 rounded-xl border {{ $option->is_correct ? 'theme-success-border theme-success-surface' : 'teacher-card' }} p-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border {{ $option->is_correct ? 'theme-success-border theme-success-text' : 'theme-border theme-muted' }} text-sm font-bold">{{ chr(65 + $index) }}</span>
                                    <span class="text-sm leading-6 {{ $option->is_correct ? 'font-semibold theme-success-text' : 'theme-body' }}">{{ $option->option_text }}</span>
                                    @if($option->is_correct)
                                        <span class="ml-auto shrink-0 text-sm font-semibold theme-success-text">Kunci</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if(filled($question->explanation))
                            <div class="mt-4 rounded-xl border teacher-card p-4">
                                <p class="text-sm font-semibold theme-muted">Pembahasan</p>
                                <p class="mt-1 text-sm leading-6 theme-body">{{ $question->explanation }}</p>
                            </div>
                        @endif

                        <div class="mt-4 flex justify-end">
                            <form action="{{ route('instructor.questions.destroy', $question) }}" method="POST" onsubmit="return confirm('Hapus pertanyaan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold theme-danger-text theme-danger-text-hover">Hapus soal</button>
                            </form>
                        </div>
                    </div>
                </details>
            @empty
                <div class="rounded-3xl border border-dashed teacher-card p-10 text-center">
                    <h3 class="text-lg font-bold theme-heading">Belum ada soal</h3>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 theme-muted">Mulai dengan prompt AI untuk membuat draft soal, atau gunakan cara manual dan unggah dokumen.</p>
                    <div class="mt-5 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('instructor.question-banks.ai-authoring.create', $questionBank) }}" class="rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-extrabold teacher-on-solid theme-accent-fill-hover">✨ Buat Soal dengan AI</a>
                        <button type="button" @click="showAddModal = true" class="rounded-xl border theme-accent-border theme-accent-surface px-5 py-2.5 text-sm font-bold theme-accent-text theme-accent-surface-hover">+ Tambah Manual</button>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 teacher-backdrop backdrop-blur-sm" @click="showEditModal = false"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border teacher-card shadow-2xl">
                <form action="{{ route('instructor.question-banks.update', $questionBank) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="border-b theme-border px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold theme-heading">Edit Bank Soal</h3>
                                <p class="mt-1 text-sm theme-muted">Atur identitas dan course bank soal.</p>
                            </div>
                            <button type="button" @click="showEditModal = false" class="teacher-secondary-button rounded-lg p-1.5 theme-muted theme-muted-surface-hover theme-heading-hover">✕</button>
                        </div>
                    </div>
                    <div class="space-y-4 p-6">
                        <label class="block text-sm font-semibold theme-body">Judul
                            <input type="text" name="title" value="{{ old('title', $questionBank->title) }}" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                        </label>
                        <label class="block text-sm font-semibold theme-body">Kursus
                            <select name="course_id" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                                <option value="">Global / lintas kursus</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" @selected((string) old('course_id', $questionBank->course_id) === (string) $course->id)>{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm font-semibold theme-body">Status
                            <select name="status" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                                <option value="active" @selected(old('status', $questionBank->status) === 'active')>Aktif</option>
                                <option value="archived" @selected(old('status', $questionBank->status) === 'archived')>Arsip</option>
                            </select>
                        </label>
                        <label class="block text-sm font-semibold theme-body">Deskripsi
                            <textarea name="description" rows="3" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">{{ old('description', $questionBank->description) }}</textarea>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 border-t teacher-card px-6 py-4">
                        <button type="button" @click="showEditModal = false" class="teacher-secondary-button rounded-xl border theme-border px-4 py-2.5 text-sm font-semibold theme-body theme-muted-surface-hover">Batal</button>
                        <button type="submit" class="rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 teacher-backdrop backdrop-blur-sm" @click="showAddModal = false"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border teacher-card shadow-2xl">
                <form action="{{ route('instructor.question-banks.questions.store', $questionBank) }}" method="POST">
                    @csrf
                    <div class="border-b theme-border px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold theme-heading">Tambah Soal Manual</h3>
                                <p class="mt-1 text-sm theme-muted">Isi pertanyaan dan tandai satu jawaban yang benar.</p>
                            </div>
                            <button type="button" @click="showAddModal = false" class="teacher-secondary-button rounded-lg p-1.5 theme-muted theme-muted-surface-hover theme-heading-hover">✕</button>
                        </div>
                    </div>

                    <div class="max-h-[70vh] space-y-5 overflow-y-auto p-6">
                        <label class="block text-sm font-semibold theme-body">Pertanyaan
                            <textarea name="question_text" rows="3" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm theme-heading theme-focus-border" placeholder="Tuliskan pertanyaan..."></textarea>
                        </label>

                        <fieldset>
                            <legend class="text-sm font-semibold theme-body">Pilihan jawaban</legend>
                            <p class="mt-1 text-sm theme-muted">Pilih radio button pada jawaban yang benar.</p>
                            <div class="mt-3 space-y-3">
                                @foreach(['A', 'B', 'C', 'D'] as $idx => $label)
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="correct_option" value="{{ $idx }}" @checked($idx === 0) class="h-4 w-4 theme-border theme-surface theme-success-text">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg theme-muted-surface text-sm font-bold theme-body">{{ $label }}</span>
                                        <input type="text" name="options[{{ $idx }}]" required class="min-w-0 flex-1 rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border" placeholder="Opsi {{ $label }}">
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>

                        <details class="rounded-xl border teacher-card">
                            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold theme-body">Pengaturan tambahan</summary>
                            <div class="grid gap-4 border-t theme-border p-4 sm:grid-cols-2">
                                <label class="text-sm font-semibold theme-body">Kesulitan
                                    <select name="difficulty" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading">
                                        <option value="easy">Mudah</option>
                                        <option value="medium" selected>Sedang</option>
                                        <option value="hard">Sulit</option>
                                    </select>
                                </label>
                                <label class="text-sm font-semibold theme-body">Bobot
                                    <input type="number" name="points" value="10" min="1" max="100" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading">
                                </label>
                            </div>
                        </details>
                    </div>

                    <div class="flex justify-end gap-3 border-t teacher-card px-6 py-4">
                        <button type="button" @click="showAddModal = false" class="teacher-secondary-button rounded-xl border theme-border px-4 py-2.5 text-sm font-semibold theme-body theme-muted-surface-hover">Batal</button>
                        <button type="submit" class="rounded-xl theme-accent-fill px-5 py-2.5 text-sm font-bold teacher-on-solid theme-accent-fill-hover">Simpan Soal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
