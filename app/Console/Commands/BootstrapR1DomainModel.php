<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\LearningMaterial;
use App\Models\LearningObjective;
use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BootstrapR1DomainModel extends Command
{
    protected $signature = 'research:r1-bootstrap
        {course : Course ID, slug, or exact title}
        {--apply : Persist the proposed R1 domain structure}
        {--map-content : Auto-map only currently-unmapped materials/questions with clear keyword matches}';

    protected $description = 'Preview or bootstrap the R1 research domain model for one course without overwriting manual mappings.';

    /** @var array<string, array{lo:string,name:string,description:string,keywords:list<string>}> */
    private array $conceptDefinitions = [
        'C01' => [
            'lo' => 'LO-01',
            'name' => 'Variabel dan Tipe Data',
            'description' => 'Deklarasi variabel, tipe data, konstanta, dan penggunaan nilai dalam program.',
            'keywords' => ['variabel', 'variable', 'tipe data', 'data type', 'konstanta', 'constant'],
        ],
        'C02' => [
            'lo' => 'LO-01',
            'name' => 'Operator dan Ekspresi',
            'description' => 'Operator aritmatika, perbandingan, logika, dan penyusunan ekspresi program.',
            'keywords' => ['operator', 'ekspresi', 'expression', 'aritmatika', 'perbandingan', 'comparison', 'logika'],
        ],
        'C03' => [
            'lo' => 'LO-02',
            'name' => 'Percabangan',
            'description' => 'Pengambilan keputusan menggunakan if, if-else, switch, dan kondisi logika.',
            'keywords' => ['percabangan', 'if else', 'if-else', 'switch', 'conditional', 'kondisi'],
        ],
        'C04' => [
            'lo' => 'LO-02',
            'name' => 'Perulangan',
            'description' => 'Penggunaan for, while, dan do-while untuk menjalankan instruksi secara berulang.',
            'keywords' => ['perulangan', 'loop', 'looping', 'for loop', 'while', 'do while', 'do-while', 'iterasi'],
        ],
        'C05' => [
            'lo' => 'LO-02',
            'name' => 'Nested Control Structure',
            'description' => 'Penggunaan struktur kontrol bersarang yang menggabungkan percabangan dan/atau perulangan.',
            'keywords' => ['nested', 'bersarang', 'nested loop', 'nested if', 'struktur kontrol bersarang'],
        ],
        'C06' => [
            'lo' => 'LO-03',
            'name' => 'Program Sederhana',
            'description' => 'Integrasi variabel, operator, percabangan, dan perulangan untuk menyelesaikan masalah pemrograman sederhana.',
            'keywords' => ['program sederhana', 'studi kasus', 'integrasi program', 'aplikasi sederhana', 'mini project', 'proyek sederhana'],
        ],
    ];

    /** @var array<string, list<string>> */
    private array $prerequisites = [
        'C02' => ['C01'],
        'C03' => ['C01', 'C02'],
        'C04' => ['C01', 'C02'],
        'C05' => ['C03', 'C04'],
        'C06' => ['C03', 'C04', 'C05'],
    ];

    /** @var array<string, array{title:string,description:string,order:int}> */
    private array $objectiveDefinitions = [
        'LO-01' => [
            'title' => 'Memahami elemen dasar program',
            'description' => 'Siswa mampu memahami dan menggunakan variabel, tipe data, operator, dan ekspresi dasar.',
            'order' => 1,
        ],
        'LO-02' => [
            'title' => 'Menerapkan struktur kontrol pemrograman',
            'description' => 'Siswa mampu menerapkan percabangan, perulangan, dan struktur kontrol bersarang.',
            'order' => 2,
        ],
        'LO-03' => [
            'title' => 'Menyusun program sederhana',
            'description' => 'Siswa mampu mengintegrasikan konsep dasar dan struktur kontrol untuk menyelesaikan masalah sederhana.',
            'order' => 3,
        ],
    ];

    public function handle(): int
    {
        $course = $this->resolveCourse((string) $this->argument('course'));

        if (! $course) {
            $this->error('Course tidak ditemukan. Gunakan ID, slug, atau judul course yang persis sama.');

            return self::FAILURE;
        }

        $this->info("R1 bootstrap target: {$course->title} (#{$course->id})");
        $this->newLine();

        $this->table(
            ['Code', 'Concept', 'Parent LO'],
            collect($this->conceptDefinitions)
                ->map(fn (array $definition, string $code): array => [$code, $definition['name'], $definition['lo']])
                ->values()
                ->all()
        );

        if (! $this->option('apply')) {
            $this->warn('PREVIEW ONLY — belum ada data yang diubah.');
            $this->line('Jalankan ulang dengan --apply untuk membuat LO, concept, dan prerequisite.');
            $this->line('Tambahkan --map-content jika ingin memetakan material/soal yang masih kosong berdasarkan kecocokan kata kunci yang jelas.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($course): void {
            $objectives = $this->ensureObjectives($course);
            $concepts = $this->ensureConcepts($course, $objectives);
            $this->ensurePrerequisites($concepts);

            if ($this->option('map-content')) {
                $this->autoMapMaterials($course, $concepts);
                $this->autoMapQuestions($course, $concepts);
            }
        });

        $this->newLine();
        $this->info('R1 bootstrap selesai. Mapping manual existing tetap dipertahankan.');
        $this->line('Buka Domain Model Readiness untuk memeriksa gate R1. Mapping otomatis tetap wajib direview secara pedagogis sebelum penelitian.');

        return self::SUCCESS;
    }

    private function resolveCourse(string $identifier): ?Course
    {
        return Course::query()
            ->when(ctype_digit($identifier), fn ($query) => $query->whereKey((int) $identifier))
            ->when(! ctype_digit($identifier), fn ($query) => $query->where(function ($query) use ($identifier): void {
                $query->where('slug', $identifier)->orWhere('title', $identifier);
            }))
            ->first();
    }

    /** @return Collection<string, LearningObjective> */
    private function ensureObjectives(Course $course): Collection
    {
        return collect($this->objectiveDefinitions)->mapWithKeys(function (array $definition, string $code) use ($course): array {
            $objective = $course->learningObjectives()->firstOrCreate(
                ['code' => $code],
                [
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'order' => $definition['order'],
                    'is_active' => true,
                ]
            );

            $this->line("LO {$code}: ".($objective->wasRecentlyCreated ? 'dibuat' : 'dipertahankan'));

            return [$code => $objective];
        });
    }

    /** @param Collection<string, LearningObjective> $objectives
     *  @return Collection<string, LearningConcept>
     */
    private function ensureConcepts(Course $course, Collection $objectives): Collection
    {
        return collect($this->conceptDefinitions)->mapWithKeys(function (array $definition, string $code) use ($course, $objectives): array {
            $concept = $course->learningConcepts()->firstOrNew(['code' => $code]);
            $concept->name = $definition['name'];
            $concept->description = $definition['description'];
            $concept->order = (int) substr($code, 1);
            $concept->is_active = true;

            if ($concept->learning_objective_id === null) {
                $concept->learning_objective_id = $objectives[$definition['lo']]->id;
            }

            $wasNew = ! $concept->exists;
            $concept->save();
            $this->line("{$code}: ".($wasNew ? 'dibuat' : 'dirapikan tanpa mengganti parent LO manual'));

            return [$code => $concept];
        });
    }

    /** @param Collection<string, LearningConcept> $concepts */
    private function ensurePrerequisites(Collection $concepts): void
    {
        foreach ($this->prerequisites as $code => $requiredCodes) {
            $concept = $concepts[$code];
            $syncData = [];

            foreach ($requiredCodes as $index => $requiredCode) {
                $syncData[$concepts[$requiredCode]->id] = [
                    'weight' => 1.00,
                    'order' => $index + 1,
                ];
            }

            $concept->prerequisites()->syncWithoutDetaching($syncData);
            $this->line("Prerequisite {$code}: ".implode(', ', $requiredCodes));
        }
    }

    /** @param Collection<string, LearningConcept> $concepts */
    private function autoMapMaterials(Course $course, Collection $concepts): void
    {
        $materials = $course->materials()->with('learningConcepts')->get();
        $unmapped = [];

        foreach ($materials as $material) {
            if ($material->learningConcepts->isNotEmpty()) {
                continue;
            }

            $match = $this->bestMatch($this->materialText($material));

            if ($match === null) {
                $unmapped[] = $material->title;
                continue;
            }

            $material->learningConcepts()->attach($concepts[$match]->id, ['relevance_weight' => 1.00]);
            $this->line("Material '{$material->title}' → {$match}");
        }

        if ($unmapped !== []) {
            $this->warn('Material belum dapat dipetakan otomatis: '.implode(' | ', $unmapped));
        }
    }

    /** @param Collection<string, LearningConcept> $concepts */
    private function autoMapQuestions(Course $course, Collection $concepts): void
    {
        $questions = Question::query()
            ->whereHas('questionBank', fn ($query) => $query->where('course_id', $course->id))
            ->with('learningConcepts')
            ->get();
        $unmapped = [];

        foreach ($questions as $question) {
            if ($question->learningConcepts->isNotEmpty()) {
                continue;
            }

            $match = $this->bestMatch($this->questionText($question));

            if ($match === null) {
                $unmapped[] = '#'.$question->id.' '.Str::limit($question->question_text, 70);
                continue;
            }

            $question->learningConcepts()->attach($concepts[$match]->id, [
                'weight' => 1.00,
                'difficulty_level' => $question->difficulty,
            ]);
            $this->line("Question #{$question->id} → {$match}");
        }

        if ($unmapped !== []) {
            $this->warn('Soal belum dapat dipetakan otomatis dan perlu review:');
            foreach ($unmapped as $item) {
                $this->line('  - '.$item);
            }
        }
    }

    private function materialText(LearningMaterial $material): string
    {
        return Str::lower(implode(' ', array_filter([
            $material->title,
            $material->description,
            $material->content,
        ])));
    }

    private function questionText(Question $question): string
    {
        return Str::lower(implode(' ', array_filter([
            $question->question_text,
            $question->topic,
            $question->explanation,
        ])));
    }

    private function bestMatch(string $text): ?string
    {
        $scores = collect($this->conceptDefinitions)->mapWithKeys(function (array $definition, string $code) use ($text): array {
            $score = collect($definition['keywords'])
                ->filter(fn (string $keyword): bool => Str::contains($text, Str::lower($keyword)))
                ->count();

            return [$code => $score];
        });

        $highest = (int) $scores->max();

        if ($highest === 0) {
            return null;
        }

        $winners = $scores->filter(fn (int $score): bool => $score === $highest)->keys();

        return $winners->count() === 1 ? $winners->first() : null;
    }
}
