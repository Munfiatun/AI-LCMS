@extends('layouts.app')

@php
    $title = 'Kursus & Materi';
    $breadcrumb = 'Kursus & Materi';
@endphp

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/75 p-6 sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-indigo-300">Ruang Kerja Konten</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Kursus & Materi</h1>
                <p class="mt-2 text-base leading-7 text-slate-400">Pilih kursus yang ingin dikerjakan. Dari dalam kursus Anda dapat mengatur bab, materi, Slidebook, publikasi, dan progres pembelajaran.</p>
            </div>

            <x-ui.button :href="route('instructor.courses.create')" size="lg">+ Buat Kursus</x-ui.button>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($courses as $course)
            <article class="flex min-h-[240px] flex-col rounded-2xl border border-slate-800 bg-slate-900/70 transition hover:border-indigo-500/25">
                <div class="flex-1 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <x-ui.badge tone="indigo" :label="$course->category?->name ?? 'Tanpa Kategori'" class="normal-case" />
                        <x-ui.badge :status="$course->status" />
                    </div>

                    <h2 class="mt-4 text-lg font-extrabold leading-snug text-white">{{ $course->title }}</h2>
                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-500">{{ $course->description ?? 'Belum ada deskripsi kursus.' }}</p>

                    <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-400">
                        <span><strong class="text-white">{{ $course->sections_count }}</strong> bab</span>
                        <span><strong class="text-white">{{ $course->materials_count }}</strong> materi</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 border-t border-slate-800 p-4">
                    <a href="{{ route('instructor.courses.edit', $course) }}" class="flex-1 rounded-xl bg-indigo-600 px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-indigo-500">Buka & Kelola Kursus</a>

                    <details class="relative">
                        <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-xl border border-slate-700 text-slate-400 hover:bg-slate-800 hover:text-white" aria-label="Opsi lainnya">•••</summary>
                        <div class="absolute bottom-12 right-0 z-20 w-48 rounded-xl border border-slate-700 bg-slate-900 p-2 shadow-2xl">
                            <a href="{{ route('instructor.courses.analytics', $course) }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-800 hover:text-white">Lihat analitik</a>
                            @if(Route::has('instructor.learning-concepts.index'))
                                <a href="{{ route('instructor.learning-concepts.index', ['course_id' => $course->id]) }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-800 hover:text-white">Pengaturan adaptif</a>
                            @endif
                            <form action="{{ route('instructor.courses.destroy', $course) }}" method="POST" class="mt-1 border-t border-slate-800 pt-1" onsubmit="return confirm('Yakin ingin menghapus kursus ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-rose-300 hover:bg-rose-500/10">Hapus kursus</button>
                            </form>
                        </div>
                    </details>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                <h2 class="text-lg font-bold text-white">Belum ada kursus</h2>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">Mulai dari satu kursus. Setelah itu tambahkan bab dan materi secara bertahap.</p>
                <x-ui.button :href="route('instructor.courses.create')" class="mt-5">+ Buat Kursus Pertama</x-ui.button>
            </div>
        @endforelse
    </section>

    @if($courses->hasPages())
        <div class="pt-2">{{ $courses->links() }}</div>
    @endif
</div>
@endsection
