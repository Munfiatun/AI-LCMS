<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 theme-surface text-slate-100 theme-heading">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} — AI LCMS</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="{{ auth()->user()->isInstructor() ? 'teacher-workspace' : '' }} flex h-full bg-slate-950 theme-surface text-slate-100 theme-heading antialiased selection:bg-indigo-500 selection:text-white"
      x-data="{ sidebarOpen: false }">

    <div x-show="sidebarOpen"
         x-cloak
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-950/80 teacher-backdrop backdrop-blur-sm lg:hidden"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-800 theme-border bg-slate-900 theme-surface transition-transform duration-200 lg:static lg:translate-x-0">

        <div class="flex h-16 items-center justify-between border-b border-slate-800 theme-border px-5">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl teacher-brand-mark bg-gradient-to-tr from-indigo-600 to-cyan-400 shadow-md shadow-indigo-500/20">
                    <svg class="h-5 w-5 text-white teacher-on-solid" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <div class="text-base font-extrabold tracking-tight text-white theme-heading">AI-LCMS</div>
                    <div class="text-xs text-slate-500 theme-muted">Learning Workspace</div>
                </div>
            </a>

            <button @click="sidebarOpen = false" class="rounded-lg p-2 text-slate-400 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover lg:hidden" aria-label="Tutup menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="mx-3 mt-3 flex items-center gap-3 rounded-xl border border-slate-800 theme-border bg-slate-950/55 theme-surface p-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 theme-accent-fill text-sm font-extrabold text-white teacher-on-solid">
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-white theme-heading">{{ auth()->user()->name }}</p>
                <p class="mt-0.5 text-xs text-slate-500 theme-muted">
                    {{ auth()->user()->isAdmin() ? 'Administrator' : (auth()->user()->isInstructor() ? 'Guru / Instructor' : 'Siswa') }}
                </p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            @if(auth()->user()->isAdmin())
                <p class="mb-2 px-3 text-xs font-bold text-slate-500 theme-muted">Menu Utama</p>

                <a href="{{ route('admin.dashboard') }}"
                   class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h14V10" /></svg>
                    Dashboard
                </a>

                <a href="{{ route('admin.categories.index') }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.categories.*') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    Kategori & Kursus
                </a>

            @elseif(auth()->user()->isInstructor())
                @php
                    $adaptiveOpen = request()->routeIs('instructor.learning-objectives.*')
                        || request()->routeIs('instructor.learning-concepts.*')
                        || request()->routeIs('instructor.diagnostics.*')
                        || request()->routeIs('instructor.mastery.*')
                        || request()->routeIs('instructor.courses.research.*');
                @endphp

                <p class="mb-2 px-3 text-xs font-bold text-slate-500 theme-muted">Kerja Harian</p>

                <a href="{{ route('instructor.dashboard') }}"
                   class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('instructor.dashboard') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid shadow-md shadow-indigo-950/30' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h14V10" /></svg>
                    Beranda Guru
                </a>

                <a href="{{ route('instructor.courses.index') }}"
                   class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('instructor.courses.*') || request()->routeIs('instructor.materials.*') || request()->routeIs('instructor.slidebooks.*') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid shadow-md shadow-indigo-950/30' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    Kursus & Materi
                </a>

                <a href="{{ route('instructor.question-banks.index') }}"
                   class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('instructor.question-banks.*') || request()->routeIs('instructor.questions.*') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid shadow-md shadow-indigo-950/30' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-7 4h8m-8 4h8m-8 4h5M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z" /></svg>
                    Bank Soal
                </a>

                <a href="{{ route('instructor.quizzes.index') }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('instructor.quizzes.*') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid shadow-md shadow-indigo-950/30' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 12h6M9 16h4" /></svg>
                    Kuis & Evaluasi
                </a>

                <div class="my-4 border-t border-slate-800 theme-border"></div>

                <div x-data="{ open: {{ $adaptiveOpen ? 'true' : 'false' }} }">
                    <button type="button"
                            @click="open = !open"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition {{ $adaptiveOpen ? 'bg-slate-800 theme-muted-surface text-white theme-heading' : 'text-slate-400 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                        <span class="flex items-center gap-3">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V3m0 18v-3m6-6h3M3 12h3m10.243-4.243l2.121-2.121M5.636 18.364l2.121-2.121m8.486 0l2.121 2.121M5.636 5.636l2.121 2.121" /></svg>
                            Pengaturan Adaptif
                        </span>
                        <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <div x-show="open" x-collapse x-cloak class="mt-1 space-y-1 pl-3">
                        <a href="{{ route('instructor.learning-objectives.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('instructor.learning-objectives.*') ? 'bg-indigo-500/15 theme-accent-surface font-semibold text-indigo-200 theme-accent-text' : 'text-slate-400 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">Tujuan Pembelajaran</a>
                        <a href="{{ route('instructor.learning-concepts.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('instructor.learning-concepts.*') ? 'bg-indigo-500/15 theme-accent-surface font-semibold text-indigo-200 theme-accent-text' : 'text-slate-400 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">Konsep & Mapping</a>
                        <a href="{{ route('instructor.diagnostics.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('instructor.diagnostics.*') ? 'bg-indigo-500/15 theme-accent-surface font-semibold text-indigo-200 theme-accent-text' : 'text-slate-400 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">Diagnostik Awal</a>
                        <a href="{{ route('instructor.mastery.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('instructor.mastery.*') ? 'bg-indigo-500/15 theme-accent-surface font-semibold text-indigo-200 theme-accent-text' : 'text-slate-400 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">Mastery Siswa</a>
                    </div>
                </div>

                <p class="mt-3 px-3 text-xs leading-5 text-slate-600 theme-muted">Fitur adaptif tetap tersedia, tetapi tidak mengganggu alur kerja harian.</p>

            @else
                <p class="mb-2 px-3 text-xs font-bold text-slate-500 theme-muted">Pembelajaran</p>

                <a href="{{ route('student.dashboard') }}"
                   class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('student.dashboard') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h14V10" /></svg>
                    Beranda
                </a>

                @if(Route::has('student.adaptive-recommendations.index'))
                    <a href="{{ route('student.adaptive-recommendations.index') }}"
                       class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('student.adaptive-recommendations.*') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        Rekomendasi Belajar
                    </a>
                @endif

                <a href="{{ route('student.courses.index') }}"
                   class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ (request()->routeIs('student.courses.*') && ! request()->routeIs('student.courses.explore')) || request()->routeIs('student.materials.*') || request()->routeIs('student.slidebooks.*') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253" /></svg>
                    Kursus Saya
                </a>

                <a href="{{ route('student.courses.explore') }}"
                   class="mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('student.courses.explore') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    Jelajah Kursus
                </a>

                <a href="{{ route('student.quizzes.index') }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('student.quizzes.*') ? 'bg-indigo-600 theme-accent-fill text-white teacher-on-solid' : 'text-slate-300 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 12h6M9 16h4" /></svg>
                    Kuis & Nilai
                </a>
            @endif
        </nav>

        <div class="border-t border-slate-800 theme-border p-3">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 theme-body transition hover:bg-rose-500/10 theme-danger-surface-hover hover:text-rose-300 theme-danger-text-hover">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
        <header class="z-30 flex h-16 items-center justify-between border-b border-slate-800 theme-border bg-slate-900/70 theme-surface px-4 backdrop-blur-md sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <button @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-400 theme-body hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover lg:hidden" aria-label="Buka menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-white theme-heading">{{ $breadcrumb ?? $title ?? 'Dashboard' }}</p>
                    <p class="hidden text-xs text-slate-500 theme-muted sm:block">{{ auth()->user()->isInstructor() ? 'Ruang kerja pembelajaran' : 'AI-LCMS' }}</p>
                </div>
            </div>

            <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-400 theme-body transition hover:bg-slate-800 theme-muted-surface-hover hover:text-white theme-heading-hover">
                Beranda Publik
            </a>
        </header>

        <div class="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="flex items-start gap-3 rounded-xl border border-emerald-500/25 theme-success-border bg-emerald-500/10 theme-success-surface p-4 text-sm text-emerald-200 theme-success-text">
                    <span class="mt-0.5">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="flex items-start gap-3 rounded-xl border border-rose-500/25 theme-danger-border bg-rose-500/10 theme-danger-surface p-4 text-sm text-rose-200 theme-danger-text">
                    <span class="mt-0.5">!</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
