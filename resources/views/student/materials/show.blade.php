@extends('layouts.app')

@php
    $title = $material->title;
    $breadcrumb = 'Materi';
    $previewMode = !empty($isPreview);
@endphp

@section('content')
<div class="space-y-6">
    @if($previewMode)
        <div class="p-4 rounded-2xl border border-indigo-500/30 bg-indigo-950/30 text-indigo-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <p class="text-sm font-bold">Instructor Preview · Read-only</p>
                <p class="text-xs text-indigo-200/80">Tampilan ini mensimulasikan pengalaman siswa tanpa mengubah progress, enrollment, atau status publikasi.</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30">Preview Mode</span>
        </div>
    @endif

    <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
        @if($previewMode)
            <a href="{{ route('instructor.materials.edit', $material) }}" class="hover:text-white transition-colors">&larr; Kembali ke Kelola Materi</a>
        @else
            <a href="{{ route('student.courses.progress', $course) }}" class="hover:text-white transition-colors">&larr; Kembali ke Kursus</a>
        @endif
        <span class="text-slate-600">&bull;</span>
        <span class="text-slate-300">{{ $course->title }}</span>
    </div>

    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/30 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ $material->title }}</h1>
                @if($material->description)
                    <p class="text-sm text-slate-400 mt-2">{{ $material->description }}</p>
                @endif
                <div class="flex items-center gap-4 mt-4">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-300 bg-slate-800/80 px-2.5 py-1 rounded-full border border-slate-700">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Estimasi {{ $material->duration_minutes }} menit
                    </span>
                </div>
            </div>

            @if($previewMode)
                <div class="px-5 py-2.5 rounded-xl text-sm font-bold text-indigo-300 bg-indigo-500/10 border border-indigo-500/20">Read-only Preview</div>
            @elseif(!$isCompleted)
                <form action="{{ route('student.materials.complete', $material) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Tandai Selesai
                    </button>
                </form>
            @else
                <div class="px-5 py-2.5 rounded-xl text-sm font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Materi Telah Selesai
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if($previewMode && $material->slidebook)
                @php
                    $previewSlidebook = $material->slidebook;
                    $previewVersionKey = optional($previewSlidebook->updated_at)->timestamp ?? $previewSlidebook->version;
                @endphp
                <div class="rounded-2xl border border-indigo-500/30 bg-slate-950/70 shadow-2xl overflow-hidden">
                    <div class="px-4 py-3 border-b border-indigo-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-indigo-300">Instructor Preview · Slidebook v{{ $previewSlidebook->version }}</p>
                            <p class="text-[11px] text-slate-500">Renderer siswa ditampilkan dalam sandbox read-only.</p>
                        </div>
                        <a href="{{ route('instructor.slidebooks.preview', $previewSlidebook) }}?v={{ $previewVersionKey }}" target="_blank" rel="noopener"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold text-indigo-200 bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500/20 transition-colors">
                            Buka Preview Penuh
                        </a>
                    </div>
                    <div class="w-full h-[650px] bg-black">
                        <iframe
                            src="{{ route('instructor.slidebooks.preview', $previewSlidebook) }}?embed=1&amp;v={{ $previewVersionKey }}"
                            class="w-full h-full border-none"
                            title="Preview siswa Slidebook versi {{ $previewSlidebook->version }}"
                            sandbox="allow-scripts allow-same-origin"
                            allowfullscreen
                            loading="eager"></iframe>
                    </div>
                </div>
            @elseif($material->publishedSlidebook && $material->publishedSlidebook->isPublished())
                <div class="w-full h-[650px] rounded-2xl bg-slate-950 border border-slate-800 shadow-xl overflow-hidden relative">
                    <iframe src="{{ route('student.slidebooks.show', $material->publishedSlidebook) }}" class="w-full h-full border-none" title="Slidebook {{ $material->publishedSlidebook->title }}"></iframe>
                </div>
            @endif

            @if(!empty($material->content))
                <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-lg prose prose-invert max-w-none">
                    {!! $material->content !!}
                </div>
            @endif

            @if($material->documents && $material->documents->count() > 0)
                <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-lg">
                    <h3 class="text-lg font-bold text-white mb-4">Dokumen Pendukung</h3>
                    <div class="space-y-3">
                        @foreach($material->documents as $doc)
                            <div class="p-4 rounded-xl bg-slate-950/50 border border-slate-800 flex items-center justify-between transition-colors hover:bg-slate-900">
                                <div class="flex items-center gap-3 truncate pr-4">
                                    <span class="p-2 rounded bg-indigo-500/10 text-indigo-400 flex-shrink-0">📄</span>
                                    <span class="text-sm text-slate-300 font-medium truncate">{{ $doc->original_name }}</span>
                                </div>
                                <a href="{{ route('documents.download', $doc) }}" target="_blank" class="px-3 py-1.5 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 transition-colors flex-shrink-0">Unduh Dokumen</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-lg">
                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-4">Navigasi Kursus</h3>
                <div class="space-y-2">
                    @foreach($course->sections as $section)
                        <div class="mb-4">
                            <h4 class="text-xs font-bold text-slate-400 mb-2">{{ $section->title }}</h4>
                            <div class="space-y-1">
                                @foreach($section->materials as $mat)
                                    @php
                                        $isCurrent = $mat->id === $material->id;
                                        $completed = in_array($mat->id, $completedMaterialIds);
                                    @endphp
                                    @if($previewMode)
                                        <div class="block px-3 py-2 rounded-lg text-sm border {{ $isCurrent ? 'bg-indigo-500/10 border-indigo-500/30 text-indigo-300' : 'border-transparent text-slate-500' }}">
                                            <div class="flex items-center gap-2">
                                                <div class="w-4 h-4 rounded-full border-2 {{ $completed ? 'border-emerald-500 bg-emerald-500/20' : 'border-slate-600' }} flex-shrink-0"></div>
                                                <span class="truncate">{{ $mat->title }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <a href="{{ route('student.materials.show', ['course' => $course, 'material' => $mat]) }}" class="block px-3 py-2 rounded-lg text-sm transition-colors border cursor-pointer {{ $isCurrent ? 'bg-indigo-500/10 border-indigo-500/30 text-indigo-300' : 'border-transparent text-slate-400 hover:bg-slate-800/50 hover:text-slate-300' }}">
                                            <div class="flex items-center gap-2 pointer-events-none">
                                                @if($completed)
                                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                @else
                                                    <div class="w-4 h-4 rounded-full border-2 border-slate-600 flex-shrink-0"></div>
                                                @endif
                                                <span class="truncate">{{ $mat->title }}</span>
                                            </div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if($course->quizzes->where('status', 'published')->count() > 0)
                        <div class="mt-6 pt-4 border-t border-slate-800">
                            <h4 class="text-xs font-bold text-slate-400 mb-2">Kuis Evaluasi</h4>
                            <div class="space-y-1">
                                @foreach($course->quizzes->where('status', 'published') as $quiz)
                                    @if($previewMode)
                                        <div class="block px-3 py-2 rounded-lg text-sm border border-transparent text-slate-500">
                                            <div class="flex items-center gap-2"><span>📝</span><span class="truncate">{{ $quiz->title }}</span><span class="ml-auto text-[9px] uppercase">read-only</span></div>
                                        </div>
                                    @else
                                        <a href="{{ route('student.quizzes.show', $quiz) }}" class="block px-3 py-2 rounded-lg text-sm border border-transparent text-slate-400 hover:bg-slate-800/50 hover:text-slate-300 transition-colors cursor-pointer">
                                            <div class="flex items-center gap-2 pointer-events-none"><span>📝</span><span class="truncate">{{ $quiz->title }}</span></div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
