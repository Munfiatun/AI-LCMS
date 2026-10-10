@extends('layouts.app')

@php
    $title = 'Dashboard Admin';
    $breadcrumb = 'Admin Dashboard';
@endphp

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-300 border border-rose-500/20 mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    Admin Control Center
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Selamat Datang, {{ auth()->user()->name }}</h1>
                <p class="text-sm text-slate-400 mt-1">Kelola pengguna, pantau aktivitas pembelajaran, dan audit pemrosesan AI platform.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Queue: Database Worker Ready
                </span>
            </div>
        </div>
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800/80 shadow hover:border-slate-700 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Pengguna</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_users'] }}</span>
                <span class="text-xs text-slate-400 ml-2">Akun terdaftar</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800/80 shadow hover:border-slate-700 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Siswa</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_students'] }}</span>
                <span class="text-xs text-emerald-400 ml-2">Siswa aktif</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800/80 shadow hover:border-slate-700 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Instruktur / Guru</span>
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_instructors'] }}</span>
                @if($stats['pending_instructors'] > 0)
                    <span class="text-xs text-amber-400 ml-2">{{ $stats['pending_instructors'] }} menunggu</span>
                @else
                    <span class="text-xs text-slate-400 ml-2">Pembuat konten</span>
                @endif
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800/80 shadow hover:border-slate-700 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Administrator</span>
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $stats['total_admins'] }}</span>
                <span class="text-xs text-rose-400 ml-2">Hak akses penuh</span>
            </div>
        </div>
    </div>

    <!-- Instructor Approval Queue -->
    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-white">Pengajuan Akun Guru</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">{{ $stats['pending_instructors'] }} MENUNGGU</span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Akun guru baru harus disetujui Admin sebelum dapat masuk ke dashboard Instructor.</p>
            </div>
        </div>

        @if($pendingInstructors->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-800 bg-slate-950/40 px-4 py-6 text-center">
                <p class="text-sm font-medium text-slate-300">Tidak ada pengajuan guru yang menunggu.</p>
                <p class="text-xs text-slate-500 mt-1">Pengajuan baru akan muncul otomatis di panel ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/80 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Diajukan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($pendingInstructors as $user)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3 font-medium text-white">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-xs text-slate-400">{{ $user->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form action="{{ route('admin.users.activate-instructor', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 hover:bg-emerald-500/20 transition-colors text-xs font-semibold">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            Aktifkan Guru
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Recent Users Table -->
    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-white">Pengguna Terbaru</h3>
                <p class="text-xs text-slate-400">Daftar akun yang baru terdaftar di platform.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Bergabung</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($recentUsers as $user)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-3 font-medium text-white flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold text-indigo-400">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <span>{{ $user->name }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                @if($user->isAdmin())
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-rose-500/20 text-rose-300 border border-rose-500/30">Admin</span>
                                @elseif($user->isInstructor())
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Instructor</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Student</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs text-amber-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Menunggu Persetujuan
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">{{ $user->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada pengguna terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
