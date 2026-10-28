@extends('layouts.app')

@php
    $title = $questionBank->title;
    $breadcrumb = 'Detail Bank Soal';
@endphp

@section('content')
<div class="space-y-6" x-data="{ showEditModal: false, showAddModal: false }">
    <!-- Header Card -->
    <div class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"></div>
        <div class="flex flex-col md:flex-row justify-between gap-6 relative z-10">
            <div class="space-y-2 flex-1">
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        {{ $questionBank->course?->title ?? 'Global (Semua Kursus)' }}
                    </span>
                    @if($questionBank->status === 'active')
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Active</span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-slate-500/20 text-slate-400 border border-slate-500/30">Archived</span>
                    @endif
                </div>
                <h1 class="text-2xl font-extrabold text-white">{{ $questionBank->title }}</h1>
                <p class="text-sm text-slate-400">{{ $questionBank->description ?? 'Tidak ada deskripsi.' }}</p>
                
                <div class="flex flex-wrap gap-4 pt-3 text-sm">
                    <div class="flex items-center gap-2 bg-slate-950/50 px-3 py-1.5 rounded-lg border border-slate-800">
                        <span class="text-slate-400">Total Soal:</span>
                        <span class="text-white font-bold">{{ $questions->count() }}</span>
                    </div>
                    @php
                        $reviewCount = $questions->where('needs_review', true)->count();
                    @endphp
                    @if($reviewCount > 0)
                    <div class="flex items-center gap-2 bg-amber-500/10 px-3 py-1.5 rounded-lg border border-amber-500/20">
                        <span class="text-amber-500/80">Perlu Review:</span>
                        <span class="text-amber-400 font-bold">{{ $reviewCount }}</span>
                        <a href="{{ route('instructor.question-banks.review', $questionBank) }}" class="ml-2 text-xs text-amber-400 hover:text-amber-300 underline font-semibold">Tinjau Sekarang</a>
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="flex items-start gap-3">
                <button @click="showEditModal = true" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium rounded-xl transition-colors border border-slate-700">
                    Edit Info
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content: Questions List -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white">Daftar Pertanyaan</h2>
                <button @click="showAddModal = true" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Manual
                </button>
            </div>

            @forelse($questions as $question)
                <div class="bg-slate-900/60 rounded-xl border {{ $question->needs_review ? 'border-amber-500/30' : 'border-slate-800' }} p-5 relative overflow-hidden group">
                    @if($question->needs_review)
                        <div class="absolute top-0 right-0 bg-amber-500/10 px-3 py-1 text-[10px] font-bold text-amber-400 rounded-bl-lg border-b border-l border-amber-500/20 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Kunci Jawaban Inferred (Butuh Review)
                        </div>
                    @endif

                    <div class="flex gap-4">
                        <div class="shrink-0 w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-sm font-bold text-slate-300">
                            {{ $loop->iteration }}
                        </div>
                        <div class="flex-1 space-y-4 pt-1">
                            <div class="text-sm text-slate-200">
                                {!! nl2br(e($question->question_text)) !!}
                            </div>
                            
                            <div class="space-y-2 mt-4">
                                @foreach($question->options as $option)
                                    <div class="flex items-start gap-3 p-3 rounded-lg border {{ $option->is_correct ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-slate-950/50 border-slate-800' }}">
                                        <div class="mt-0.5 {{ $option->is_correct ? 'text-emerald-400' : 'text-slate-500' }}">
                                            @if($option->is_correct)
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            @else
                                                <div class="w-4 h-4 rounded-full border border-slate-600"></div>
                                            @endif
                                        </div>
                                        <div class="text-sm {{ $option->is_correct ? 'text-emerald-100' : 'text-slate-400' }}">
                                            {{ $option->option_text }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-4 pt-4 mt-4 border-t border-slate-800/80">
                                <span class="text-xs text-slate-500">Kesulitan: <strong class="text-slate-300 capitalize">{{ $question->difficulty }}</strong></span>
                                <span class="text-xs text-slate-500">Bobot: <strong class="text-slate-300">{{ $question->points }} pts</strong></span>
                                
                                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2">
                                    <form action="{{ route('instructor.questions.destroy', $question) }}" method="POST" onsubmit="return confirm('Hapus pertanyaan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 font-medium">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center rounded-2xl bg-slate-900/60 border border-slate-800 border-dashed">
                    <p class="text-slate-400 text-sm">Belum ada pertanyaan di bank soal ini.</p>
                </div>
            @endforelse
        </div>

        <!-- Sidebar: Upload & AI Extraction -->
        <div class="space-y-6">
            <div class="bg-slate-900/80 rounded-2xl border border-slate-800 p-5">
                <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    Ekstraksi Soal dengan AI
                </h3>
                
                <form action="{{ route('instructor.question-banks.upload-document', $questionBank) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="border-2 border-dashed border-slate-700 rounded-xl p-4 text-center hover:bg-slate-800/50 transition-colors relative cursor-pointer" x-data="{ fileName: '' }">
                        <input type="file" name="document" accept=".pdf,.doc,.docx" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="fileName = $event.target.files[0].name">
                        
                        <div x-show="!fileName">
                            <svg class="mx-auto h-8 w-8 text-slate-500 mb-2" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-xs text-slate-400">Pilih dokumen soal (PDF/DOCX)</p>
                            <p class="text-[10px] text-slate-500 mt-1">Maks 10MB</p>
                        </div>
                        <div x-show="fileName" style="display: none;">
                            <svg class="mx-auto h-8 w-8 text-indigo-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-xs text-indigo-300 font-medium break-all" x-text="fileName"></p>
                        </div>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition-colors shadow-lg shadow-indigo-600/20">
                        Unggah & Siapkan AI
                    </button>
                </form>

                @if($documents->count() > 0)
                    <div class="mt-6 pt-5 border-t border-slate-800/80 space-y-3">
                        <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dokumen Siap Ekstrak</h4>
                        @foreach($documents as $doc)
                            <div class="bg-slate-950 border border-slate-800 rounded-lg p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 truncate">
                                        <p class="text-xs font-medium text-slate-300 truncate" title="{{ $doc->original_name }}">{{ $doc->original_name }}</p>
                                        <p class="text-[10px] text-slate-500 mt-0.5">{{ number_format($doc->size / 1024, 1) }} KB</p>
                                    </div>
                                    
                                    <form action="{{ route('instructor.question-banks.extract', $questionBank) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="document_id" value="{{ $doc->id }}">
                                        <button type="submit" class="px-2 py-1 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 text-[10px] font-bold rounded border border-indigo-500/20 transition-colors whitespace-nowrap">
                                            Ekstrak AI
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-cyan-500/20 bg-cyan-500/[0.06] p-5">
                <div class="mb-4">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-cyan-300">R4 Adaptive Flow</p>
                    <h3 class="mt-1 text-base font-bold text-white">Siapkan Formative Otomatis</h3>
                    <p class="mt-2 text-xs leading-5 text-slate-400">Pilih satu concept. Sistem akan memetakan seluruh soal terverifikasi di bank ini ke concept tersebut, membuat quiz formative draft, dan memasukkan semua soal secara otomatis.</p>
                </div>

                @if($questionBank->course && $concepts->isNotEmpty())
                    <form action="{{ route('instructor.question-banks.create-formative', $questionBank) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-slate-300">Target Concept</label>
                            <select name="learning_concept_id" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                <option value="">— Pilih Concept —</option>
                                @foreach($concepts as $concept)
                                    <option value="{{ $concept->id }}">{{ $concept->code }} — {{ $concept->name }}</option>
                                @endforeach
                            </select>
                            @error('learning_concept_id')
                                <p class="mt-2 text-xs font-medium text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-cyan-500">
                            Siapkan Formative
                        </button>
                        <p class="text-[11px] leading-5 text-slate-500">Quiz tetap dibuat sebagai Draft. Guru masih harus meninjau lalu menekan <strong class="text-slate-300">Terbitkan Kuis</strong>.</p>
                    </form>
                @elseif(!$questionBank->course)
                    <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-3 text-xs leading-5 text-amber-200">Hubungkan bank soal ke course melalui <strong>Edit Info</strong> terlebih dahulu.</div>
                @else
                    <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-3 text-xs leading-5 text-amber-200">Course ini belum memiliki Learning Concept aktif.</div>
                @endif
            </div>

            <!-- Guidelines -->
            <div class="bg-slate-900/60 rounded-2xl border border-slate-800 p-5 text-sm text-slate-400 space-y-3">
                <h4 class="font-bold text-slate-300 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Panduan AI
                </h4>
                <ul class="list-disc pl-4 space-y-1 text-xs">
                    <li>Gunakan dokumen berekstensi PDF atau DOCX.</li>
                    <li>Sertakan kunci jawaban di akhir soal jika ada. Jika tidak, AI akan menandai soal untuk direview manual.</li>
                    <li>Struktur soal yang jelas (nomor, pilihan A/B/C/D) akan mempercepat proses ekstraksi.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Edit Modal (AlpineJS) -->
    <div
        x-cloak
        x-show="showEditModal"
        @keydown.escape.window="showEditModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
        aria-labelledby="edit-question-bank-title"
        role="dialog"
        aria-modal="true"
    >
        <div @click="showEditModal = false" class="absolute inset-0 z-0 bg-slate-950/80 backdrop-blur-sm" aria-hidden="true"></div>
        <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 text-left shadow-2xl">
            <form action="{{ route('instructor.question-banks.update', $questionBank) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="px-6 py-5 border-b border-slate-800">
                    <h3 id="edit-question-bank-title" class="text-lg font-bold text-white">Edit Bank Soal</h3>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Judul Bank Soal <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $questionBank->title) }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Course</label>
                        <select name="course_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">Global (Semua Kursus)</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected((string) old('course_id', $questionBank->course_id) === (string) $course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-500">Bank soal global dapat dipindahkan ke course tertentu. Jika soal sudah memiliki Concept Mapping dari course lain, perpindahan akan ditolak agar relasi tetap konsisten.</p>
                        @error('course_id')
                            <p class="mt-1.5 text-xs font-medium text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                        <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="active" @selected(old('status', $questionBank->status) === 'active')>Active</option>
                            <option value="archived" @selected(old('status', $questionBank->status) === 'archived')>Archived</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Deskripsi Singkat</label>
                        <textarea name="description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">{{ old('description', $questionBank->description) }}</textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-950/50 border-t border-slate-800 flex justify-end gap-3">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Question Modal (AlpineJS) -->
    <div x-show="showAddModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showAddModal" @click="showAddModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showAddModal" class="inline-block align-bottom bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-slate-800">
                <form action="{{ route('instructor.question-banks.questions.store', $questionBank) }}" method="POST">
                    @csrf
                    <div class="px-6 py-5 border-b border-slate-800">
                        <h3 class="text-lg font-bold text-white">Tambah Pertanyaan Manual</h3>
                    </div>
                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Pertanyaan <span class="text-rose-500">*</span></label>
                            <textarea name="question_text" rows="3" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-600" placeholder="Tuliskan pertanyaan di sini..."></textarea>
                        </div>
                        
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-slate-300">Pilihan Jawaban <span class="text-rose-500">*</span></label>
                            <p class="text-xs text-slate-500 mb-2">Pilih salah satu radio button untuk menandai kunci jawaban yang benar.</p>
                            
                            @foreach(['A', 'B', 'C', 'D'] as $idx => $label)
                            <div class="flex items-center gap-3">
                                <input type="radio" name="correct_option" value="{{ $idx }}" {{ $idx === 0 ? 'checked' : '' }} class="w-4 h-4 text-emerald-500 bg-slate-950 border-slate-700 focus:ring-emerald-500 focus:ring-offset-slate-900">
                                <div class="flex-1 flex gap-2">
                                    <span class="inline-flex items-center justify-center w-10 bg-slate-800 text-slate-300 rounded-lg text-sm font-bold">{{ $label }}</span>
                                    <input type="text" name="options[{{ $idx }}]" required placeholder="Opsi {{ $label }}..." class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Tingkat Kesulitan</label>
                                <select name="difficulty" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <option value="easy">Easy</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-1">Bobot Nilai</label>
                                <input type="number" name="points" value="10" min="1" max="100" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-950/50 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors">Simpan Pertanyaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #334155;
    border-radius: 10px;
}
</style>
@endsection