@extends('layouts.app')

@php
    $title = 'Review Slidebook: ' . $slidebook->title;
    $breadcrumb = 'Review Slidebook';
@endphp
@inject('layoutRegistry', 'App\Services\SlideLayoutRegistry')

@section('content')
@if(in_array($slidebook->status, ['draft', 'review'], true) && $reviewErrors)
    <p class="text-sm theme-warning-text">{{ implode(' ', $reviewErrors) }}</p>
@endif
<div class="space-y-6" x-data="slidebookReviewManager()">
    {{-- Header & Control Bar --}}
    <div class="teacher-page-header p-6 border">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm theme-body mb-2">
                    <a href="{{ route('instructor.courses.edit', $course) }}" class="theme-heading-hover transition-colors">&larr; {{ $course->title }}</a>
                    <span class="theme-muted">&bull;</span>
                    <a href="{{ route('instructor.materials.edit', $material) }}" class="theme-heading-hover transition-colors">{{ $material->title }}</a>
                    <span class="theme-muted">&bull;</span>
                    <span class="theme-accent-text font-medium">Slidebook v{{ $slidebook->version }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="teacher-page-title text-2xl font-extrabold theme-heading tracking-tight">{{ $slidebook->title }}</h1>
                    @if($slidebook->status === 'published')
                        <span class="px-2.5 py-1 text-sm font-bold uppercase rounded-full theme-success-surface theme-success-text border theme-success-border">Published</span>
                    @elseif($slidebook->status === 'review')
                        <span class="px-2.5 py-1 text-sm font-bold uppercase rounded-full theme-warning-surface theme-warning-text border theme-warning-border animate-pulse">Menunggu Review Guru</span>
                    @else
                        <span class="px-2.5 py-1 text-sm font-bold uppercase rounded-full theme-muted-surface theme-body border theme-border">{{ $slidebook->approved_by ? 'Approved' : ucfirst($slidebook->status) }}</span>
                    @endif
                </div>
                <p class="text-sm theme-body mt-1">{{ $slidebook->subtitle ?? 'Slide presentasi terstruktur berbasis ekstraksi AI' }} &bull; Total {{ $slidebook->slides->count() }} Lembar Slide</p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2.5">
                @can('create', \App\Models\Quiz::class)
                    <a href="{{ route('instructor.quizzes.index', ['slidebook_id' => $slidebook->id]) }}" class="teacher-secondary-button px-3.5 py-2 text-sm font-semibold rounded-xl theme-muted-surface theme-muted-surface-hover theme-heading border theme-border transition-colors">Generate Quiz with AI</a>
                @endcan
                @if($slidebook->isPublished())
                    <form action="{{ route('instructor.slidebooks.revision', $slidebook) }}" method="POST">
                        @csrf
                        <button type="submit">Create Revision</button>
                    </form>
                @endif
                {{-- Regenerate Form --}}
                <form action="{{ route('instructor.materials.ai.slidebook', $material) }}" method="POST" onsubmit="return confirm('Regenerate akan membuat versi slidebook baru dari dokumen sumber. Lanjutkan?')">
                    @csrf
                    <input type="hidden" name="regenerate" value="1">
                    <button type="submit" class="teacher-secondary-button px-3.5 py-2 text-sm font-semibold rounded-xl theme-muted-surface theme-muted-surface-hover theme-heading border theme-border transition-colors flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4 theme-accent-text" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Regenerate AI
                    </button>
                </form>

                {{-- Approve Button (if in review) --}}
                @if($reviewErrors === [] && ! $slidebook->approved_by)
                    <form action="{{ route('instructor.slidebooks.approve', $slidebook) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 text-sm font-bold rounded-xl theme-warning-surface theme-warning-surface-hover theme-warning-text border theme-warning-border transition-colors flex items-center gap-1.5 shadow-sm">
                            <svg class="w-4 h-4 theme-warning-text" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Setujui Draft (Approve)
                        </button>
                    </form>
                @endif

                {{-- Publish Button --}}
                @if($publicationErrors === [])
                    <form action="{{ route('instructor.slidebooks.publish', $slidebook) }}" method="POST" onsubmit="return confirm('Publikasikan slidebook ini ke siswa? Siswa yang terdaftar akan dapat langsung membaca materi ini.')">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-bold rounded-xl theme-accent-fill theme-accent-fill-hover teacher-on-solid shadow-lg shadow-emerald-600/25 transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Rilis ke Siswa (Publish)
                        </button>
                    </form>
                @endif

                @if(in_array($slidebook->status, ['published', 'draft', 'review'], true))
                    <a href="{{ route('instructor.slidebooks.preview', $slidebook) }}" target="_blank" rel="noopener" class="px-3 py-2 text-sm font-semibold rounded-xl theme-accent-surface theme-accent-surface-hover theme-accent-text border theme-accent-border transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        Preview Siswa
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Design Configuration Panel --}}
    @if(in_array($slidebook->status, ['draft', 'review'], true))
        <div class="p-5 rounded-2xl theme-surface border theme-border shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-sm font-bold theme-heading">Pengaturan Desain & Target Siswa</h3>
                    <p class="text-sm theme-body mt-1">Target siswa mengatur kepadatan isi dan skala visual tanpa menghapus teks sumber.</p>
                </div>
            </div>
            <form action="{{ route('instructor.slidebooks.design.update', $slidebook) }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                @csrf
                @method('PUT')
                @php
                    $design = $slidebook->design_settings ?? ['preset' => 'indigo-dark'];
                    $preset = $design['preset'] ?? 'indigo-dark';
                    $audience = $design['audience'] ?? 'sma-smk';
                @endphp
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Preset Tema Desain</label>
                    <select name="preset" class="w-full px-3 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="indigo-dark" {{ $preset === 'indigo-dark' ? 'selected' : '' }}>Indigo Dark (Default)</option>
                        <option value="modern-tech" {{ $preset === 'modern-tech' ? 'selected' : '' }}>Modern Tech</option>
                        <option value="academic-blue" {{ $preset === 'academic-blue' ? 'selected' : '' }}>Academic Blue</option>
                        <option value="creative-education" {{ $preset === 'creative-education' ? 'selected' : '' }}>Creative Education</option>
                        <option value="fresh-learning" {{ $preset === 'fresh-learning' ? 'selected' : '' }}>Fresh Learning</option>
                        <option value="minimalist" {{ $preset === 'minimalist' ? 'selected' : '' }}>Minimalist</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Target Siswa</label>
                    <select name="audience" class="w-full px-3 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="smp" {{ $audience === 'smp' ? 'selected' : '' }}>SMP — lebih visual & ringkas</option>
                        <option value="sma-smk" {{ $audience === 'sma-smk' ? 'selected' : '' }}>SMA/SMK — visual + detail</option>
                        <option value="general" {{ $audience === 'general' ? 'selected' : '' }}>Umum — kepadatan standar</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full px-4 py-2 text-sm font-bold teacher-on-solid theme-accent-fill theme-accent-fill-hover rounded-xl shadow-md transition-colors">
                        Simpan Desain
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Split Screen: Source Document vs AI Slidebook Deck --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Left: Source Document Extracted Text (5 Cols) --}}
        <div class="lg:col-span-5 space-y-4">
            <div class="p-5 rounded-2xl theme-surface border theme-border shadow flex flex-col h-[760px]">
                <div class="flex items-center justify-between border-b theme-border pb-3 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full theme-accent-fill animate-pulse"></span>
                        <h2 class="text-sm font-bold theme-heading uppercase tracking-wider">Teks Sumber Dokumen</h2>
                    </div>
                    @php
                        $firstDoc = $material->documents->first();
                        $extraction = $firstDoc?->extraction;
                    @endphp
                    @if($extraction)
                        <span class="text-sm px-2 py-0.5 rounded-full theme-muted-surface theme-body font-mono">
                            {{ number_format($extraction->word_count) }} kata &bull; {{ $extraction->page_count ?? 1 }} hlm
                        </span>
                    @endif
                </div>

                {{-- Search Filter in Source Text --}}
                <div class="mb-3 relative">
                    <input type="text" x-model="searchQuery" placeholder="Cari kata kunci pada dokumen sumber..."
                           class="w-full px-3 py-1.5 pl-8 theme-surface border theme-border rounded-lg text-sm theme-heading placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-cyan-500">
                    <svg class="w-3.5 h-3.5 theme-muted absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>

                {{-- Source Text Scrollable Viewport --}}
                <div class="flex-1 overflow-y-auto pr-2 space-y-3 font-mono text-sm theme-body leading-relaxed theme-surface p-4 rounded-xl border theme-border">
                    @if($material->documents->isEmpty())
                        <div class="text-center py-12 theme-muted">
                            <p>Tidak ada dokumen terlampir.</p>
                            <p class="text-sm mt-1 theme-muted">AI menggunakan teks konten materi langsung.</p>
                        </div>
                    @else
                        @foreach($material->documents as $doc)
                            <div class="pb-4 mb-4 border-b theme-border last:border-0">
                                <div class="flex items-center justify-between text-sm theme-accent-text font-bold mb-2">
                                    <span>📄 {{ $doc->original_name }}</span>
                                    <span class="theme-muted uppercase">{{ $doc->extension }}</span>
                                </div>
                                @if($doc->extraction && !empty($doc->extraction->content))
                                    <div class="whitespace-pre-wrap selection:bg-cyan-500/30" x-html="highlightText(@js($doc->extraction->content))"></div>
                                @else
                                    <p class="theme-muted italic">Ekstraksi dokumen belum tersedia atau gagal diproses.</p>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Generated Slide Deck & Human-in-the-loop Editor (7 Cols) --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="p-5 rounded-2xl theme-surface border theme-border shadow flex flex-col h-[760px]">
                <div class="flex items-center justify-between border-b theme-border pb-3 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full theme-accent-fill"></span>
                        <h2 class="text-sm font-bold theme-heading uppercase tracking-wider">Slidebook Deck (Human-in-the-Loop)</h2>
                    </div>
                    @if(in_array($slidebook->status, ['draft', 'review'], true))
                        <button type="button" @click="openAddSlideModal()"
                                class="px-3 py-1.5 text-sm font-bold teacher-on-solid theme-accent-fill theme-accent-fill-hover rounded-lg transition-colors flex items-center gap-1 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Tambah Slide
                        </button>
                    @endif
                </div>

                {{-- Scrollable Slide Deck List --}}
                <div class="flex-1 overflow-y-auto pr-2 space-y-4">
                    @forelse($slidebook->slides as $slide)
                        <div class="p-4 rounded-xl border transition-all duration-200 {{ $slide->needs_review ? 'theme-warning-surface theme-warning-border theme-warning-border-hover' : 'theme-surface theme-border theme-accent-border-hover' }}">
                            {{-- Slide Card Header --}}
                            <div class="flex items-start justify-between gap-3 mb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg theme-accent-surface theme-accent-text font-bold text-sm flex items-center justify-center border theme-accent-border">
                                        {{ $slide->order }}
                                    </span>
                                    <div>
                                        <h3 class="text-sm font-bold theme-heading">{{ $slide->title }}</h3>
                                        @if($slide->subtitle)
                                            <p class="text-sm theme-body">{{ $slide->subtitle }}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Badges & Controls --}}
                                <div class="flex items-center gap-2">
                                    @if($slide->needs_review)
                                        <span class="px-2 py-0.5 text-sm font-bold rounded-full theme-warning-surface theme-warning-text border theme-warning-border flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                            Perlu Verifikasi Guru
                                        </span>
                                    @endif

                                    @if(!empty($slide->source_reference))
                                        <span class="px-2 py-0.5 text-sm font-medium rounded-full theme-accent-surface theme-accent-text border theme-accent-border">
                                            Rujukan: {{ json_encode($slide->source_reference) }}
                                        </span>
                                    @endif

                                    @if(in_array($slidebook->status, ['draft', 'review'], true))
                                        {{-- Edit & Delete Buttons --}}
                                        <button type="button" @click="openEditSlideModal(@js($slide))"
                                                class="p-1.5 rounded-lg theme-body theme-heading-hover theme-muted-surface-hover transition-colors" title="Edit Slide">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>

                                        <form action="{{ route('instructor.slides.destroy', $slide) }}" method="POST" onsubmit="return confirm('Hapus lembar slide ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg theme-muted theme-danger-text-hover theme-danger-surface-hover transition-colors" title="Hapus Slide">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            {{-- Slide Content Preview --}}
                            <div class="text-sm theme-body leading-relaxed whitespace-pre-wrap theme-surface p-3 rounded-lg border theme-border selection:bg-indigo-500/30">
                                {{ $slide->content }}
                            </div>

                            {{-- Slide Summary Pill --}}
                            @if($slide->summary)
                                <div class="mt-2.5 p-2 rounded-lg theme-accent-surface border theme-accent-border flex items-start gap-2 text-sm theme-accent-text">
                                    <span class="font-bold theme-accent-text flex-shrink-0">Intisari:</span>
                                    <span>{{ $slide->summary }}</span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-16 theme-muted">
                            <p class="text-sm font-semibold">Slidebook belum memiliki lembar slide.</p>
                            <p class="text-sm mt-1">Klik "Tambah Slide" atau jalankan "Regenerate AI" di atas.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Slide Modal --}}
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 teacher-backdrop backdrop-blur-sm">
        <div class="w-full max-w-xl theme-surface border theme-border rounded-2xl shadow-2xl p-6 space-y-4" @click.outside="editModal = false">
            <div class="flex items-center justify-between border-b theme-border pb-3">
                <h3 class="text-base font-bold theme-heading flex items-center gap-2">
                    <svg class="w-5 h-5 theme-accent-text" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit Lembar Slide #<span x-text="activeSlide.order"></span>
                </h3>
                <button type="button" @click="editModal = false" class="theme-muted theme-heading-hover">&times;</button>
            </div>

            <form :action="'/instructor/slides/' + activeSlide.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Judul Slide <span class="theme-danger-text">*</span></label>
                    <input type="text" name="title" x-model="activeSlide.title" required
                           class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Sub-judul / Fokus</label>
                    <input type="text" name="subtitle" x-model="activeSlide.subtitle"
                           class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Konten Slide <span class="theme-danger-text">*</span></label>
                    <textarea name="content" x-model="activeSlide.content" rows="6" required
                              class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm font-mono focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Layout Presentasi (Opsional)</label>
                    <select name="layout" x-model="activeSlide.layout"
                            class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">(Otomatis sesuai konten)</option>
                        @foreach($layoutRegistry->getAvailableLayouts() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Intisari / Rangkuman</label>
                    <input type="text" name="summary" x-model="activeSlide.summary"
                           class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex items-center gap-3 p-3 rounded-xl theme-surface border theme-border">
                    <input type="checkbox" id="needs_review" name="needs_review" value="1" :checked="activeSlide.needs_review"
                           class="w-4 h-4 rounded theme-warning-text theme-surface theme-border focus:ring-amber-500">
                    <label for="needs_review" class="text-sm theme-body cursor-pointer">
                        Tandai sebagai <span class="theme-warning-text font-bold">Perlu Verifikasi Guru</span> jika konten memerlukan pengecekan lebih lanjut.
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t theme-border">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-sm font-semibold theme-body theme-heading-hover rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold teacher-on-solid theme-accent-fill theme-accent-fill-hover rounded-xl shadow-md transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Slide Modal --}}
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 teacher-backdrop backdrop-blur-sm">
        <div class="w-full max-w-xl theme-surface border theme-border rounded-2xl shadow-2xl p-6 space-y-4" @click.outside="addModal = false">
            <div class="flex items-center justify-between border-b theme-border pb-3">
                <h3 class="text-base font-bold theme-heading flex items-center gap-2">
                    <svg class="w-5 h-5 theme-accent-text" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Tambah Lembar Slide Baru
                </h3>
                <button type="button" @click="addModal = false" class="theme-muted theme-heading-hover">&times;</button>
            </div>

            <form action="{{ route('instructor.slidebooks.slides.store', $slidebook) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Judul Slide <span class="theme-danger-text">*</span></label>
                    <input type="text" name="title" required placeholder="Contoh: Prinsip Dasar Algoritma"
                           class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Sub-judul / Fokus</label>
                    <input type="text" name="subtitle" placeholder="Contoh: Efisiensi & Kompleksitas Waktu"
                           class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Konten Slide <span class="theme-danger-text">*</span></label>
                    <textarea name="content" rows="6" required placeholder="Tuliskan poin-poin materi slide..."
                              class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm font-mono focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Layout Presentasi (Opsional)</label>
                    <select name="layout"
                            class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">(Otomatis sesuai konten)</option>
                        @foreach($layoutRegistry->getAvailableLayouts() as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Intisari / Rangkuman</label>
                    <input type="text" name="summary" placeholder="Poin kunci yang wajib dipahami siswa"
                           class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t theme-border">
                    <button type="button" @click="addModal = false" class="px-4 py-2 text-sm font-semibold theme-body theme-heading-hover rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold teacher-on-solid theme-accent-fill theme-accent-fill-hover rounded-xl shadow-md transition-colors">Tambah ke Deck</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function slidebookReviewManager() {
    return {
        searchQuery: '',
        editModal: false,
        addModal: false,
        activeSlide: {},

        openEditSlideModal(slide) {
            this.activeSlide = JSON.parse(JSON.stringify(slide));
            this.editModal = true;
        },

        openAddSlideModal() {
            this.addModal = true;
        },

        highlightText(content) {
            if (!this.searchQuery || this.searchQuery.trim() === '') {
                return this.escapeHtml(content);
            }
            const query = this.searchQuery.trim();
            const escaped = this.escapeHtml(content);
            const regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            return escaped.replace(regex, '<mark class="theme-warning-surface theme-warning-text font-bold px-0.5 rounded">$1</mark>');
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
}
</script>
@endsection
