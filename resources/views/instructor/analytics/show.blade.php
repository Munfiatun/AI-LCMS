@extends('layouts.app')

@php
    $title = 'Analitik Kursus: ' . $course->title;
    $breadcrumb = 'Analitik & Pelaporan';
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2 text-sm theme-body mb-2">
        <a href="{{ route('instructor.courses.index') }}" class="theme-heading-hover transition-colors">&larr; Kembali ke Daftar Kursus</a>
        <span class="theme-muted">&bull;</span>
        <span class="theme-body">{{ $course->title }}</span>
    </div>

    <div class="teacher-page-header p-6 border relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.22em] theme-accent-text">Instructor Analytics</p>
                <h1 class="teacher-page-title mt-2 text-2xl sm:text-3xl font-extrabold theme-heading tracking-tight">Laporan Analitik Kursus</h1>
                <p class="text-sm theme-body mt-2">Pantau progres siswa, partisipasi quiz, dan performa assessment untuk {{ $course->title }}.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-semibold {{ $course->status === 'published' ? 'theme-success-surface theme-success-text border theme-success-border' : 'theme-muted-surface theme-body' }}">
                Status: {{ ucfirst($course->status) }}
            </span>
        </div>
        <div class="absolute -right-10 -bottom-10 w-64 h-64 theme-accent-surface rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl theme-surface border theme-border shadow">
            <span class="text-sm font-bold uppercase tracking-wider theme-body">Total Pendaftar</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold theme-heading tracking-tight">{{ $totalStudents }}</span>
                <span class="text-sm theme-accent-text">Siswa</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl theme-surface border theme-border shadow">
            <span class="text-sm font-bold uppercase tracking-wider theme-body">Siswa Tuntas</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold theme-heading tracking-tight">{{ $completedStudents }}</span>
                <span class="text-sm theme-success-text">100% progres</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl theme-surface border theme-border shadow">
            <span class="text-sm font-bold uppercase tracking-wider theme-body">Rata-Rata Progres</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold theme-heading tracking-tight">{{ number_format($averageProgress, 1) }}%</span>
                <span class="text-sm theme-warning-text">Kursus</span>
            </div>
            <div class="w-full theme-muted-surface rounded-full h-1.5 mt-3">
                <div class="theme-accent-surface h-1.5 rounded-full" style="width: {{ max(0, min(100, $averageProgress)) }}%"></div>
            </div>
        </div>

        <div class="p-5 rounded-2xl theme-surface border {{ $atRiskStudents > 0 ? 'theme-danger-border' : 'theme-border' }} shadow">
            <span class="text-sm font-bold uppercase tracking-wider theme-body">Perlu Perhatian</span>
            <div class="mt-3 flex items-end justify-between gap-3">
                <span class="text-3xl font-extrabold {{ $atRiskStudents > 0 ? 'theme-danger-text' : 'theme-heading' }} tracking-tight">{{ $atRiskStudents }}</span>
                <span class="text-sm {{ $atRiskStudents > 0 ? 'theme-danger-text' : 'theme-muted' }}">Prioritas tinggi</span>
            </div>
        </div>
    </div>

    <div class="p-6 rounded-2xl theme-surface border theme-border shadow-xl">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] theme-accent-text">Intervention Overview</p>
                <h3 class="mt-2 text-lg font-bold theme-heading">Ringkasan Intervensi</h3>
                <p class="mt-1 text-sm theme-muted">Prioritas ditentukan dari progres, ketuntasan materi, dan hasil assessment. Ini alat bantu pemantauan, bukan diagnosis otomatis.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 min-w-full xl:min-w-[520px] xl:max-w-2xl">
                <div class="rounded-xl border theme-danger-border theme-danger-surface p-3">
                    <p class="text-sm font-bold uppercase tracking-wider theme-danger-text">Prioritas Tinggi</p>
                    <p class="mt-2 text-2xl font-extrabold theme-heading">{{ $interventionCounts['high'] }}</p>
                </div>
                <div class="rounded-xl border theme-warning-border theme-warning-surface p-3">
                    <p class="text-sm font-bold uppercase tracking-wider theme-warning-text">Perlu Dipantau</p>
                    <p class="mt-2 text-2xl font-extrabold theme-heading">{{ $interventionCounts['medium'] }}</p>
                </div>
                <div class="rounded-xl border theme-accent-border theme-accent-surface p-3">
                    <p class="text-sm font-bold uppercase tracking-wider theme-accent-text">Pemantauan Rutin</p>
                    <p class="mt-2 text-2xl font-extrabold theme-heading">{{ $interventionCounts['low'] }}</p>
                </div>
                <div class="rounded-xl border theme-success-border theme-success-surface p-3">
                    <p class="text-sm font-bold uppercase tracking-wider theme-success-text">Tuntas / Stabil</p>
                    <p class="mt-2 text-2xl font-extrabold theme-heading">{{ $interventionCounts['stable'] }}</p>
                </div>
            </div>
        </div>

        <div class="mt-5 border-t theme-border pt-5">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-sm font-bold theme-heading">Prioritas Tindak Lanjut</h4>
                    <p class="mt-1 text-sm theme-muted">Maksimal lima siswa ditampilkan berdasarkan tingkat intervensi dan progres terendah.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                @forelse($priorityStudents as $studentStat)
                    @php
                        $level = $studentStat['intervention_level'];
                        $badgeClass = match ($level) {
                            'high' => 'theme-danger-border theme-danger-surface theme-danger-text',
                            'medium' => 'theme-warning-border theme-warning-surface theme-warning-text',
                            'stable' => 'theme-success-border theme-success-surface theme-success-text',
                            default => 'theme-accent-border theme-accent-surface theme-accent-text',
                        };
                        $levelLabel = match ($level) {
                            'high' => 'Prioritas Tinggi',
                            'medium' => 'Perlu Dipantau',
                            'stable' => 'Tuntas / Stabil',
                            default => 'Pemantauan Rutin',
                        };
                    @endphp
                    <div class="rounded-xl border teacher-card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold theme-heading">{{ $studentStat['enrollment']->student->name }}</p>
                                <p class="mt-1 text-sm theme-muted">
                                    Progres {{ number_format($studentStat['enrollment']->progress_percentage, 0) }}% &bull;
                                    Rata-rata quiz {{ number_format($studentStat['average_quiz_score'], 1) }}%
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-sm font-bold uppercase tracking-wide {{ $badgeClass }}">{{ $levelLabel }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed theme-body">{{ $studentStat['intervention_message'] }}</p>
                        <div class="mt-3 text-right">
                            <a href="{{ route('instructor.courses.analytics.student', [$course, $studentStat['enrollment']->student]) }}" class="text-sm font-bold theme-accent-text theme-accent-text-hover">Buka Detail Siswa &rarr;</a>
                        </div>
                    </div>
                @empty
                    <div class="lg:col-span-2 rounded-xl border border-dashed theme-border px-4 py-6 text-center text-sm theme-muted">
                        Belum ada data siswa untuk dirangkum.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl theme-surface border theme-border shadow">
            <span class="text-sm font-bold uppercase tracking-wider theme-body">Rata-Rata Nilai Quiz</span>
            <p class="mt-3 text-3xl font-extrabold theme-heading">{{ number_format($averageQuizScore, 1) }}%</p>
            <p class="mt-1 text-sm theme-muted">Dari {{ $submittedAttempts }} attempt submitted</p>
        </div>

        <div class="p-5 rounded-2xl theme-surface border theme-border shadow">
            <span class="text-sm font-bold uppercase tracking-wider theme-body">Pass Rate Quiz</span>
            <p class="mt-3 text-3xl font-extrabold theme-success-text">{{ number_format($quizPassRate, 1) }}%</p>
            <p class="mt-1 text-sm theme-muted">Berdasarkan passing score masing-masing quiz</p>
        </div>

        <div class="p-5 rounded-2xl theme-surface border theme-border shadow">
            <span class="text-sm font-bold uppercase tracking-wider theme-body">Partisipasi Quiz</span>
            <p class="mt-3 text-3xl font-extrabold theme-accent-text">{{ $quizParticipants }}</p>
            <p class="mt-1 text-sm theme-muted">Siswa unik pada {{ $totalQuizzes }} quiz</p>
        </div>
    </div>

    <div class="p-6 rounded-2xl theme-surface border theme-border shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <div>
                <h3 class="text-lg font-bold theme-heading">Performa Quiz</h3>
                <p class="mt-1 text-sm theme-muted">Ringkasan performa assessment pada course ini.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b theme-border">
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body">Quiz</th>
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body text-center">Peserta</th>
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body text-center">Attempt</th>
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body text-center">Rata-Rata</th>
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body text-center">Pass Rate</th>
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body text-center">Passing</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($quizPerformance as $quizStat)
                        <tr class="theme-muted-surface-hover transition-colors">
                            <td class="py-4">
                                <p class="text-sm font-semibold theme-heading">{{ $quizStat['title'] }}</p>
                                <p class="mt-1 text-sm uppercase tracking-wide theme-muted">{{ $quizStat['status'] }}</p>
                            </td>
                            <td class="py-4 text-center text-sm theme-body">{{ $quizStat['participants'] }}</td>
                            <td class="py-4 text-center text-sm theme-body">{{ $quizStat['attempts'] }}</td>
                            <td class="py-4 text-center text-sm font-semibold theme-heading">{{ number_format($quizStat['average_score'], 1) }}%</td>
                            <td class="py-4 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-sm font-bold {{ $quizStat['pass_rate'] >= 70 ? 'theme-success-surface theme-success-text border theme-success-border' : 'theme-warning-surface theme-warning-text border theme-warning-border' }}">
                                    {{ number_format($quizStat['pass_rate'], 1) }}%
                                </span>
                            </td>
                            <td class="py-4 text-center text-sm theme-body">{{ number_format($quizStat['passing_score'], 0) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-sm theme-muted">Belum ada quiz pada kursus ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="p-6 rounded-2xl theme-surface border theme-border shadow-xl">
        <div class="mb-4">
            <h3 class="text-lg font-bold theme-heading">Daftar Pendaftar (Siswa)</h3>
            <p class="mt-1 text-sm theme-muted">Buka detail siswa untuk melihat materi, assessment, dan rekomendasi tindak lanjut.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b theme-border">
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body">Siswa</th>
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body">Tanggal Daftar</th>
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body text-center">Progres</th>
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body">Status</th>
                        <th class="pb-3 text-sm font-bold uppercase tracking-wider theme-body text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($enrollments as $enrollment)
                        <tr class="theme-muted-surface-hover transition-colors">
                            <td class="py-4 text-sm theme-heading font-medium">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full theme-accent-surface theme-accent-text flex items-center justify-center font-bold text-sm border theme-accent-border">
                                        {{ substr($enrollment->student->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p>{{ $enrollment->student->name }}</p>
                                        <p class="text-sm theme-muted font-normal">{{ $enrollment->student->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 text-sm theme-body">{{ $enrollment->created_at->format('d M Y') }}</td>
                            <td class="py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-24 theme-muted-surface rounded-full h-2">
                                        <div class="theme-accent-fill h-2 rounded-full" style="width: {{ max(0, min(100, (float) $enrollment->progress_percentage)) }}%"></div>
                                    </div>
                                    <span class="text-sm theme-body font-medium w-10">{{ number_format($enrollment->progress_percentage, 0) }}%</span>
                                </div>
                            </td>
                            <td class="py-4">
                                @if($enrollment->status === 'completed')
                                    <span class="inline-flex items-center gap-1 text-sm font-bold theme-success-text theme-success-surface px-2 py-1 rounded-md border theme-success-border">Lulus</span>
                                @elseif((float) $enrollment->progress_percentage < 50)
                                    <span class="inline-flex items-center gap-1 text-sm font-bold theme-danger-text theme-danger-surface px-2 py-1 rounded-md border theme-danger-border">Perlu Perhatian</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-sm font-bold theme-warning-text theme-warning-surface px-2 py-1 rounded-md border theme-warning-border">Sedang Belajar</span>
                                @endif
                            </td>
                            <td class="py-4 text-right">
                                <a href="{{ route('instructor.courses.analytics.student', [$course, $enrollment->student]) }}" class="inline-flex items-center rounded-lg border theme-accent-border theme-accent-surface px-3 py-1.5 text-sm font-bold theme-accent-text transition-colors theme-accent-surface-hover">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center theme-muted text-sm">Belum ada siswa yang mendaftar ke kursus ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $enrollments->links() }}</div>
    </div>
</div>
@endsection
