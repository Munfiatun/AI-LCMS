@extends('layouts.app')

@php
    $title = 'Builder Kuis';
    $breadcrumb = 'Builder Kuis';
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="quizBuilder()">
    @if($errors->any())
        <div role="alert" class="rounded-2xl border theme-danger-border theme-danger-surface px-4 py-3 text-sm theme-danger-text">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="teacher-page-header teacher-page-header-flush overflow-hidden border">
        <div class="p-6 lg:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full border theme-accent-border theme-accent-surface px-3 py-1 text-sm font-extrabold uppercase tracking-[0.18em] theme-accent-text">Question Composer</span>
                        <span class="rounded-full border theme-border theme-surface px-3 py-1 text-sm font-semibold theme-body">{{ $quiz->status === 'published' ? 'Published' : 'Draft' }}</span>
                    </div>
                    <h1 class="teacher-page-title text-2xl font-extrabold tracking-tight theme-heading sm:text-3xl">Builder Kuis: {{ $quiz->title }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 theme-body">Pilih soal dari Bank Soal, atur bobot poin, lalu sinkronkan komposisi kuis.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="rounded-xl border teacher-card px-4 py-3 sm:min-w-32 sm:text-right">
                        <p class="text-sm font-semibold uppercase tracking-wide theme-muted">Target Soal</p>
                        <p class="mt-1 text-xl font-extrabold" :class="selectedQuestions.length === {{ $quiz->total_questions }} ? 'theme-success-text' : 'theme-accent-text'">
                            <span x-text="selectedQuestions.length"></span> / {{ $quiz->total_questions }}
                        </p>
                    </div>
                    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="teacher-secondary-button inline-flex items-center justify-center rounded-xl border theme-border theme-surface px-4 py-2.5 text-sm font-semibold theme-body transition theme-border-hover theme-heading-hover">Kembali ke Review</a>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <section class="space-y-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-extrabold uppercase tracking-[0.18em] theme-muted">Question Banks</p>
                    <h2 class="mt-1 text-lg font-bold theme-heading">Pilih Soal</h2>
                </div>
                <p class="text-sm theme-muted">Klik bank untuk membuka daftar soal.</p>
            </div>

            @forelse($questionBanks as $bank)
                <article class="overflow-hidden rounded-2xl border teacher-card" x-data="{ open: false }">
                    <button type="button" class="teacher-secondary-button flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition theme-muted-surface-hover" @click="open = !open" :aria-expanded="open.toString()">
                        <div>
                            <h3 class="text-sm font-bold theme-heading">{{ $bank->title }}</h3>
                            <p class="mt-1 text-sm theme-muted">{{ $bank->questions->count() }} Soal Tersedia</p>
                        </div>
                        <svg class="h-5 w-5 shrink-0 theme-muted transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <div x-show="open" x-collapse class="divide-y divide-slate-800 border-t theme-border">
                        @forelse($bank->questions as $question)
                            <div class="flex flex-col gap-4 p-4 transition theme-muted-surface-hover sm:flex-row sm:items-start">
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-3 text-sm leading-6 theme-heading">{{ strip_tags($question->question_text) }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border theme-border theme-surface px-2 py-1 text-sm font-semibold theme-body">{{ str_replace('_', ' ', $question->type) }}</span>
                                        <span class="rounded-full border theme-border theme-surface px-2 py-1 text-sm font-semibold theme-body">{{ ucfirst($question->difficulty) }}</span>
                                        @if($question->needs_review)
                                            <span class="rounded-full border theme-warning-border theme-warning-surface px-2 py-1 text-sm font-semibold theme-warning-text">Perlu Review</span>
                                        @else
                                            <span class="rounded-full border theme-success-border theme-success-surface px-2 py-1 text-sm font-semibold theme-success-text">Terverifikasi</span>
                                        @endif
                                    </div>
                                </div>
                                <button type="button"
                                    @click="toggleQuestion({{ $question->id }}, {{ json_encode(strip_tags($question->question_text)) }}, '{{ $question->type }}')"
                                    class="inline-flex shrink-0 items-center justify-center rounded-xl border px-3 py-2 text-sm font-bold transition"
                                    :class="isSelected({{ $question->id }}) ? 'theme-danger-border theme-danger-surface theme-danger-text theme-danger-surface-hover' : 'theme-accent-border theme-accent-surface theme-accent-text theme-accent-surface-hover'">
                                    <span x-text="isSelected({{ $question->id }}) ? 'Hapus' : 'Pilih'"></span>
                                </button>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-center text-sm theme-muted">Bank ini belum memiliki soal.</div>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed teacher-card p-10 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border teacher-card theme-muted">?</div>
                    <h3 class="mt-4 text-sm font-bold theme-heading">Belum ada Bank Soal</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-5 theme-body">Tidak ada Bank Soal yang tersedia. Buat atau ekstrak soal terlebih dahulu.</p>
                    <a href="{{ route('instructor.question-banks.index') }}" class="mt-5 inline-flex rounded-xl theme-accent-fill px-4 py-2.5 text-sm font-semibold teacher-on-solid transition theme-accent-fill-hover">Buka Bank Soal</a>
                </div>
            @endforelse
        </section>

        <aside class="teacher-secondary-button h-fit rounded-2xl border theme-border theme-surface lg:sticky lg:top-6">
            <div class="border-b theme-border px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-extrabold uppercase tracking-[0.16em] theme-accent-text">Selected Set</p>
                        <h2 class="mt-1 text-base font-bold theme-heading">Soal Terpilih (<span x-text="selectedQuestions.length"></span>)</h2>
                    </div>
                    <span class="rounded-lg border theme-border theme-surface px-2 py-1 text-sm font-bold theme-body" x-text="`${selectedQuestions.length}/${{ $quiz->total_questions }}`"></span>
                </div>
            </div>

            <div class="max-h-[55vh] overflow-y-auto p-4">
                <template x-if="selectedQuestions.length === 0">
                    <div class="py-10 text-center">
                        <p class="text-sm font-semibold theme-body">Belum ada soal yang dipilih.</p>
                        <p class="mt-1 text-sm leading-5 theme-muted">Pilih soal dari panel Bank Soal di sebelah kiri.</p>
                    </div>
                </template>

                <ul class="space-y-3">
                    <template x-for="(q, index) in selectedQuestions" :key="q.id">
                        <li class="rounded-xl border theme-border theme-surface p-3">
                            <div class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border theme-accent-border theme-accent-surface text-sm font-extrabold theme-accent-text" x-text="index + 1"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-3 text-sm leading-5 theme-body" x-text="q.text"></p>
                                    <div class="mt-3 flex items-center justify-between gap-2 border-t theme-border pt-3">
                                        <span class="text-sm font-semibold theme-muted" x-text="q.type.replace('_', ' ')"></span>
                                        <div class="flex items-center gap-2">
                                            <label class="text-sm font-semibold theme-muted">Poin</label>
                                            <input type="number" x-model.number="q.points" min="1" max="100" class="w-16 rounded-lg border theme-border theme-surface px-2 py-1.5 text-sm theme-heading outline-none theme-focus-border">
                                        </div>
                                    </div>
                                </div>
                                <button @click="removeQuestion(q.id)" type="button" class="shrink-0 rounded-lg p-1 theme-muted transition theme-danger-surface-hover theme-danger-text-hover" aria-label="Hapus soal terpilih">✕</button>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <form action="{{ route('instructor.quizzes.sync-questions', $quiz) }}" method="POST" class="border-t theme-border p-4">
                @csrf
                <template x-for="(q, index) in selectedQuestions" :key="q.id">
                    <div>
                        <input type="hidden" :name="`questions[${index}][id]`" :value="q.id">
                        <input type="hidden" :name="`questions[${index}][points]`" :value="q.points">
                        <input type="hidden" :name="`questions[${index}][order]`" :value="index + 1">
                    </div>
                </template>
                <button type="submit" :disabled="selectedQuestions.length === 0" class="inline-flex w-full items-center justify-center rounded-xl theme-accent-fill px-4 py-2.5 text-sm font-bold teacher-on-solid shadow-lg shadow-indigo-950/25 transition theme-accent-fill-hover disabled:cursor-not-allowed disabled:opacity-40">Simpan & Sinkronisasi</button>
                <p class="mt-2 text-center text-sm leading-4 theme-muted">Urutan panel ini menjadi urutan soal saat randomisasi dinonaktifkan.</p>
            </form>
        </aside>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('quizBuilder', () => ({
        selectedQuestions: {{ \Illuminate\Support\Js::from($selectedQuestions) }},

        isSelected(id) {
            return this.selectedQuestions.some(q => q.id === id);
        },

        toggleQuestion(id, text, type) {
            if (this.isSelected(id)) {
                this.removeQuestion(id);
            } else {
                this.selectedQuestions.push({ id, text, type, points: 10 });
            }
        },

        removeQuestion(id) {
            this.selectedQuestions = this.selectedQuestions.filter(q => q.id !== id);
        }
    }));
});
</script>
@endsection
