@extends('layouts.app')

@php
    $title = 'Domain Model';
    $breadcrumb = 'Adaptive Learning Foundation';
@endphp

@section('content')
<div class="space-y-7">
    <section class="rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-900 via-cyan-950/30 to-slate-900 p-6 shadow-xl sm:p-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-4xl">
                <span class="inline-flex rounded-full border border-cyan-500/20 bg-cyan-500/10 px-3 py-1 text-sm font-semibold text-cyan-200">R1 — Domain Model</span>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Learning Concepts &amp; Content Alignment</h1>
                <p class="mt-3 text-base leading-7 text-slate-300">Pecah Learning Objective menjadi konsep-konsep yang dapat ditelusuri, tentukan prerequisite, lalu petakan materi dan soal ke konsep yang benar.</p>
                <p class="mt-2 text-sm leading-6 text-slate-400">Tahap ini belum menghitung mastery dan belum menghasilkan rekomendasi adaptif.</p>
            </div>
            <a href="{{ route('instructor.learning-objectives.index', $selectedCourse ? ['course_id' => $selectedCourse->id] : []) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-bold text-slate-100 transition-colors hover:bg-slate-700">Learning Objectives →</a>
        </div>
    </section>

    <section aria-label="Alur Domain Model" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['number' => '1', 'title' => 'Pilih Kursus', 'desc' => 'Gunakan satu konteks course.'],
            ['number' => '2', 'title' => 'Definisikan Konsep', 'desc' => 'Buat unit pengetahuan yang akan diukur.'],
            ['number' => '3', 'title' => 'Atur Prerequisite', 'desc' => 'Tentukan konsep fondasi yang diperlukan.'],
            ['number' => '4', 'title' => 'Mapping Konten', 'desc' => 'Hubungkan materi dan soal ke konsep.'],
        ] as $step)
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-base font-extrabold text-white">{{ $step['number'] }}</div>
                    <div>
                        <h2 class="text-base font-bold text-white">{{ $step['title'] }}</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-400">{{ $step['desc'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-950/40 p-4 text-base text-emerald-100">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="space-y-1 rounded-xl border border-rose-500/30 bg-rose-950/50 p-4 text-base text-rose-100">
            @foreach($errors->all() as $error)
                <p>&bull; {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-600 text-lg font-extrabold text-white">1</div>
            <div>
                <h2 class="text-xl font-extrabold text-white">Pilih Kursus</h2>
                <p class="mt-0.5 text-sm text-slate-400">Concept, prerequisite, materi, dan soal harus berada pada course yang sama.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('instructor.learning-concepts.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="course_id" class="mb-2 block text-sm font-semibold text-slate-300">Kursus yang dikerjakan</label>
                <select id="course_id" name="course_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected($selectedCourse?->id === $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-cyan-500">Buka Kursus</button>
        </form>
    </section>

    @if(!$selectedCourse)
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-8 text-center">
            <p class="text-lg font-bold text-white">Belum ada kursus.</p>
            <p class="mt-2 text-base text-slate-400">Buat kursus terlebih dahulu untuk menyusun Domain Model.</p>
        </section>
    @else
        @php
            $concepts = $selectedCourse->learningConcepts;
            $mappedMaterialCount = $selectedCourse->sections->flatMap->materials->filter(fn ($material) => $material->learningConcepts->isNotEmpty())->count();
            $mappedQuestionCount = $selectedCourse->questionBanks->flatMap->questions->filter(fn ($question) => $question->learningConcepts->isNotEmpty())->count();
        @endphp

        <section class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach([
                ['label' => 'Learning Objectives', 'value' => $selectedCourse->learningObjectives->count()],
                ['label' => 'Learning Concepts', 'value' => $concepts->count()],
                ['label' => 'Materi Terpetakan', 'value' => $mappedMaterialCount],
                ['label' => 'Soal Terpetakan', 'value' => $mappedQuestionCount],
            ] as $metric)
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                    <div class="text-2xl font-extrabold text-white">{{ $metric['value'] }}</div>
                    <div class="mt-1 text-sm text-slate-400">{{ $metric['label'] }}</div>
                </div>
            @endforeach
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-600 text-lg font-extrabold text-white">2</div>
                <div>
                    <h2 class="text-xl font-extrabold text-white">Definisikan Learning Concept</h2>
                    <p class="mt-0.5 text-sm text-slate-400">Concept adalah unit pengetahuan spesifik yang nanti menjadi dasar evidence dan mastery.</p>
                </div>
            </div>

            @if($selectedCourse->learningObjectives->isEmpty())
                <div class="mb-5 rounded-xl border border-amber-500/30 bg-amber-950/30 p-4 text-sm text-amber-100">Belum ada Learning Objective. Concept tetap dapat dibuat, tetapi sebaiknya definisikan objective terlebih dahulu agar struktur domain lebih jelas.</div>
            @endif

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-5">
                    <h3 class="text-lg font-bold text-white">Tambah Concept</h3>
                    <form action="{{ route('instructor.learning-concepts.store', $selectedCourse) }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="is_active" value="1">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-300">Kode Concept</label>
                                <input type="text" name="code" required maxlength="50" placeholder="C01" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base font-bold text-cyan-300 uppercase">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-300">Parent Objective</label>
                                <select name="learning_objective_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                                    <option value="">Belum ditentukan</option>
                                    @foreach($selectedCourse->learningObjectives as $objective)
                                        <option value="{{ $objective->id }}">{{ $objective->code }} — {{ $objective->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Nama Concept</label>
                            <input type="text" name="name" required maxlength="255" placeholder="Percabangan" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Deskripsi</label>
                            <textarea name="description" rows="3" placeholder="Ruang lingkup konsep yang harus dipahami siswa." class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base leading-6 text-white"></textarea>
                        </div>
                        <button class="w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-cyan-500">+ Tambah Concept</button>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-5">
                    <h3 class="text-lg font-bold text-white">Concept pada Kursus Ini</h3>
                    <p class="mt-1 text-sm text-slate-400">{{ $concepts->count() }} concept terdefinisi.</p>

                    <div class="mt-5 space-y-3">
                        @forelse($concepts as $concept)
                            <details class="group rounded-xl border border-slate-800 bg-slate-900/70 open:border-cyan-500/30">
                                <summary class="flex cursor-pointer list-none items-start justify-between gap-4 p-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-lg bg-cyan-500/10 px-2.5 py-1 text-base font-extrabold text-cyan-300">{{ $concept->code }}</span>
                                            <span class="rounded-full border border-slate-700 bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-300">{{ $concept->learningObjective?->code ?? 'No LO' }}</span>
                                            @if(!$concept->is_active)
                                                <span class="rounded-full border border-slate-700 bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-400">Nonaktif</span>
                                            @endif
                                        </div>
                                        <p class="mt-3 text-base font-semibold text-white">{{ $concept->name }}</p>
                                        <p class="mt-2 text-sm text-slate-400">{{ $concept->prerequisites->count() }} prerequisite &bull; {{ $concept->materials->count() }} materi &bull; {{ $concept->questions->count() }} soal</p>
                                    </div>
                                    <span class="text-sm font-semibold text-cyan-300 group-open:hidden">Edit ↓</span>
                                    <span class="hidden text-sm font-semibold text-cyan-300 group-open:inline">Tutup ↑</span>
                                </summary>
                                <div class="border-t border-slate-800 p-4">
                                    <form action="{{ route('instructor.learning-concepts.update', $concept) }}" method="POST" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-300">Kode</label>
                                                <input name="code" required maxlength="50" value="{{ $concept->code }}" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base font-bold text-cyan-300 uppercase">
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-300">Urutan</label>
                                                <input name="order" type="number" min="0" value="{{ $concept->order }}" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                                            </div>
                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-300">Parent LO</label>
                                                <select name="learning_objective_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                                                    <option value="">Tanpa LO</option>
                                                    @foreach($selectedCourse->learningObjectives as $objective)
                                                        <option value="{{ $objective->id }}" @selected($concept->learning_objective_id === $objective->id)>{{ $objective->code }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-300">Nama Concept</label>
                                            <input name="name" required maxlength="255" value="{{ $concept->name }}" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base text-white">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-300">Deskripsi</label>
                                            <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-base leading-6 text-slate-200">{{ $concept->description }}</textarea>
                                        </div>
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <label class="flex items-center gap-3 text-sm text-slate-200">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" @checked($concept->is_active) class="h-5 w-5 rounded border-slate-700 bg-slate-900 text-cyan-500">
                                                Concept aktif
                                            </label>
                                            <button class="rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700">Simpan</button>
                                        </div>
                                    </form>
                                    <form action="{{ route('instructor.learning-concepts.destroy', $concept) }}" method="POST" class="mt-4 border-t border-slate-800 pt-4 text-right" onsubmit="return confirm('Hapus concept ini dan seluruh mapping-nya?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm font-semibold text-rose-400 hover:text-rose-300">Hapus Concept</button>
                                    </form>
                                </div>
                            </details>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-700 p-5 text-sm text-slate-400">Belum ada concept. Tambahkan concept pertama dari form di sebelah kiri.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-600 text-lg font-extrabold text-white">3</div>
                <div>
                    <h2 class="text-xl font-extrabold text-white">Atur Concept Prerequisite</h2>
                    <p class="mt-0.5 text-sm text-slate-400">Pilih konsep yang harus dikuasai sebelum siswa mempelajari concept tujuan.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                @forelse($concepts as $concept)
                    @php $prerequisiteIds = $concept->prerequisites->pluck('id')->all(); @endphp
                    <form action="{{ route('instructor.learning-concepts.prerequisites.sync', $concept) }}" method="POST" class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                        @csrf
                        @method('PUT')
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="text-sm font-extrabold text-cyan-300">{{ $concept->code }}</span>
                                <h3 class="mt-1 text-base font-bold text-white">{{ $concept->name }}</h3>
                            </div>
                            <span class="text-xs text-slate-500">{{ count($prerequisiteIds) }} prerequisite</span>
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach($concepts->where('id', '!=', $concept->id) as $candidate)
                                <label class="flex items-start gap-2 rounded-lg border border-slate-800 bg-slate-900/60 p-3 text-sm text-slate-300">
                                    <input type="checkbox" name="learning_concept_ids[]" value="{{ $candidate->id }}" @checked(in_array($candidate->id, $prerequisiteIds, true)) class="mt-0.5 h-4 w-4 rounded border-slate-700 bg-slate-900 text-cyan-500">
                                    <span><strong class="text-white">{{ $candidate->code }}</strong> — {{ $candidate->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button class="mt-4 rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700">Simpan Prerequisite</button>
                    </form>
                @empty
                    <p class="text-sm text-slate-400">Tambahkan concept terlebih dahulu.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 sm:p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-600 text-lg font-extrabold text-white">4</div>
                <div>
                    <h2 class="text-xl font-extrabold text-white">Mapping Content ke Concept</h2>
                    <p class="mt-0.5 text-sm text-slate-400">Materi menunjukkan concept yang diajarkan; soal menunjukkan concept yang diukur.</p>
                </div>
            </div>

            <div class="space-y-8">
                <div>
                    <h3 class="text-lg font-bold text-white">A. Material → Concept</h3>
                    <div class="mt-4 space-y-5">
                        @forelse($selectedCourse->sections as $section)
                            <div>
                                <h4 class="mb-3 text-sm font-bold text-cyan-300">{{ $section->title }}</h4>
                                <div class="space-y-3">
                                    @forelse($section->materials as $material)
                                        @php $materialConceptIds = $material->learningConcepts->pluck('id')->all(); @endphp
                                        <form action="{{ route('instructor.materials.learning-concepts.sync', $material) }}" method="POST" class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                                            @csrf
                                            @method('PUT')
                                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div class="lg:w-1/3">
                                                    <h5 class="font-bold text-white">{{ $material->title }}</h5>
                                                    <p class="mt-1 text-xs text-slate-500">{{ count($materialConceptIds) }} concept terhubung</p>
                                                </div>
                                                <div class="grid flex-1 grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                                    @foreach($concepts as $concept)
                                                        <label class="flex items-start gap-2 text-sm text-slate-300">
                                                            <input type="checkbox" name="learning_concept_ids[]" value="{{ $concept->id }}" @checked(in_array($concept->id, $materialConceptIds, true)) class="mt-0.5 h-4 w-4 rounded border-slate-700 bg-slate-900 text-cyan-500">
                                                            <span><strong class="text-white">{{ $concept->code }}</strong> {{ $concept->name }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="mt-3 text-right"><button class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-bold text-white hover:bg-slate-700">Simpan Mapping Materi</button></div>
                                        </form>
                                    @empty
                                        <p class="text-sm text-slate-500">Belum ada materi pada section ini.</p>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Belum ada section atau materi.</p>
                        @endforelse
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-7">
                    <h3 class="text-lg font-bold text-white">B. Question → Concept</h3>
                    <div class="mt-4 space-y-5">
                        @forelse($selectedCourse->questionBanks as $bank)
                            <div>
                                <h4 class="mb-3 text-sm font-bold text-cyan-300">{{ $bank->title }}</h4>
                                <div class="space-y-3">
                                    @forelse($bank->questions as $question)
                                        @php $questionConceptIds = $question->learningConcepts->pluck('id')->all(); @endphp
                                        <form action="{{ route('instructor.questions.learning-concepts.sync', $question) }}" method="POST" class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                                            @csrf
                                            @method('PUT')
                                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div class="lg:w-1/3">
                                                    <p class="line-clamp-3 text-sm font-semibold leading-6 text-white">{{ $question->question_text }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">Difficulty: {{ $question->difficulty ?? 'belum ditentukan' }}</p>
                                                </div>
                                                <div class="grid flex-1 grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                                    @foreach($concepts as $concept)
                                                        <label class="flex items-start gap-2 text-sm text-slate-300">
                                                            <input type="checkbox" name="learning_concept_ids[]" value="{{ $concept->id }}" @checked(in_array($concept->id, $questionConceptIds, true)) class="mt-0.5 h-4 w-4 rounded border-slate-700 bg-slate-900 text-cyan-500">
                                                            <span><strong class="text-white">{{ $concept->code }}</strong> {{ $concept->name }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="mt-3 text-right"><button class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-bold text-white hover:bg-slate-700">Simpan Mapping Soal</button></div>
                                        </form>
                                    @empty
                                        <p class="text-sm text-slate-500">Belum ada soal pada bank ini.</p>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Belum ada bank soal yang terhubung ke course.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
