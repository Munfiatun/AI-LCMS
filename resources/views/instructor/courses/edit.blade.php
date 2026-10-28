@extends('layouts.app')

@php
    $title = 'Kelola Silabus Kursus';
    $breadcrumb = 'Kelola: ' . $course->title;
    $canPublish = $course->status === 'draft' && $publicationErrors === [];
@endphp

@section('content')
<div class="space-y-8" x-data="{ addSectionModal: false }">
    <div class="teacher-page-header p-6 border flex flex-col xl:flex-row xl:items-center justify-between gap-5">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <a href="{{ route('instructor.courses.index') }}" class="text-sm font-semibold theme-body theme-heading-hover transition-colors">&larr; Kembali ke Daftar</a>
                <span class="theme-muted">&bull;</span>
                <span class="px-2 py-0.5 text-sm font-bold uppercase rounded-full theme-accent-surface theme-accent-text border theme-accent-border">{{ $course->category?->name ?? 'Uncategorized' }}</span>
                <span class="px-2 py-0.5 text-sm font-bold uppercase rounded-full {{ $course->status === 'published' ? 'theme-success-surface theme-success-text border theme-success-border' : ($course->status === 'archived' ? 'theme-muted-surface theme-body border theme-border' : 'theme-warning-surface theme-warning-text border theme-warning-border') }}">{{ ucfirst($course->status) }}</span>
            </div>
            <h1 class="teacher-page-title text-2xl font-extrabold theme-heading tracking-tight truncate">{{ $course->title }}</h1>
            <p class="text-sm theme-body mt-1">Slug: <code class="theme-accent-text theme-surface px-1.5 py-0.5 rounded">{{ $course->slug }}</code></p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if($course->status === 'published')
                <form action="{{ route('instructor.courses.archive', $course) }}" method="POST" onsubmit="return confirm('Arsipkan kursus ini? Kursus tidak lagi tersedia untuk pendaftaran baru.')">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl text-sm font-bold theme-warning-text theme-warning-surface theme-warning-surface-hover border theme-warning-border transition-colors">Arsipkan Kursus</button>
                </form>
                <span class="text-sm theme-success-text flex items-center gap-1.5 theme-success-surface border theme-success-border px-3 py-2 rounded-xl">
                    <span class="w-2 h-2 rounded-full theme-success-fill"></span>
                    Kursus Aktif & Terbit
                </span>
            @elseif($course->status === 'draft')
                <form action="{{ route('instructor.courses.publish', $course) }}" method="POST">
                    @csrf
                    <button type="submit" @disabled(!$canPublish)
                        class="px-4 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-1.5 {{ $canPublish ? 'teacher-on-solid theme-success-fill theme-success-fill-hover shadow-md shadow-emerald-600/20' : 'theme-muted theme-muted-surface border theme-border cursor-not-allowed' }}">
                        Publikasikan Kursus
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($course->status === 'draft')
        <div class="p-5 rounded-2xl border {{ $publicationErrors === [] ? 'theme-success-surface theme-success-border' : 'theme-warning-surface theme-warning-border' }}">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 w-8 h-8 rounded-lg flex items-center justify-center {{ $publicationErrors === [] ? 'theme-success-surface theme-success-text' : 'theme-warning-surface theme-warning-text' }}">
                    {{ $publicationErrors === [] ? '✓' : '!' }}
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold theme-heading">Kesiapan Publikasi</h2>
                    @if($publicationErrors === [])
                        <p class="text-sm theme-success-text mt-1">Kursus siap dipublikasikan.</p>
                    @else
                        <p class="text-sm theme-body mt-1 mb-2">Tombol publikasi dinonaktifkan sampai syarat berikut terpenuhi:</p>
                        <ul class="space-y-1 text-sm theme-warning-text">
                            @foreach($publicationErrors as $error)
                                <li class="flex items-start gap-2"><span>•</span><span>{{ $error }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <div class="space-y-6 lg:col-span-1">
            <div class="p-6 rounded-2xl theme-surface border theme-border shadow space-y-4">
                <h2 class="text-base font-bold theme-heading border-b theme-border pb-3">Informasi Umum Kursus</h2>
                <form action="{{ route('instructor.courses.update', $course) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="title" class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Judul Kursus</label>
                        <input type="text" id="title" name="title" required value="{{ old('title', $course->title) }}" class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Kategori</label>
                        <select id="category_id" name="category_id" class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Tanpa Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $course->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Deskripsi</label>
                        <textarea id="description" name="description" rows="5" class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500">{{ old('description', $course->description) }}</textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-bold teacher-on-solid theme-accent-fill theme-accent-fill-hover transition-colors shadow">Perbarui Informasi</button>
                </form>
            </div>

            <div class="p-6 rounded-2xl theme-surface border theme-border shadow space-y-4">
                <h2 class="text-base font-bold theme-heading border-b theme-border pb-3">Akses & Pendaftaran Siswa</h2>
                <div class="space-y-3">
                    <label class="block text-sm font-semibold theme-body uppercase tracking-wider">Token Kelas</label>
                    @if($course->enrollment_code)
                        <div class="flex items-center justify-between gap-3 p-3 theme-surface border theme-border rounded-xl">
                            <code class="text-lg font-mono font-bold theme-success-text tracking-widest truncate" id="tokenText">{{ $course->enrollment_code }}</code>
                            <button onclick="navigator.clipboard.writeText(@js($course->enrollment_code)).then(() => alert('Token berhasil disalin.'))" type="button" class="teacher-secondary-button px-3 py-1.5 theme-muted-surface theme-muted-surface-hover theme-body text-sm font-bold uppercase rounded-lg border theme-border">Salin</button>
                        </div>
                    @else
                        <div class="p-3 theme-surface border theme-warning-border rounded-xl"><p class="text-sm theme-warning-text font-medium">Belum ada token. Regenerate untuk membuat token baru.</p></div>
                    @endif
                    <form action="{{ route('instructor.courses.regenerate-code', $course) }}" method="POST" onsubmit="return confirm('Token lama tidak dapat digunakan lagi untuk pendaftaran baru. Lanjutkan?')">
                        @csrf
                        <button type="submit" class="w-full py-2 px-4 rounded-xl text-sm font-bold theme-warning-text theme-warning-surface theme-warning-surface-hover border theme-warning-border transition-colors">Regenerate Token</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold theme-heading">Silabus & Struktur Materi</h2>
                    <p class="text-sm theme-body">Atur chapter/bab dan materi ajar beserta dokumen PDF/DOCX pendukung.</p>
                </div>
                <button @click="addSectionModal = true" type="button" class="px-3.5 py-2 rounded-xl text-sm font-bold teacher-on-solid theme-accent-fill theme-accent-fill-hover transition-all shadow">+ Tambah Bab</button>
            </div>

            <div class="space-y-5">
                @forelse($course->sections as $section)
                    <div class="rounded-2xl theme-surface border theme-border shadow overflow-hidden">
                        <div class="p-4 theme-surface border-b theme-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-7 h-7 rounded-md theme-accent-surface theme-accent-text text-sm font-bold flex items-center justify-center border theme-accent-border flex-shrink-0">{{ $section->order }}</span>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold theme-heading truncate">{{ $section->title }}</h3>
                                    @if($section->description)<p class="text-sm theme-body mt-0.5">{{ $section->description }}</p>@endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="{{ route('instructor.materials.create', $section) }}" class="px-2.5 py-1 text-sm font-semibold rounded-lg theme-accent-surface theme-accent-text theme-accent-surface-hover border theme-accent-border">+ Materi</a>
                                <form action="{{ route('instructor.sections.destroy', $section) }}" method="POST" onsubmit="return confirm('Hapus bab ini dan seluruh materinya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-sm theme-danger-text theme-danger-text-hover">Hapus</button>
                                </form>
                            </div>
                        </div>

                        <div class="p-3 space-y-2">
                            @forelse($section->materials as $mat)
                                <div class="p-3 rounded-xl theme-surface border theme-border theme-border-hover flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-6 h-6 rounded theme-muted-surface theme-body text-sm font-semibold flex items-center justify-center flex-shrink-0">{{ $mat->order }}</span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('instructor.materials.edit', $mat) }}" class="text-sm font-bold theme-heading theme-accent-text-hover transition-colors">{{ $mat->title }}</a>
                                                <span class="px-1.5 py-0.5 text-sm font-bold uppercase rounded {{ $mat->status === 'published' ? 'theme-success-surface theme-success-text' : 'theme-muted-surface theme-body' }}">{{ ucfirst($mat->status) }}</span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2 text-sm theme-body mt-1">
                                                <span>⏱ {{ $mat->duration_minutes }} menit</span><span>&bull;</span><span class="theme-accent-text">📄 {{ $mat->documents->count() }} dokumen</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <a href="{{ route('instructor.materials.edit', $mat) }}" class="text-sm theme-accent-text font-medium px-2.5 py-1.5 rounded-lg theme-accent-surface">Kelola</a>
                                        <form action="{{ route('instructor.materials.destroy', $mat) }}" method="POST" onsubmit="return confirm('Hapus materi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm theme-danger-text px-2 py-1">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="py-5 text-center text-sm theme-muted">Belum ada materi di bab ini. Klik <strong>+ Materi</strong> untuk menambahkan.</div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center rounded-2xl theme-surface border theme-border">
                        <p class="text-sm theme-body font-medium">Belum ada bab/chapter pembelajaran.</p>
                        <p class="text-sm theme-muted mt-1">Klik tombol <strong>+ Tambah Bab</strong> untuk membuat bab pertama.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div x-show="addSectionModal" class="fixed inset-0 teacher-backdrop backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak>
        <div class="theme-surface border theme-border rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="addSectionModal = false">
            <h3 class="text-base font-bold theme-heading">Tambah Bab / Chapter Baru</h3>
            <form action="{{ route('instructor.sections.store', $course) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="section_title" class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Judul Bab <span class="theme-danger-text">*</span></label>
                    <input type="text" id="section_title" name="title" required class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: Bab 1: Dasar Pengenalan">
                </div>
                <div>
                    <label for="section_desc" class="block text-sm font-semibold theme-body uppercase tracking-wider mb-1">Deskripsi Singkat</label>
                    <textarea id="section_desc" name="description" rows="2" class="w-full px-3.5 py-2 theme-surface border theme-border rounded-xl theme-heading text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Penjelasan pokok bahasan..."></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="addSectionModal = false" class="px-4 py-2 text-sm font-semibold theme-body theme-heading-hover">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-bold teacher-on-solid theme-accent-fill theme-accent-fill-hover rounded-xl">Simpan Bab</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
