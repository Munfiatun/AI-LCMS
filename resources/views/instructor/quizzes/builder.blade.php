@extends('layouts.app')

@php
    $title = 'Builder Kuis';
    $breadcrumb = 'Builder Kuis';
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-6" x-data="quizBuilder()">
    @if($errors->any())
        <div role="alert" class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/75 shadow-xl shadow-black/10">
        <div class="p-6 lg:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[0.18em] text-indigo-300">Question Composer</span>
                        <span class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-[10px] font-semibold text-slate-400">{{ $quiz->status === 'published' ? 'Published' : 'Draft' }}</span>
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Builder Kuis: {{ $quiz->title }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">Pilih soal dari Bank Soal, atur bobot poin, lalu sinkronkan komposisi kuis.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="rounded-xl border border-slate-800 bg-slate-950/55 px-4 py-3 sm:min-w-32 sm:text-right">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Target Soal</p>
                        <p class="mt-1 text-xl font-extrabold" :class="selectedQuestions.length === {{ $quiz->total_questions }} ? 'text-emerald-300' : 'text-indigo-300'">
                            <span x-text="selectedQuestions.length"></span> / {{ $quiz->total_questions }}
                        </p>
                    </div>
                    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-slate-600 hover:text-white">Kembali ke Review</a>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <section class="space-y-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-slate-500">Question Banks</p>
                    <h2 class="mt-1 text-lg font-bold text-white">Pilih Soal</h2>
                </div>
                <p class="text-xs text-slate-500">Klik bank untuk membuka daftar soal.</p>
            </div>

            @forelse($questionBanks as $bank)
                <article class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/65" x-data="{ open: false }">
                    <button type="button" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-slate-800/35" @click="open = !open" :aria-expanded="open.toString()">
                        <div>
                            <h3 class="text-sm font-bold text-slate-100">{{ $bank->title }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $bank->questions->count() }} Soal Tersedia</p>
                        </div>
                        <svg class="h-5 w-5 shrink-0 text-slate-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <div x-show="open" x-collapse class="divide-y divide-slate-800 border-t border-slate-800">
                        @forelse($bank->questions as $question)
                            <div class="flex flex-col gap-4 p-4 transition hover:bg-slate-800/20 sm:flex-row sm:items-start">
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-3 text-sm leading-6 text-slate-200">{{ strip_tags($question->question_text) }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border border-slate-700 bg-slate-950/60 px-2 py-1 text-[10px] font-semibold text-slate-400">{{ str_replace('_', ' ', $question->type) }}</span>
                                        <span class="rounded-full border border-slate-700 bg-slate-950/60 px-2 py-1 text-[10px] font-semibold text-slate-400">{{ ucfirst($question->difficulty) }}</span>
                                        @if($question->needs_review)
                                            <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold text-amber-300">Perlu Review</span>
                                        @else
                                            <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[10px] font-semibold text-emerald-300">Terverifikasi</span>
                                        @endif
                                    </div>
                                </div>
                                <button type="button"
                                    @click="toggleQuestion({{ $question->id }}, {{ json_encode(strip_tags($question->question_text)) }}, '{{ $question->type }}')"
                                    class="inline-flex shrink-0 items-center justify-center rounded-xl border px-3 py-2 text-xs font-bold transition"
                                    :class="isSelected({{ $question->id }}) ? 'border-rose-500/25 bg-rose-500/10 text-rose-300 hover:bg-rose-500/15' : 'border-indigo-500/25 bg-indigo-500/10 text-indigo-300 hover:bg-indigo-500/15'">
                                    <span x-text="isSelected({{ $question->id }}) ? 'Hapus' : 'Pilih'"></span>
                                </button>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-center text-sm text-slate-500">Bank ini belum memiliki soal.</div>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/45 p-10 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-800 bg-slate-950/60 text-slate-500">?</div>
                    <h3 class="mt-4 text-sm font-bold text-white">Belum ada Bank Soal</h3>
                    <p class="mx-auto mt-2 max-w-md text-xs leading-5 text-slate-400">Tidak ada Bank Soal yang tersedia. Buat atau ekstrak soal terlebih dahulu.</p>
                    <a href="{{ route('instructor.question-banks.index') }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">Buka Bank Soal</a>
                </div>
            @endforelse
        </section>

        <aside class="h-fit rounded-2xl border border-slate-800 bg-slate-900/75 lg:sticky lg:top-6">
            <div class="border-b border-slate-800 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-indigo-300">Selected Set</p>
                        <h2 class="mt-1 text-base font-bold text-white">Soal Terpilih (<span x-text="selectedQuestions.length"></span>)</h2>
                    </div>
                    <span class="rounded-lg border border-slate-700 bg-slate-950/60 px-2 py-1 text-[10px] font-bold text-slate-400" x-text="`${selectedQuestions.length}/${{ $quiz->total_questions }}`"></span>
                </div>
            </div>

            <div class="max-h-[55vh] overflow-y-auto p-4">
                <template x-if="selectedQuestions.length === 0">
                    <div class="py-10 text-center">
                        <p class="text-sm font-semibold text-slate-400">Belum ada soal yang dipilih.</p>
                        <p class="mt-1 text-xs leading-5 text-slate-600">Pilih soal dari panel Bank Soal di sebelah kiri.</p>
                    </div>
                </template>

                <ul class="space-y-3">
                    <template x-for="(q, index) in selectedQuestions" :key="q.id">
                        <li class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                            <div class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-indigo-500/20 bg-indigo-500/10 text-[11px] font-extrabold text-indigo-300" x-text="index + 1"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-3 text-xs leading-5 text-slate-300" x-text="q.text"></p>
                                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-800 pt-3">
                                        <span class="text-[10px] font-semibold text-slate-500" x-text="q.type.replace('_', ' ')"></span>
                                        <div class="flex items-center gap-2">
                                            <label class="text-[10px] font-semibold text-slate-500">Poin</label>
                                            <input type="number" x-model.number="q.points" min="1" max="100" class="w-16 rounded-lg border border-slate-700 bg-slate-900 px-2 py-1.5 text-xs text-white outline-none focus:border-indigo-500">
                                        </div>
                                    </div>
                                </div>
                                <button @click="removeQuestion(q.id)" type="button" class="shrink-0 rounded-lg p-1 text-slate-500 transition hover:bg-rose-500/10 hover:text-rose-300" aria-label="Hapus soal terpilih">✕</button>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <form action="{{ route('instructor.quizzes.sync-questions', $quiz) }}" method="POST" class="border-t border-slate-800 p-4">
                @csrf
                <template x-for="(q, index) in selectedQuestions" :key="q.id">
                    <div>
                        <input type="hidden" :name="`questions[${index}][id]`" :value="q.id">
                        <input type="hidden" :name="`questions[${index}][points]`" :value="q.points">
                        <input type="hidden" :name="`questions[${index}][order]`" :value="index + 1">
                    </div>
                </template>
                <button type="submit" :disabled="selectedQuestions.length === 0" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-950/25 transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-40">Simpan & Sinkronisasi</button>
                <p class="mt-2 text-center text-[10px] leading-4 text-slate-600">Urutan panel ini menjadi urutan soal saat randomisasi dinonaktifkan.</p>
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