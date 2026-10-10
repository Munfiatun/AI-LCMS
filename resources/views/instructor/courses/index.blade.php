@extends('layouts.app')

@php
    $title = 'Kursus Saya';
    $breadcrumb = 'Kursus Saya';
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Koleksi Kursus Saya</h1>
            <p class="mt-1 text-sm text-slate-400">Buat silabus pembelajaran, unggah modul materi, dan pantau publikasi materi.</p>
        </div>
        <x-ui.button :href="route('instructor.courses.create')" size="lg">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Buat Kursus Baru
        </x-ui.button>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse($courses as $course)
            <x-ui.panel padding="none" class="flex flex-col justify-between overflow-hidden transition-colors hover:border-slate-700">
                <div class="space-y-3 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <x-ui.badge tone="indigo" :label="$course->category?->name ?? 'Tanpa Kategori'" class="normal-case" />
                        <x-ui.badge :status="$course->status" />
                    </div>

                    <h3 class="line-clamp-1 text-base font-bold text-white transition-colors hover:text-indigo-400">
                        <a href="{{ route('instructor.courses.edit', $course) }}">{{ $course->title }}</a>
                    </h3>

                    <p class="line-clamp-2 text-xs leading-relaxed text-slate-400">
                        {{ $course->description ?? 'Belum ada deskripsi kursus.' }}
                    </p>

                    <div class="flex items-center gap-4 border-t border-slate-800/80 pt-2 text-xs text-slate-500">
                        <span class="flex items-center gap-1">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            {{ $course->sections_count }} Bab
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            {{ $course->materials_count }} Materi
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-slate-800/80 bg-slate-950/60 px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('instructor.courses.edit', $course) }}" class="ui-text-link flex items-center gap-1 text-xs">
                            <span>Kelola Silabus</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                        <span class="text-slate-700" aria-hidden="true">|</span>
                        <a href="{{ route('instructor.courses.analytics', $course) }}" class="flex items-center gap-1 text-xs font-semibold text-emerald-400 transition-colors hover:text-emerald-300">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            <span>Analitik</span>
                        </a>
                    </div>

                    <form action="{{ route('instructor.courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kursus ini?')">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="danger" size="sm">Hapus</x-ui.button>
                    </form>
                </div>
            </x-ui.panel>
        @empty
            <x-ui.panel class="col-span-full text-center" padding="lg">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-800 text-slate-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                </div>
                <h3 class="text-base font-bold text-white">Belum Ada Kursus</h3>
                <p class="mx-auto mt-1 max-w-sm text-xs text-slate-400">Mulai susun kurikulum Anda sekarang untuk menyediakan pembelajaran bermutu bagi para siswa.</p>
                <x-ui.button :href="route('instructor.courses.create')" class="mt-4">+ Buat Kursus Pertama</x-ui.button>
            </x-ui.panel>
        @endforelse
    </div>

    @if($courses->hasPages())
        <div class="pt-4">
            {{ $courses->links() }}
        </div>
    @endif
</div>
@endsection
