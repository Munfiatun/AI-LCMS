@extends('layouts.guest')

@section('content')
<div class="min-h-[calc(100vh-10rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-slate-900/80 p-8 rounded-2xl border border-slate-800 shadow-2xl backdrop-blur-xl">
        <div class="text-center">
            <a href="{{ route('home') }}" aria-label="Kembali ke beranda" class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-400 text-white shadow-lg shadow-indigo-500/25 mb-4 hover:scale-105 active:scale-95 transition-transform cursor-pointer">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
            </a>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">Masuk ke Akun Anda</h2>
            <p class="mt-2 text-sm text-slate-400">Pilih akun demo atau gunakan kredensial yang terdaftar.</p>
        </div>

        @if(app()->environment(['local', 'testing']) && config('auth.demo_user_password'))
            <div class="bg-slate-950/80 p-4 rounded-xl border border-slate-800/80 space-y-2.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block text-center">Akun Demo Cepat (1-Klik)</span>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="fillDemoCredentials('admin@example.com')" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-rose-500/10 text-rose-300 border border-rose-500/20 hover:bg-rose-500/20 transition-all text-center">👑 Admin</button>
                    <button type="button" onclick="fillDemoCredentials('instructor@example.com')" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 hover:bg-indigo-500/20 transition-all text-center">🎓 Guru</button>
                    <button type="button" onclick="fillDemoCredentials('student@example.com')" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all text-center">🎒 Siswa</button>
                </div>
                <p class="text-[10px] text-slate-500 text-center">Panel ini hanya tersedia pada environment local/testing.</p>
            </div>
        @endif

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

        @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 text-xs">{{ session('success') }}</div>
        @endif

        <form class="mt-6 space-y-5" action="{{ route('login') }}" method="POST">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Alamat Email</label>
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="nama@email.com">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                       placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-800 bg-slate-950 rounded">
                    <label for="remember" class="ml-2 block text-xs text-slate-400">Ingat Saya</label>
                </div>
            </div>

            <div>
                <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 shadow-lg shadow-indigo-600/25 transition-all hover:scale-[1.01] active:scale-[0.99]">Masuk ke Platform</button>
            </div>
        </form>

        <div class="text-center text-xs text-slate-400">
            Belum memiliki akun?
            <a href="{{ route('register') }}" class="font-semibold text-indigo-400 hover:text-indigo-300">Daftar sebagai siswa atau guru</a>
        </div>
    </div>
</div>

@if(app()->environment(['local', 'testing']) && config('auth.demo_user_password'))
<script>
    const demoPassword = @js(config('auth.demo_user_password'));

    function fillDemoCredentials(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = demoPassword;
    }
</script>
@endif
@endsection
