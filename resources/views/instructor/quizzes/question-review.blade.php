@php
    $sourceLabel = match ($question->answer_source) {
        \App\Models\Question::SOURCE_EXPLICIT => 'Kunci eksplisit dari sumber',
        \App\Models\Question::SOURCE_MANUAL => 'Kunci ditetapkan guru',
        default => $question->needs_review ? 'Kunci hasil inferensi AI' : 'Inferensi AI diverifikasi guru',
    };
    $sourceClass = match ($question->answer_source) {
        \App\Models\Question::SOURCE_EXPLICIT => 'theme-success-border theme-success-surface theme-success-text',
        \App\Models\Question::SOURCE_MANUAL => 'theme-accent-border theme-accent-surface theme-accent-text',
        default => $question->needs_review
            ? 'theme-warning-border theme-warning-surface theme-warning-text'
            : 'theme-accent-border theme-accent-surface theme-accent-text',
    };
@endphp

<details class="group overflow-hidden rounded-2xl border {{ $question->needs_review ? 'theme-warning-border theme-surface' : 'theme-success-border theme-surface' }}" @if($question->needs_review) open @endif>
    <summary class="flex cursor-pointer list-none items-start gap-4 p-5 marker:hidden">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $question->needs_review ? 'theme-warning-surface theme-warning-text' : 'theme-success-surface theme-success-text' }} text-sm font-extrabold">
            {{ $number ?? $question->order }}
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <p class="pr-4 text-sm font-semibold leading-6 theme-heading">{{ $question->question_text }}</p>
                <span class="shrink-0 rounded-full border px-2.5 py-1 text-sm font-bold uppercase tracking-wide {{ $question->needs_review ? 'theme-warning-border theme-warning-surface theme-warning-text' : 'theme-success-border theme-success-surface theme-success-text' }}">
                    {{ $question->needs_review ? 'Perlu Review' : 'Terverifikasi' }}
                </span>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full border px-2.5 py-1 text-sm font-bold uppercase tracking-wide {{ $sourceClass }}">{{ $sourceLabel }}</span>
                <span class="rounded-full border theme-border theme-surface px-2.5 py-1 text-sm font-semibold theme-body">{{ $question->topic }}</span>
                <span class="rounded-full border theme-border theme-surface px-2.5 py-1 text-sm font-semibold theme-body">{{ ucfirst($question->difficulty) }}</span>
            </div>
        </div>
        <svg class="mt-2 h-4 w-4 shrink-0 theme-muted transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
    </summary>

    <div class="border-t theme-border px-5 pb-5 pt-4 sm:pl-[4.5rem]">
        @if($question->answer_source === \App\Models\Question::SOURCE_INFERRED && $question->needs_review)
            <div class="mb-4 rounded-xl border theme-warning-border theme-warning-surface px-4 py-3 text-sm leading-5 theme-warning-text">
                Jawaban benar dipilih AI berdasarkan materi dan belum dianggap valid sampai guru memverifikasinya.
            </div>
        @endif

        @if(filled($question->source_excerpt))
            <div class="mb-5 overflow-hidden rounded-xl border theme-accent-border theme-accent-surface">
                <div class="flex items-center justify-between gap-3 border-b theme-accent-border px-4 py-2.5">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 theme-accent-text" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        <span class="text-sm font-bold uppercase tracking-[0.15em] theme-accent-text">Referensi Materi</span>
                    </div>
                    @if($question->source_slide_number)
                        <span class="rounded-full border theme-accent-border theme-accent-surface px-2.5 py-1 text-sm font-bold theme-accent-text">Slide {{ $question->source_slide_number }}</span>
                    @endif
                </div>
                <blockquote class="px-4 py-3 text-sm leading-6 theme-body">“{{ $question->source_excerpt }}”</blockquote>
                <p class="border-t theme-accent-border px-4 py-2 text-sm leading-5 theme-muted">Cuplikan ini disimpan dari slide yang dirujuk AI saat quiz dibuat, sehingga guru dapat membandingkan soal dengan materi sumber.</p>
            </div>
        @endif

        <form action="{{ route('instructor.questions.update', $question) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-2 block text-sm font-semibold theme-body">Pertanyaan</label>
                <textarea name="question_text" required rows="3" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-6 theme-heading theme-placeholder theme-focus-border focus:ring-1 focus:ring-indigo-500">{{ $question->question_text }}</textarea>
            </div>

            <fieldset>
                <legend class="mb-3 text-sm font-semibold theme-body">Pilihan jawaban <span class="font-normal theme-muted">— pilih satu jawaban benar</span></legend>
                <div class="grid gap-2">
                    @foreach($question->options as $index => $option)
                        <label class="flex items-center gap-3 rounded-xl border {{ $option->is_correct ? 'theme-success-border theme-success-surface' : 'theme-border theme-surface' }} p-3 transition theme-border-hover">
                            <input type="radio" name="correct_option" value="{{ $index }}" @checked($option->is_correct) required aria-label="Jawaban benar pilihan {{ $index + 1 }}" class="h-4 w-4 theme-border theme-surface theme-success-text focus:ring-emerald-500">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border theme-border theme-surface text-sm font-bold theme-body">{{ chr(65 + $index) }}</span>
                            <input type="text" name="options[{{ $index }}]" value="{{ $option->option_text }}" required class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm theme-heading focus:ring-0" aria-label="Pilihan {{ $index + 1 }}">
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid gap-4 lg:grid-cols-[1fr_180px]">
                <div>
                    <label class="mb-2 block text-sm font-semibold theme-body">Pembahasan</label>
                    <textarea name="explanation" rows="3" class="w-full rounded-xl border theme-border theme-surface px-4 py-3 text-sm leading-6 theme-heading theme-placeholder theme-focus-border focus:ring-1 focus:ring-indigo-500">{{ $question->explanation }}</textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold theme-body">Tingkat Kesulitan</label>
                    <select name="difficulty" class="w-full rounded-xl border theme-border theme-surface px-3 py-3 text-sm theme-heading theme-focus-border focus:ring-1 focus:ring-indigo-500">
                        @foreach(['easy', 'medium', 'hard'] as $difficulty)
                            <option value="{{ $difficulty }}" @selected($question->difficulty === $difficulty)>{{ ucfirst($difficulty) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <input type="hidden" name="points" value="{{ $question->points }}">

            <div class="flex flex-col gap-3 border-t theme-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm leading-5 theme-muted">Menyimpan soal akan menandainya sebagai hasil verifikasi guru.</p>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl theme-accent-fill px-4 py-2.5 text-sm font-bold teacher-on-solid shadow-lg shadow-indigo-600/20 transition theme-accent-fill-hover">
                    Simpan & Verifikasi Soal
                </button>
            </div>
        </form>
    </div>
</details>
