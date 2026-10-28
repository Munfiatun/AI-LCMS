<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Laravel AI LCMS' }} — Learning Content Management System</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js + Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full flex flex-col font-sans antialiased bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white">
    @unless($focusMode ?? false)
    <!-- Navbar -->
    <header class="theme-guest-header border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('home') }}" class="flex items-center space-x-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-400 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-200">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <span class="guest-brand-text text-lg font-bold tracking-tight bg-gradient-to-r from-white via-slate-100 to-indigo-200 bg-clip-text text-transparent">AI-LCMS</span>
                        <span class="hidden sm:inline-block ml-1.5 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-full">Pro</span>
                    </div>
                </a>

                <!-- Nav Links -->
                <nav class="hidden md:flex items-center ml-8 space-x-1">
                    <a href="{{ route('home') }}" class="px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('home') ? 'text-white bg-slate-800' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }} transition-colors">
                        Beranda
                    </a>
                    <a href="{{ route('courses.index') }}" class="px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('courses.*') ? 'text-white bg-slate-800' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }} transition-colors">
                        Katalog Kursus
                    </a>
                </nav>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <button type="button" data-theme-toggle
                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-3 text-sm font-semibold text-slate-300 transition hover:bg-slate-700"
                        aria-label="Ganti tema">
                    Tema
                </button>

                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-md shadow-indigo-600/20 transition-colors">
                            Admin Dashboard &rarr;
                        </a>
                    @elseif(auth()->user()->isInstructor())
                        <a href="{{ route('instructor.dashboard') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-md shadow-indigo-600/20 transition-colors">
                            Instructor Dashboard &rarr;
                        </a>
                    @else
                        <a href="{{ route('student.dashboard') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-md shadow-indigo-600/20 transition-colors">
                            Student Portal &rarr;
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium text-slate-300 hover:text-white transition-colors">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 rounded-lg shadow-md shadow-indigo-600/25 transition-all hover:scale-[1.02]">
                        Daftar Gratis
                    </a>
                @endauth
            </div>
        </div>
    </header>

    @endunless

    <!-- Main Content -->
    <main class="flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @unless($focusMode ?? false)
    <!-- Footer -->
    <footer class="theme-guest-footer border-t border-slate-800/80 bg-slate-950 py-10 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2 text-slate-400 text-sm">
                <span>&copy; {{ date('Y') }} AI-Powered LCMS. Built with Laravel 13 & PHP 8.5.</span>
            </div>
            <div class="flex items-center space-x-6 text-sm text-slate-400">
                <span class="flex items-center gap-1.5 text-xs text-emerald-400 bg-emerald-950/60 border border-emerald-800/40 px-2.5 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    System Operational
                </span>
            </div>
        </div>
    </footer>
    @endunless
</body>
</html>