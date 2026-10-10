@extends('layouts.guest', ['focusMode' => true])
@inject('presenter', 'App\Services\SlidePresentation')
@inject('designService', 'App\Services\SlidebookDesignService')
@inject('visualEnrichment', 'App\Services\SlideVisualEnrichment')
@inject('visualScene', 'App\Services\SlideVisualScene')

@php
    $title = $slidebook->title . ' — Presentasi Slidebook';
    $preview = $isPreview ?? false;
    $returnUrl = $preview ? route('instructor.materials.slidebook.review', $material) : route('student.courses.continue', $course);
    $totalSlides = $slides->count();
    $designTokens = $designService->getDesignTokens($slidebook);
    $initialVisibleBlocks = $designTokens['initial_visible_blocks'] ?? 5;
@endphp

@section('content')
@include('student.slidebooks.presentation-styles')
@include('student.slidebooks.presentation-visual-rich')
@include('student.slidebooks.presentation-visual-learning')
<div class="slide-presentation min-h-screen flex flex-col {{ $designTokens['font_class'] ?? 'font-sans' }} {{ $designTokens['audience_class'] ?? 'audience-sma_smk' }}" style="{{ $designTokens['css_variables'] ?? '' }}" x-data="studentSlidePresentation({{ $totalSlides }}, {{ $initialVisibleBlocks }})" @keydown.window="handleKey($event)">
    {{-- Header --}}
    <header class="presentation-header border-b border-slate-800 flex items-center justify-between gap-4 px-4 py-4 sm:px-8">
        <div class="min-w-0 flex items-center gap-4">
            <a href="{{ $returnUrl }}" class="presentation-button shrink-0" aria-label="{{ $preview ? 'Tutup Preview' : 'Kembali ke Kursus' }}">
                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m14 6-6 6 6 6M8 12h13"/></svg>
                <span class="hidden sm:inline">{{ $preview ? 'Tutup Preview' : 'Kursus' }}</span>
            </a>
            <div class="min-w-0">
                <p class="text-xs text-indigo-300 mb-1">{{ $preview ? 'Preview Siswa' : 'Ruang belajar' }}</p>
                <h1 class="text-sm font-semibold truncate">{{ $slidebook->title }}</h1>
                <p class="text-xs text-slate-400 truncate">{{ $course->title }}</p>
            </div>
        </div>
        <button type="button" class="presentation-button shrink-0" @click="toggleFullscreen()" aria-label="Ubah mode layar penuh" title="Layar penuh (F)">
            <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/></svg>
        </button>
    </header>

    {{-- Progress Bar --}}
    <div class="presentation-progress" role="progressbar" aria-label="Posisi slide" aria-valuemin="0" aria-valuemax="{{ $totalSlides }}" :aria-valuenow="total ? currentIndex + 1 : 0">
        <span :style="{ width: (total ? (currentIndex + 1) / total * 100 : 0) + '%' }"></span>
    </div>

    {{-- Screen reader announcements --}}
    <p class="sr-only" role="status" x-text="announcement"></p>
    <p class="text-sm text-amber-200 px-4" role="status" x-show="fullscreenMessage" x-text="fullscreenMessage" x-cloak></p>

    {{-- Slide Stage --}}
    <div class="presentation-stage flex-1" id="presentationContainer">
        @forelse($slides as $slide)
            @php
                $presentation = $presenter->present($slide->title, $slide->content, $slide->layout);
                $visualStats = $visualEnrichment->percentageStats($slide->content);
                $heroLayouts = ['cover', 'section-divider', 'closing'];
                $scene = $visualScene->sceneFor($slide->title, $slide->content, $presentation['layout']);
            @endphp
            <article class="presentation-slide visual-rich-slide layout-{{ $presentation['layout'] }} {{ $scene ? 'visual-learning-with-scene' : '' }}"
                     data-layout="{{ $presentation['layout'] }}"
                     data-slide-index="{{ $loop->index }}"
                     x-show="currentIndex === {{ $loop->index }}"
                     @if(!$loop->first) style="display:none" @endif
                     :data-entering="enteringSlide === {{ $loop->index }} ? 'true' : null"
                     aria-labelledby="slide-title-{{ $slide->id }}">

                {{-- Slide Header --}}
                <header class="slide-heading">
                    <div class="flex flex-wrap items-center gap-3 mb-5" @if(in_array($presentation['layout'], $heroLayouts, true)) style="display:none;" @endif>
                        <span class="slide-kicker">{{ $presentation['label'] }}</span>
                        <span class="text-xs text-slate-400">Slide {{ $loop->iteration }} dari {{ $totalSlides }}</span>
                    </div>
                    <h2 id="slide-title-{{ $slide->id }}" tabindex="-1">{{ $slide->title }}</h2>
                    @if($slide->subtitle)<p class="slide-subtitle">{{ $slide->subtitle }}</p>@endif

                    {{-- Signal Terms Bar --}}
                    @if(count($presentation['signalTerms']) > 0)
                        <div class="signal-bar" aria-label="Kata kunci terkait">
                            @foreach($presentation['signalTerms'] as $term)
                                <span class="signal-badge">{{ $term }}</span>
                            @endforeach
                        </div>
                    @endif
                </header>

                <div class="visual-learning-main">
                    @if($scene)
                        @include('student.slidebooks.visual-scene', ['scene' => $scene, 'slide' => $slide])
                    @endif

                    <div class="visual-learning-content min-w-0">
                        {{-- Slide Body: layout-specific composition --}}
                        <div class="slide-composition">
                            @if($presentation['layout'] === 'concept')
                                {{-- CONCEPT: semantic scene already acts as the main visual anchor when available. --}}
                                @if(!$scene)
                                    <div class="concept-mark" aria-hidden="true">
                                        @if($presentation['icon'])
                                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="{{ $presentation['icon'] }}"/></svg>
                                        @else
                                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5"/></svg>
                                        @endif
                                    </div>
                                @endif
                                <div class="slide-blocks">
                                    @foreach($presentation['blocks'] as $block)
                                        <div class="slide-block block-{{ $block['type'] }}" data-reveal style="--reveal-delay: {{ min($loop->index, 4) * 120 }}ms">
                                            @include('student.slidebooks.presentation-block', ['block' => $block, 'layout' => $presentation['layout'], 'presenter' => $presenter, 'designTokens' => $designTokens])
                                        </div>
                                    @endforeach
                                </div>

                            @elseif(in_array($presentation['layout'], ['process', 'timeline']))
                                {{-- PROCESS / TIMELINE: Sequential layout --}}
                                <div class="slide-blocks">
                                    @foreach($presentation['blocks'] as $block)
                                        <div class="slide-block block-{{ $block['type'] }}" data-reveal style="--reveal-delay: {{ min($loop->index, 6) * 150 }}ms"
                                             x-show="visibleBlocks > {{ $loop->index }}" x-cloak>
                                            @include('student.slidebooks.presentation-block', ['block' => $block, 'layout' => $presentation['layout'], 'presenter' => $presenter, 'designTokens' => $designTokens])
                                        </div>
                                    @endforeach
                                </div>

                            @elseif(in_array($presentation['layout'], ['comparison', 'key-points']))
                                {{-- COMPARISON / KEY-POINTS: Grid of cards --}}
                                <div class="slide-blocks">
                                    @foreach($presentation['blocks'] as $block)
                                        <div class="slide-block block-{{ $block['type'] }}" data-reveal style="--reveal-delay: {{ min($loop->index, 5) * 100 }}ms"
                                             x-show="visibleBlocks > {{ $loop->index }}" x-cloak>
                                            @include('student.slidebooks.presentation-block', ['block' => $block, 'layout' => $presentation['layout'], 'presenter' => $presenter, 'designTokens' => $designTokens])
                                        </div>
                                    @endforeach
                                </div>

                            @elseif(in_array($presentation['layout'], ['summary', 'cover', 'section-divider', 'closing']))
                                {{-- SUMMARY / COVER / SECTION-DIVIDER / CLOSING: Clean checklist or centered --}}
                                <div class="slide-blocks">
                                    @foreach($presentation['blocks'] as $block)
                                        <div class="slide-block block-{{ $block['type'] }}" data-reveal style="--reveal-delay: {{ min($loop->index, 6) * 80 }}ms">
                                            @include('student.slidebooks.presentation-block', ['block' => $block, 'layout' => $presentation['layout'], 'presenter' => $presenter, 'designTokens' => $designTokens])
                                        </div>
                                    @endforeach
                                </div>

                            @else
                                {{-- DEFAULT: case-study, definition, image-focus, quote, code, diagram, checkpoint, example, reading --}}
                                <div class="slide-blocks">
                                    @foreach($presentation['blocks'] as $block)
                                        <div class="slide-block block-{{ $block['type'] }}" data-reveal style="--reveal-delay: {{ min($loop->index, 4) * 100 }}ms"
                                             x-show="visibleBlocks > {{ $loop->index }}" x-cloak>
                                            @include('student.slidebooks.presentation-block', ['block' => $block, 'layout' => $presentation['layout'], 'presenter' => $presenter, 'designTokens' => $designTokens])
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Source-faithful visual data: generated only from explicit labelled percentages in the slide content. --}}
                        @if(count($visualStats) >= 2 && !in_array($presentation['layout'], ['cover', 'section-divider', 'closing', 'code', 'quote', 'image-focus'], true))
                            <aside class="visual-stat-chart" aria-label="Visualisasi data persentase dari materi">
                                <div class="visual-stat-chart-header">
                                    <div>
                                        <span class="slide-kicker">Visual Data</span>
                                        <h3 class="visual-stat-chart-title">Perbandingan persentase dari materi</h3>
                                    </div>
                                    <p class="visual-stat-chart-note">Nilai diambil langsung dari teks slide.</p>
                                </div>
                                <div class="visual-stat-list">
                                    @foreach($visualStats as $stat)
                                        <div class="visual-stat-row" aria-label="{{ $stat['label'] }} {{ rtrim(rtrim(number_format($stat['value'], 2, '.', ''), '0'), '.') }} persen">
                                            <span class="visual-stat-label">{{ $stat['label'] }}</span>
                                            <span class="visual-stat-track" aria-hidden="true">
                                                <span class="visual-stat-fill" style="--visual-stat-value: {{ $stat['value'] }}%;"></span>
                                            </span>
                                            <span class="visual-stat-value">{{ rtrim(rtrim(number_format($stat['value'], 2, '.', ''), '0'), '.') }}%</span>
                                        </div>
                                    @endforeach
                                </div>
                            </aside>
                        @endif

                        {{-- "Show more" button uses the selected audience density. --}}
                        @if(count($presentation['blocks']) > $initialVisibleBlocks && !in_array($presentation['layout'], ['concept', 'summary', 'cover', 'section-divider', 'closing']))
                            <button class="presentation-button mt-5" type="button" @click="visibleBlocks += {{ $initialVisibleBlocks }}" x-show="visibleBlocks < {{ count($presentation['blocks']) }}">
                                Lihat bagian berikutnya <span class="text-indigo-300" x-text="'(' + Math.min(visibleBlocks, {{ count($presentation['blocks']) }}) + '/{{ count($presentation['blocks']) }})'"></span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Takeaway / Summary --}}
                @if($slide->summary)
                    <aside class="slide-takeaway">
                        <span class="slide-kicker">Intisari</span>
                        <p>{{ $slide->summary }}</p>
                    </aside>
                @endif

                {{-- Original text toggle --}}
                @if($slide->content)
                    <details class="slide-source">
                        <summary>Baca teks asli</summary>
                        <div class="whitespace-pre-wrap mt-4">{{ $slide->content }}</div>
                    </details>
                @endif
            </article>
        @empty
            <div class="py-20 text-center">
                <h2 class="text-2xl font-bold mb-3">Belum ada slide</h2>
                <p class="text-slate-400">Materi presentasi ini belum tersedia.</p>
                <a class="presentation-button mt-6 inline-flex" href="{{ $returnUrl }}">Kembali</a>
            </div>
        @endforelse
    </div>

    {{-- Navigation Footer --}}
    @if($slides->isNotEmpty())
        <footer class="presentation-navigation border-t border-slate-800 px-4 py-4 sm:px-8 flex items-center justify-between gap-3">
            <button type="button" class="presentation-button" @click="goToSlide(currentIndex - 1)" :disabled="currentIndex === 0"><span aria-hidden="true">←</span> <span>Sebelumnya</span></button>
            <div class="text-center min-w-0">
                {{-- Progress Dots --}}
                @if($totalSlides <= 12)
                    <div class="progress-dots" aria-label="Navigasi slide">
                        @foreach($slides as $slide)
                            <button type="button" class="progress-dot" :class="{ active: currentIndex === {{ $loop->index }} }" @click="goToSlide({{ $loop->index }})" aria-label="Slide {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                @endif
                <label for="slide-position" class="sr-only">Pilih slide</label>
                <select id="slide-position" class="slide-select" :value="currentIndex" @change="goToSlide(Number($event.target.value))">
                    @foreach($slides as $slide)
                        <option value="{{ $loop->index }}">Slide {{ $loop->iteration }} dari {{ $totalSlides }}</option>
                    @endforeach
                </select>
                <p class="hidden sm:block text-xs text-slate-400 mt-1">Gunakan tombol ← →</p>
            </div>
            <button type="button" class="presentation-button presentation-primary" @click="goToSlide(currentIndex + 1)" x-show="currentIndex < total - 1">Selanjutnya <span aria-hidden="true">→</span></button>
            <a href="{{ $returnUrl }}" class="presentation-button presentation-primary" x-show="currentIndex === total - 1" x-cloak>{{ $preview ? 'Tutup Preview' : 'Kembali ke Kursus' }} <span aria-hidden="true">→</span></a>
        </footer>
    @endif
    <noscript><style>.slide-presentation .presentation-slide,.slide-presentation .slide-block{display:block!important}.presentation-navigation{display:none!important}</style><p class="p-4">JavaScript nonaktif. Semua slide ditampilkan berurutan.</p></noscript>
</div>
@include('student.slidebooks.presentation-script')
@endsection