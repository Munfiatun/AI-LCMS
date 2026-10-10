@extends('layouts.app')

@php
    $title = 'Dashboard Guru';
    $breadcrumb = 'Dashboard Guru';
@endphp

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-purple-950/40 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    Ruang Kerja Guru
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Halo, {{ $user->name }}</h1>
                <p class="text-sm text-slate-400 mt-2 leading-relaxed">Bangun pembelajaran dari materi hingga evaluasi dalam satu alur. Sistem akan menunjukkan langkah berikutnya tanpa mengharuskan Anda memahami istilah teknis di balik proses AI.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('instructor.courses.create') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-900/20">+ Buat Kursus</a>
                <a href="{{ route('instructor.quizzes.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors">Kelola Quiz</a>
            </div>
        </div>
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
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
            <div class="p-4 sm:p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $card['label'] }}</span>
                <div class="mt-3 flex items-baseline justify-between gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ $card['value'] }}</span>
                    <span class="text-[11px] {{ $card['tone'] }}">{{ $card['unit'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow space-y-5">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-3">
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-400">Learning Creation Journey</div>
                <h2 class="text-lg font-extrabold text-white mt-1">Alur Pembuatan Pembelajaran</h2>
                <p class="text-sm text-slate-400 mt-1">Ikuti alur ini dari kiri ke kanan. Setiap kursus di bawah akan menunjukkan posisi dan langkah berikutnya secara otomatis.</p>
            </div>
            <span class="text-xs text-slate-500">Materi → Slidebook → Quiz → Publish → Siswa → Analytics</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-7 gap-3">
            @foreach($journey as $step)
                <div class="relative p-4 rounded-xl bg-slate-950/60 border border-slate-800 min-h-[132px]">
                    <div class="w-7 h-7 rounded-lg bg-indigo-500/15 text-indigo-300 border border-indigo-500/20 flex items-center justify-center text-xs font-extrabold">{{ $step['number'] }}</div>
                    <h3 class="text-sm font-bold text-white mt-3">{{ $step['label'] }}</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $step['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-white">Kesiapan Kursus</h2>
                <p class="text-sm text-slate-400 mt-1">Lihat bagian yang sudah siap, hal yang masih perlu diperiksa, dan tindakan berikutnya untuk tiap kursus.</p>
            </div>
            <a href="{{ route('instructor.courses.index') }}" class="text-xs font-semibold text-indigo-300 hover:text-indigo-200">Lihat semua kursus →</a>
        </div>

        @forelse($courseReadiness as $item)
            @php
                $course = $item['course'];
                $counts = $item['counts'];
                $statusLabel = $course->status === 'published' ? 'Terbit' : ($course->status === 'archived' ? 'Diarsipkan' : 'Draft');
                $statusClass = $course->status === 'published'
                    ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'
                    : ($course->status === 'archived' ? 'bg-slate-500/15 text-slate-300 border-slate-500/30' : 'bg-amber-500/15 text-amber-300 border-amber-500/30');
            @endphp
            <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow overflow-hidden">
                <div class="p-5 flex flex-col xl:flex-row xl:items-start justify-between gap-5">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-bold text-white truncate">{{ $course->title }}</h3>
                            <span class="px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase {{ $statusClass }}">{{ $statusLabel }}</span>
                            <span class="text-[11px] text-slate-500">{{ $item['completed_steps'] }}/{{ $item['total_steps'] }} tahap</span>
                        </div>

                        <div class="mt-3 h-2 rounded-full bg-slate-950 overflow-hidden border border-slate-800">
                            <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $item['progress_percent'] }}%"></div>
                        </div>

                        <div class="mt-4 grid grid-cols-4 gap-2 max-w-2xl">
                            <div class="p-2.5 rounded-lg bg-slate-950/70 border border-slate-800 text-center">
                                <div class="text-base font-extrabold text-white">{{ $counts['materials'] }}</div>
                                <div class="text-[10px] text-slate-500">Materi</div>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-950/70 border border-slate-800 text-center">
                                <div class="text-base font-extrabold text-white">{{ $counts['published_slidebooks'] }}</div>
                                <div class="text-[10px] text-slate-500">Slidebook Terbit</div>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-950/70 border border-slate-800 text-center">
                                <div class="text-base font-extrabold text-white">{{ $counts['published_quizzes'] }}</div>
                                <div class="text-[10px] text-slate-500">Quiz Terbit</div>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-950/70 border border-slate-800 text-center">
                                <div class="text-base font-extrabold text-white">{{ $counts['students'] }}</div>
                                <div class="text-[10px] text-slate-500">Siswa</div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($item['steps'] as $step)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[10px] font-semibold {{ $step['complete'] ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-300' : 'bg-slate-950 border-slate-800 text-slate-500' }}">
                                    <span>{{ $step['complete'] ? '✓' : '○' }}</span>{{ $step['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="w-full xl:w-[340px] p-4 rounded-xl bg-slate-950/70 border border-slate-800">
                        <div class="text-[10px] uppercase tracking-wider font-bold text-indigo-400">Langkah Berikutnya</div>
                        <h4 class="text-sm font-bold text-white mt-1">{{ $item['next_action']['label'] }}</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $item['next_action']['description'] }}</p>
                        <a href="{{ $item['next_action']['url'] }}" class="mt-3 inline-flex items-center justify-center w-full px-3 py-2 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors">Lanjutkan →</a>
                    </div>
                </div>

                @if($item['warnings'] !== [])
                    <div class="px-5 py-3 border-t border-slate-800 bg-amber-950/10 flex flex-wrap items-center gap-x-5 gap-y-1">
                        <span class="text-[10px] uppercase tracking-wider font-bold text-amber-400">Perlu perhatian</span>
                        @foreach($item['warnings'] as $warning)
                            <span class="text-xs text-amber-200/80">• {{ $warning }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="p-8 text-center rounded-2xl bg-slate-900/70 border border-dashed border-slate-700">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center mx-auto text-indigo-300 text-xl">1</div>
                <h3 class="text-base font-bold text-white mt-4">Mulai dari kursus pertama</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-xl mx-auto">Setelah kursus dibuat, dashboard ini akan memandu Anda menambahkan materi, membuat Slidebook dan Quiz, lalu mempublikasikannya ke siswa.</p>
                <a href="{{ route('instructor.courses.create') }}" class="mt-4 inline-flex px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500">Buat Kursus Pertama</a>
            </div>
        @endforelse
    </div>

    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h3 class="text-sm font-bold text-white">Prinsip Kendali Guru</h3>
            <p class="text-xs text-slate-500 mt-1">AI membantu menyusun Slidebook dan Quiz, tetapi konten tetap harus diperiksa dan disetujui guru sebelum diterbitkan kepada siswa.</p>
        </div>
        <a href="{{ route('instructor.question-banks.index') }}" class="text-xs font-semibold text-cyan-300 hover:text-cyan-200">Kelola Bank Soal →</a>
    </div>
</div>
@endsection
