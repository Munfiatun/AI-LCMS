@extends('layouts.app')

@php
    $title = 'Dashboard Guru';
    $breadcrumb = 'Dashboard Guru';
@endphp

@section('content')
<div class="space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-purple-950/40 to-slate-900 p-6 shadow-xl">
        <div class="relative z-10 flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
            <div class="max-w-3xl">
                <x-ui.badge tone="indigo" label="Ruang Kerja Guru" class="mb-2 normal-case" />
                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Halo, {{ $user->name }}</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-400">Bangun pembelajaran dari materi hingga evaluasi dalam satu alur. Sistem akan menunjukkan langkah berikutnya tanpa mengharuskan Anda memahami istilah teknis di balik proses AI.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.button :href="route('instructor.courses.create')">+ Buat Kursus</x-ui.button>
                <x-ui.button :href="route('instructor.learning-objectives.index')" variant="secondary">Learning Objectives</x-ui.button>
                <x-ui.button :href="route('instructor.learning-concepts.index')" variant="secondary">Domain Model</x-ui.button>
                <x-ui.button :href="route('instructor.diagnostics.index')" variant="secondary">Diagnostic Assessment</x-ui.button>
                <x-ui.button :href="route('instructor.quizzes.index')" variant="secondary">Kelola Quiz</x-ui.button>
            </div>
        </div>
        <div class="pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 rounded-full bg-purple-500/10 blur-3xl"></div>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        @php
            $cards = [
                ['label' => 'Kursus Saya', 'value' => $stats['total_courses'], 'unit' => 'Kursus', 'tone' => 'text-indigo-400'],
                ['label' => 'Materi Ajar', 'value' => $stats['total_materials'], 'unit' => 'Materi', 'tone' => 'text-blue-400'],
                ['label' => 'Slidebook', 'value' => $stats['total_slidebooks'], 'unit' => 'Versi', 'tone' => 'text-purple-400'],
                ['label' => 'Bank Soal', 'value' => $stats['total_question_banks'], 'unit' => 'Koleksi', 'tone' => 'text-cyan-400'],
                ['label' => 'Quiz', 'value' => $stats['total_quizzes'], 'unit' => 'Quiz', 'tone' => 'text-emerald-400'],
                ['label' => 'Siswa', 'value' => $stats['total_students'], 'unit' => 'Terdaftar', 'tone' => 'text-amber-400'],
            ];
        @endphp
        @foreach($cards as $card)
            <x-ui.panel padding="sm" class="shadow">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $card['label'] }}</span>
                <div class="mt-3 flex items-baseline justify-between gap-2">
                    <span class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $card['value'] }}</span>
                    <span class="text-[11px] {{ $card['tone'] }}">{{ $card['unit'] }}</span>
                </div>
            </x-ui.panel>
        @endforeach
    </div>

    <x-ui.panel class="space-y-5 shadow">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-end">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-400">Learning Creation Journey</div>
                <h2 class="mt-1 text-lg font-extrabold text-white">Alur Pembuatan Pembelajaran</h2>
                <p class="mt-1 text-sm text-slate-400">Ikuti alur ini dari kiri ke kanan. Setiap kursus di bawah akan menunjukkan posisi dan langkah berikutnya secara otomatis.</p>
            </div>
            <span class="text-xs text-slate-500">Materi → Slidebook → Quiz → Terbitkan → Siswa → Analytics</span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-7">
            @foreach($journey as $step)
                <x-ui.panel variant="muted" padding="sm" class="relative min-h-[132px]">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg border border-indigo-500/20 bg-indigo-500/15 text-xs font-extrabold text-indigo-300">{{ $step['number'] }}</div>
                    <h3 class="mt-3 text-sm font-bold text-white">{{ $step['label'] }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $step['description'] }}</p>
                </x-ui.panel>
            @endforeach
        </div>
    </x-ui.panel>

    <div class="space-y-4">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-lg font-extrabold text-white">Kesiapan Kursus</h2>
                <p class="mt-1 text-sm text-slate-400">Lihat bagian yang sudah siap, hal yang masih perlu diperiksa, dan tindakan berikutnya untuk tiap kursus.</p>
            </div>
            <a href="{{ route('instructor.courses.index') }}" class="ui-text-link text-xs">Lihat semua kursus →</a>
        </div>

        @forelse($courseReadiness as $item)
            @php
                $course = $item['course'];
                $counts = $item['counts'];
            @endphp
            <x-ui.panel padding="none" class="overflow-hidden shadow">
                <div class="flex flex-col justify-between gap-5 p-5 xl:flex-row xl:items-start">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-base font-bold text-white">{{ $course->title }}</h3>
                            <x-ui.badge :status="$course->status" />
                            <span class="text-[11px] text-slate-500">{{ $item['completed_steps'] }}/{{ $item['total_steps'] }} tahap</span>
                        </div>

                        <div class="mt-3 h-2 overflow-hidden rounded-full border border-slate-800 bg-slate-950" role="progressbar" aria-label="Kesiapan kursus {{ $course->title }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item['progress_percent'] }}">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $item['progress_percent'] }}%"></div>
                        </div>

                        <div class="mt-4 grid max-w-2xl grid-cols-4 gap-2">
                            @foreach([
                                ['value' => $counts['materials'], 'label' => 'Materi'],
                                ['value' => $counts['published_slidebooks'], 'label' => 'Slidebook Terbit'],
                                ['value' => $counts['published_quizzes'], 'label' => 'Quiz Terbit'],
                                ['value' => $counts['students'], 'label' => 'Siswa'],
                            ] as $metric)
                                <div class="rounded-lg border border-slate-800 bg-slate-950/70 p-2.5 text-center">
                                    <div class="text-base font-extrabold text-white">{{ $metric['value'] }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $metric['label'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($item['steps'] as $step)
                                <x-ui.badge :tone="$step['complete'] ? 'success' : 'neutral'" size="sm" class="normal-case">
                                    <span aria-hidden="true">{{ $step['complete'] ? '✓' : '○' }}</span>&nbsp;{{ $step['label'] }}
                                </x-ui.badge>
                            @endforeach
                        </div>
                    </div>

                    <x-ui.panel variant="muted" padding="sm" class="w-full xl:w-[340px]">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-400">Langkah Berikutnya</div>
                        <h4 class="mt-1 text-sm font-bold text-white">{{ $item['next_action']['label'] }}</h4>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $item['next_action']['description'] }}</p>
                        <x-ui.button :href="$item['next_action']['url']" block size="sm" class="mt-3">Lanjutkan →</x-ui.button>
                        <div class="mt-3 flex flex-wrap justify-center gap-x-3 gap-y-2">
                            <a href="{{ route('instructor.learning-concepts.index', ['course_id' => $course->id]) }}" class="text-xs font-semibold text-cyan-300 hover:text-cyan-200">Domain Model →</a>
                            <a href="{{ route('instructor.diagnostics.index', ['course_id' => $course->id]) }}" class="text-xs font-semibold text-indigo-300 hover:text-indigo-200">Diagnostic →</a>
                            <a href="{{ route('instructor.courses.research.show', $course) }}" class="text-xs font-semibold text-indigo-300 hover:text-indigo-200">Research Setup →</a>
                        </div>
                    </x-ui.panel>
                </div>

                @if($item['warnings'] !== [])
                    <div class="border-t border-slate-800 p-4">
                        <x-ui.alert tone="warning" title="Perlu perhatian" class="!rounded-xl !p-3">
                            <ul class="space-y-1">
                                @foreach($item['warnings'] as $warning)
                                    <li>• {{ $warning }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert>
                    </div>
                @endif
            </x-ui.panel>
        @empty
            <x-ui.panel class="text-center" padding="lg">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-indigo-500/20 bg-indigo-500/10 text-xl text-indigo-300">1</div>
                <h3 class="mt-4 text-base font-bold text-white">Mulai dari kursus pertama</h3>
                <p class="mx-auto mt-1 max-w-xl text-sm text-slate-500">Setelah kursus dibuat, dashboard ini akan memandu Anda menambahkan materi, membuat Slidebook dan Quiz, lalu mempublikasikannya ke siswa.</p>
                <x-ui.button :href="route('instructor.courses.create')" class="mt-4">Buat Kursus Pertama</x-ui.button>
            </x-ui.panel>
        @endforelse
    </div>

    <x-ui.panel class="flex flex-col justify-between gap-3 md:flex-row md:items-center" padding="sm">
        <div>
            <h3 class="text-sm font-bold text-white">Prinsip Kendali Guru</h3>
            <p class="mt-1 text-xs text-slate-500">AI membantu menyusun Slidebook dan Quiz, tetapi konten tetap harus diperiksa dan disetujui guru sebelum diterbitkan kepada siswa.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('instructor.learning-objectives.index') }}" class="text-xs font-semibold text-indigo-300 hover:text-indigo-200">Learning Objectives & Alignment →</a>
            <a href="{{ route('instructor.learning-concepts.index') }}" class="text-xs font-semibold text-cyan-300 hover:text-cyan-200">Domain Model →</a>
            <a href="{{ route('instructor.diagnostics.index') }}" class="text-xs font-semibold text-indigo-300 hover:text-indigo-200">Diagnostic Assessment →</a>
            <a href="{{ route('instructor.question-banks.index') }}" class="text-xs font-semibold text-cyan-300 hover:text-cyan-200">Kelola Bank Soal →</a>
        </div>
    </x-ui.panel>
</div>
@endsection
