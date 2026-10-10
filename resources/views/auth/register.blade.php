@extends('layouts.guest')

@section('content')
<div class="min-h-[calc(100vh-10rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-7 bg-slate-900/80 p-8 rounded-2xl border border-slate-800 shadow-2xl backdrop-blur-xl">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-400 text-white shadow-lg shadow-indigo-500/25 mb-4">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">Pendaftaran Akun</h2>
            <p class="mt-2 text-sm text-slate-400">
                Pilih peran Anda untuk bergabung ke platform pembelajaran AI-LCMS.
            </p>
        </div>

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <p class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ $error }}</span>
                    </p>
                @endforeach
            </div>
        @endif

        <form class="space-y-4" action="{{ route('register') }}" method="POST">
            @csrf

            <fieldset>
                <legend class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Daftar Sebagai</legend>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="account_type" value="student" class="peer sr-only" {{ old('account_type', 'student') === 'student' ? 'checked' : '' }} required>
                        <span class="flex flex-col items-center gap-2 rounded-xl border border-slate-800 bg-slate-950/80 px-4 py-3 text-slate-400 transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-500/10 peer-checked:text-indigo-300 hover:border-slate-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0118.5 15c0 1.66-.336 3.24-.944 4.678L12 17l-5.556 2.678A11.95 11.95 0 015.5 15c0-1.559.3-3.048.844-4.414L12 14z" /></svg>
                            <span class="text-sm font-bold">Siswa</span>
                        </span>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="account_type" value="instructor" class="peer sr-only" {{ old('account_type') === 'instructor' ? 'checked' : '' }} required>
                        <span class="flex flex-col items-center gap-2 rounded-xl border border-slate-800 bg-slate-950/80 px-4 py-3 text-slate-400 transition-all peer-checked:border-cyan-500 peer-checked:bg-cyan-500/10 peer-checked:text-cyan-300 hover:border-slate-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" /></svg>
                            <span class="text-sm font-bold">Guru</span>
                        </span>
                    </label>
                </div>
                <p class="mt-2.5 text-[11px] leading-relaxed text-slate-500">
                    Akun siswa langsung aktif. Pendaftaran guru memerlukan persetujuan Admin sebelum dapat masuk ke dashboard Instructor.
                </p>
            </fieldset>

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <input id="name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}"
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="Contoh: Budi Santoso">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Alamat Email</label>
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="nama@email.com">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="Minimal 8 karakter">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="Ulangi password">
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-lg shadow-indigo-600/25 transition-all hover:scale-[1.01] active:scale-[0.99]">
                    Lanjutkan Pendaftaran
                </button>
            </div>
        </form>

        <div class="text-center text-xs text-slate-400">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="font-semibold text-indigo-400 hover:text-indigo-300">Masuk di sini</a>
        </div>
    </div>
</div>
@endsection
