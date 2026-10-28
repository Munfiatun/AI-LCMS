@extends('layouts.app')

@php
    $title = 'Beranda Guru';
    $breadcrumb = 'Beranda Guru';
    $primaryItem = collect($courseReadiness)->first();
@endphp

@section('content')
<div class="space-y-6">
    <section class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-sm font-semibold text-indigo-200">Ruang Kerja Guru</div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Halo, {{ $user->name }}</h1>
                <p class="mt-3 max-w-2xl text-base leading-7 text-slate-400">Fokus pada satu langkah berikutnya. Fitur lanjutan tetap tersedia, tetapi tidak perlu dibuka jika Anda hanya ingin menyiapkan materi dan evaluasi.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                @if($primaryItem)
                    <x-ui.button :href="$primaryItem['next_action']['url']" size="lg">Lanjutkan Pekerjaan</x-ui.button>
                    <x-ui.button :href="route('instructor.courses.index')" variant="secondary" size="lg">Lihat Kursus</x-ui.button>
                @else
                    <x-ui.button :href="route('instructor.courses.create')" size="lg">+ Buat Kursus Pertama</x-ui.button>
                @endif
            </div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([
            ['label' => 'Kursus', 'value' => $stats['total_courses'], 'hint' => 'yang Anda kelola'],
            ['label' => 'Materi', 'value' => $stats['total_materials'], 'hint' => 'materi ajar'],
            ['label' => 'Kuis', 'value' => $stats['total_quizzes'], 'hint' => 'assessment'],
            ['label' => 'Siswa', 'value' => $stats['total_students'], 'hint' => 'terdaftar'],
        ] as $metric)
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
                <p class="text-sm font-semibold text-slate-400">{{ $metric['label'] }}</p>
                <div class="mt-2 text-3xl font-extrabold tracking-tight text-white">{{ $metric['value'] }}</div>
                <p class="mt-1 text-xs text-slate-500">{{ $metric['hint'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-indigo-300">Alur Kerja Sederhana</p>
                <h2 class="mt-1 text-xl font-extrabold text-white">Dari kursus sampai evaluasi</h2>
                <p class="mt-1 text-sm leading-6 text-slate-400">Tidak perlu membuka semua menu. Ikuti urutan ini saat menyiapkan pembelajaran.</p>
            </div>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([
                ['1', 'Kursus', 'Buat struktur kursus dan bab.'],
                ['2', 'Materi', 'Unggah materi dan siapkan Slidebook.'],
                ['3', 'Bank Soal', 'Simpan atau ekstrak soal dari dokumen.'],
                ['4', 'Kuis', 'Susun evaluasi dan review sebelum terbit.'],
                ['5', 'Pantau', 'Lihat progres dan hasil belajar siswa.'],
            ] as $step)
                <div class="rounded-2xl border border-slate-800 bg-slate-950/45 p-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-600 text-sm font-extrabold text-white">{{ $step[0] }}</div>
                    <h3 class="mt-3 text-base font-bold text-white">{{ $step[1] }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $step[2] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="space-y-4">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold text-white">Kursus yang Perlu Dilanjutkan</h2>
                <p class="mt-1 text-sm text-slate-400">Setiap kartu hanya menampilkan satu tindakan utama agar Anda tidak perlu menebak langkah berikutnya.</p>
            </div>
            <a href="{{ route('instructor.courses.index') }}" class="text-sm font-semibold text-indigo-300 hover:text-indigo-200">Semua kursus →</a>
        </div>

        @forelse(collect($courseReadiness)->take(4) as $item)
            @php
                $course = $item['course'];
                $counts = $item['counts'];
            @endphp
            <article class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6">
                <div class="grid gap-5 xl:grid-cols-[1fr_320px] xl:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-extrabold text-white">{{ $course->title }}</h3>
                            <x-ui.badge :status="$course->status" />
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-950" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item['progress_percent'] }}">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $item['progress_percent'] }}%"></div>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">{{ $item['completed_steps'] }}/{{ $item['total_steps'] }} tahap siap · {{ $item['progress_percent'] }}%</p>

                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-400">
                            <span><strong class="text-white">{{ $counts['materials'] }}</strong> materi</span>
                            <span><strong class="text-white">{{ $counts['published_quizzes'] }}</strong> kuis terbit</span>
                            <span><strong class="text-white">{{ $counts['students'] }}</strong> siswa</span>
                        </div>

                        @if($item['warnings'] !== [])
                            <details class="mt-4 rounded-xl border border-amber-500/15 bg-amber-500/5 px-4 py-3">
                                <summary class="cursor-pointer text-sm font-semibold text-amber-200">Ada {{ count($item['warnings']) }} hal yang perlu diperiksa</summary>
                                <ul class="mt-3 space-y-1 text-sm leading-6 text-amber-100/80">
                                    @foreach($item['warnings'] as $warning)
                                        <li>• {{ $warning }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/[0.06] p-5">
                        <p class="text-sm font-semibold text-indigo-300">Langkah berikutnya</p>
                        <h4 class="mt-2 text-base font-extrabold text-white">{{ $item['next_action']['label'] }}</h4>
                        <p class="mt-2 text-sm leading-6 text-slate-400">{{ $item['next_action']['description'] }}</p>
                        <x-ui.button :href="$item['next_action']['url']" block class="mt-4">Lanjutkan</x-ui.button>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                <h3 class="text-lg font-bold text-white">Belum ada kursus</h3>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">Mulai dari satu kursus. Setelah itu sistem akan memandu Anda ke materi, soal, kuis, dan evaluasi.</p>
                <x-ui.button :href="route('instructor.courses.create')" class="mt-5">+ Buat Kursus</x-ui.button>
            </div>
        @endforelse
    </section>

    <details class="rounded-3xl border border-slate-800 bg-slate-900/60 p-5 sm:p-6">
        <summary class="cursor-pointer list-none">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-white">Pengaturan Adaptif & Riset</h2>
                    <p class="mt-1 text-sm text-slate-500">Buka hanya saat Anda perlu mengatur objective, concept mapping, diagnostik, atau mastery.</p>
                </div>
                <span class="text-sm font-semibold text-indigo-300">Buka pengaturan ↓</span>
            </div>
        </summary>

        <div class="mt-5 grid gap-3 border-t border-slate-800 pt-5 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('instructor.learning-objectives.index') }}" class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4 transition hover:border-indigo-500/30 hover:bg-slate-900">
                <h3 class="font-bold text-white">Tujuan Pembelajaran</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">Atur Learning Objective dan alignment.</p>
            </a>
            <a href="{{ route('instructor.learning-concepts.index') }}" class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4 transition hover:border-indigo-500/30 hover:bg-slate-900">
                <h3 class="font-bold text-white">Konsep & Mapping</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">Hubungkan materi dan soal ke concept.</p>
            </a>
            <a href="{{ route('instructor.diagnostics.index') }}" class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4 transition hover:border-indigo-500/30 hover:bg-slate-900">
                <h3 class="font-bold text-white">Diagnostik Awal</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">Siapkan baseline pemahaman siswa.</p>
            </a>
            <a href="{{ route('instructor.mastery.index') }}" class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4 transition hover:border-indigo-500/30 hover:bg-slate-900">
                <h3 class="font-bold text-white">Mastery Siswa</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">Pantau evidence dan perkembangan concept.</p>
            </a>
        </div>
    </details>
</div>
@endsection
