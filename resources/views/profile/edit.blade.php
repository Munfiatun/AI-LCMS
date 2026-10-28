@extends('layouts.app')

@php
    $title = 'Profil';
    $breadcrumb = 'Profil & Tampilan';
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <section class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80 p-6 sm:p-8">
        <div class="relative z-10 max-w-2xl">
            <p class="text-sm font-semibold text-indigo-300">Pengaturan akun</p>
            <h1 class="mt-2 text-2xl font-extrabold text-white sm:text-3xl">Profil & Tampilan</h1>
            <p class="mt-3 text-base leading-7 text-slate-400">Atur foto profil, informasi singkat, dan pilih tampilan yang paling nyaman.</p>
        </div>

        <div class="education-ornament absolute -bottom-4 right-5 hidden opacity-30 sm:block" aria-hidden="true">
            <svg class="h-28 w-28 text-indigo-300" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M24 30c14 0 27 5 36 14v48c-9-9-22-14-36-14V30Z" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
                <path d="M96 30c-14 0-27 5-36 14v48c9-9 22-14 36-14V30Z" stroke="currentColor" stroke-width="5" stroke-linejoin="round"/>
                <path d="M60 44v48" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
            </svg>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6">
        <div>
            <h2 class="text-lg font-bold text-white">Tema</h2>
            <p class="mt-1 text-sm text-slate-500">Pilihan tersimpan di perangkat ini dan dapat diubah kapan saja.</p>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2" role="group" aria-label="Pilih tema tampilan">
            <button type="button" data-theme-choice="dark" class="theme-choice min-h-20 rounded-2xl border border-slate-700 bg-slate-950 p-4 text-left transition hover:border-indigo-500/50">
                <span class="flex items-center justify-between gap-3">
                    <span>
                        <span class="block font-bold text-white">Gelap</span>
                        <span class="mt-1 block text-sm text-slate-500">Nyaman untuk lingkungan redup.</span>
                    </span>
                    <span class="text-xl" aria-hidden="true">☾</span>
                </span>
            </button>

            <button type="button" data-theme-choice="light" class="theme-choice min-h-20 rounded-2xl border border-slate-700 bg-slate-800 p-4 text-left transition hover:border-indigo-500/50">
                <span class="flex items-center justify-between gap-3">
                    <span>
                        <span class="block font-bold text-white">Terang</span>
                        <span class="mt-1 block text-sm text-slate-400">Lebih ringan untuk penggunaan siang hari.</span>
                    </span>
                    <span class="text-xl" aria-hidden="true">☀</span>
                </span>
            </button>
        </div>
    </section>

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 sm:p-6">
        @csrf
        @method('PUT')

        <div class="grid gap-7 md:grid-cols-[200px_1fr]">
            <div>
                <h2 class="text-lg font-bold text-white">Foto Profil</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Gunakan foto yang jelas. Maksimal 2 MB.</p>

                <div class="mt-5" x-data="{ preview: @js($avatarUrl) }">
                    <div class="relative h-32 w-32 overflow-hidden rounded-3xl border border-slate-700 bg-slate-950 shadow-lg">
                        <template x-if="preview">
                            <img :src="preview" alt="Preview foto profil" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!preview">
                            <div class="flex h-full w-full items-center justify-center bg-indigo-600 text-4xl font-extrabold text-white">
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                        </template>
                    </div>

                    <label for="avatar" class="mt-4 inline-flex min-h-11 cursor-pointer items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                        Pilih Foto
                    </label>
                    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only"
                           @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : @js($avatarUrl)">
                    @error('avatar')
                        <p class="ui-error">{{ $message }}</p>
                    @enderror

                    @if($avatarUrl)
                        <label class="mt-3 flex items-center gap-2 text-sm text-slate-500">
                            <input type="checkbox" name="remove_avatar" value="1" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-600">
                            Hapus foto saat disimpan
                        </label>
                    @endif
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label for="name" class="ui-label">Nama</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="ui-field" required maxlength="255">
                    @error('name')
                        <p class="ui-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="ui-label">Email</label>
                    <input id="email" type="email" value="{{ $user->email }}" class="ui-field" disabled>
                    <p class="ui-help">Email akun tidak diubah dari halaman ini.</p>
                </div>

                <div>
                    <label for="bio" class="ui-label">Bio Singkat</label>
                    <textarea id="bio" name="bio" rows="5" maxlength="1000" class="ui-field min-h-32 resize-y" placeholder="Tuliskan peran atau deskripsi singkat...">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <p class="ui-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-500">
                        Simpan Profil
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
