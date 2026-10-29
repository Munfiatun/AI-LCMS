@extends('layouts.app')

@php
    $title = 'Review Draft Soal AI';
    $breadcrumb = 'Bank Soal';
    $questions = old('questions', $draft['questions']);
    $settings = $draft['settings'] ?? [];
@endphp

@section('content')
<div class="space-y-6">
    @if($errors->any())
        <div role="alert" class="rounded-2xl border theme-danger-border theme-danger-surface p-4 text-sm theme-danger-text">
            <p class="font-bold">Ada bagian draft yang perlu diperbaiki:</p>
            <div class="mt-2 space-y-1">
                @foreach($errors->all() as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <section class="teacher-page-header border p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <a href="{{ route('instructor.question-banks.ai-authoring.create', $questionBank) }}" class="text-sm font-semibold theme-muted transition theme-accent-text-hover">← Ubah Prompt</a>
                <p class="mt-4 text-sm font-semibold theme-accent-text">AI Draft · Belum tersimpan</p>
                <h1 class="teacher-page-title mt-1 text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Review Draft Soal AI</h1>
                <p class="mt-2 text-base leading-7 theme-body">Periksa pertanyaan, opsi, kunci, dan pembahasan. Saat Anda menekan simpan, draft dianggap telah direview guru dan baru dimasukkan ke bank soal.</p>
            </div>
            <div class="rounded-2xl border teacher-card px-4 py-3 text-sm">
                <p class="font-semibold theme-heading">{{ count($questions) }} soal</p>
                <p class="mt-1 theme-muted">{{ strtoupper($draft['provider'] ?? 'AI') }}{{ !empty($draft['model']) ? ' · '.$draft['model'] : '' }}</p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border theme-accent-border theme-accent-surface p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold theme-accent-text">Prompt aktif</p>
                <p class="mt-1 text-sm leading-6 theme-body"><strong>{{ $settings['topic'] ?? '-' }}</strong> · {{ ucfirst($settings['difficulty'] ?? 'medium') }} · {{ $settings['total_questions'] ?? count($questions) }} soal</p>
                @if(!empty($settings['instructions']))
                    <p class="mt-1 text-sm leading-6 theme-muted">{{ $settings['instructions'] }}</p>
                @endif
            </div>
            <form action="{{ route('instructor.question-banks.ai-authoring.generate', $questionBank) }}" method="POST">
                @csrf
                <input type="hidden" name="topic" value="{{ $settings['topic'] ?? '' }}">
                <input type="hidden" name="total_questions" value="{{ $settings['total_questions'] ?? count($questions) }}">
                <input type="hidden" name="difficulty" value="{{ $settings['difficulty'] ?? 'medium' }}">
                <input type="hidden" name="instructions" value="{{ $settings['instructions'] ?? '' }}">
                <input type="hidden" name="force_regenerate" value="1">
                <button type="submit" class="teacher-secondary-button whitespace-nowrap rounded-xl border theme-accent-border px-4 py-2.5 text-sm font-bold theme-accent-text theme-accent-surface-hover">↻ Generate Ulang</button>
            </form>
        </div>
    </section>

    <form action="{{ route('instructor.question-banks.ai-authoring.store', $questionBank) }}" method="POST" class="space-y-5">
        @csrf

        @foreach($questions as $questionIndex => $question)
            <article class="overflow-hidden rounded-3xl border teacher-card">
                <div class="flex items-center justify-between gap-3 border-b theme-border px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl theme-accent-surface text-sm font-extrabold theme-accent-text">{{ $loop->iteration }}</span>
                        <div>
                            <h2 class="font-extrabold theme-heading">Soal {{ $loop->iteration }}</h2>
                            <p class="text-sm theme-muted">Edit jika ada redaksi, konteks, kode, atau kunci yang belum tepat.</p>
                        </div>
                    </div>
                    <span class="rounded-full border theme-warning-border theme-warning-surface px-3 py-1 text-sm font-semibold theme-warning-text">AI Draft</span>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    <label class="block text-sm font-semibold theme-body">Pertanyaan
                        <textarea name="questions[{{ $questionIndex }}][question_text]" rows="6" required maxlength="1200" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-6 theme-heading theme-focus-border">{{ $question['question_text'] ?? '' }}</textarea>
                        <span class="mt-1.5 block text-sm font-normal leading-5 theme-muted">Jika soal mengacu pada program, kode, tabel, atau kasus tertentu, objek tersebut harus tertulis lengkap di dalam pertanyaan.</span>
                    </label>

                    <fieldset>
                        <legend class="text-sm font-semibold theme-body">Pilihan jawaban & kunci</legend>
                        <p class="mt-1 text-sm theme-muted">Pilih satu radio button sebagai jawaban benar.</p>
                        <div class="mt-3 space-y-3">
                            @foreach(($question['options'] ?? []) as $optionIndex => $option)
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="questions[{{ $questionIndex }}][correct_option]" value="{{ $optionIndex }}" required @checked((int) ($question['correct_option'] ?? -1) === $optionIndex) class="h-4 w-4 theme-border theme-surface theme-success-text">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg theme-muted-surface text-sm font-bold theme-body">{{ chr(65 + $optionIndex) }}</span>
                                    <input type="text" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}]" value="{{ $option }}" required maxlength="255" class="min-w-0 flex-1 rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="block text-sm font-semibold theme-body">Topik
                            <input type="text" name="questions[{{ $questionIndex }}][topic]" value="{{ $question['topic'] ?? '' }}" maxlength="100" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                        </label>
                        <label class="block text-sm font-semibold theme-body">Kesulitan
                            <select name="questions[{{ $questionIndex }}][difficulty]" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                                <option value="easy" @selected(($question['difficulty'] ?? '') === 'easy')>Mudah</option>
                                <option value="medium" @selected(($question['difficulty'] ?? 'medium') === 'medium')>Sedang</option>
                                <option value="hard" @selected(($question['difficulty'] ?? '') === 'hard')>Sulit</option>
                            </select>
                        </label>
                        <label class="block text-sm font-semibold theme-body">Bobot
                            <input type="number" name="questions[{{ $questionIndex }}][points]" value="{{ $question['points'] ?? 10 }}" min="1" max="100" required class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm theme-heading theme-focus-border">
                        </label>
                    </div>

                    <label class="block text-sm font-semibold theme-body">Pembahasan
                        <textarea name="questions[{{ $questionIndex }}][explanation]" rows="2" maxlength="1000" class="mt-2 w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-6 theme-heading theme-focus-border">{{ $question['explanation'] ?? '' }}</textarea>
                    </label>
                </div>
            </article>
        @endforeach

        <div class="sticky bottom-4 rounded-2xl border teacher-card p-4 shadow-2xl">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm leading-6 theme-muted">Dengan menyimpan, Anda menyatakan telah meninjau redaksi dan kunci jawaban draft AI.</p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <button type="submit" form="discard-ai-draft" class="teacher-secondary-button rounded-xl border theme-border px-4 py-2.5 text-sm font-semibold theme-danger-text theme-danger-surface-hover">Batalkan Draft</button>
                    <button type="submit" class="rounded-xl theme-success-fill px-5 py-2.5 text-sm font-extrabold teacher-on-solid theme-success-fill-hover">✓ Simpan ke Bank Soal</button>
                </div>
            </div>
        </div>
    </form>

    <form id="discard-ai-draft" action="{{ route('instructor.question-banks.ai-authoring.destroy', $questionBank) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection