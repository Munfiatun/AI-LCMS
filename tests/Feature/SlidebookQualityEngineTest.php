<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\SlidebookQualityService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SlidebookQualityEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Course $course;

    private CourseSection $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->teacher = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail()->id,
            'is_active' => true,
        ]);
        $this->course = Course::factory()->create([
            'instructor_id' => $this->teacher->id,
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $this->section = CourseSection::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
    }

    public function test_teacher_can_request_structured_quality_report_for_current_slidebook(): void
    {
        [$material, $slidebook] = $this->makeSlidebook('smp');
        $slidebook->slides()->create([
            'title' => 'Konsep Dasar',
            'content' => 'Materi ringkas yang dapat dipahami siswa dan mudah diperiksa guru.',
            'layout' => 'concept',
            'order' => 1,
            'source_reference' => ['manual' => true],
            'needs_review' => false,
            'status' => 'active',
        ]);

        $this->actingAs($this->teacher)
            ->getJson(route('instructor.materials.slidebook.review', $material).'?quality=1')
            ->assertOk()
            ->assertJsonStructure([
                'score',
                'status',
                'audience',
                'summary' => [
                    'slides',
                    'critical_count',
                    'warning_count',
                    'needs_review_count',
                    'source_coverage_percent',
                    'unique_layouts',
                ],
                'issues',
                'blocking_messages',
            ])
            ->assertJsonPath('audience', 'smp')
            ->assertJsonPath('summary.slides', 1);
    }

    public function test_extremely_dense_slide_is_blocked_before_teacher_approval(): void
    {
        [, $slidebook] = $this->makeSlidebook('sma-smk');
        $slidebook->slides()->create([
            'title' => 'Arsitektur Aplikasi Web',
            'content' => implode(' ', array_fill(0, 300, 'penjelasan')),
            'layout' => 'reading',
            'order' => 1,
            'source_reference' => ['manual' => true],
            'needs_review' => false,
            'status' => 'active',
        ]);

        $this->actingAs($this->teacher)
            ->postJson(route('instructor.slidebooks.approve', $slidebook))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slidebook');

        $this->assertNull($slidebook->fresh()->approved_by);
        $this->assertSame(Slidebook::STATUS_REVIEW, $slidebook->fresh()->status);

        $report = app(SlidebookQualityService::class)->evaluate($slidebook);
        $this->assertSame('blocked', $report['status']);
        $this->assertContains('text_density_critical', collect($report['issues'])->pluck('code')->all());
    }

    public function test_legacy_slide_without_source_reference_remains_approvable_with_warning(): void
    {
        [, $slidebook] = $this->makeSlidebook('sma-smk');
        $slidebook->slides()->create([
            'title' => 'Slide Versi Lama',
            'content' => 'Konten pembelajaran lama yang tetap jelas, ringkas, dan valid.',
            'layout' => 'concept',
            'order' => 1,
            'source_reference' => null,
            'needs_review' => false,
            'status' => 'active',
        ]);

        $report = app(SlidebookQualityService::class)->evaluate($slidebook);
        $this->assertSame(0, $report['summary']['critical_count']);
        $this->assertNotEmpty($report['issues']);

        $this->actingAs($this->teacher)
            ->post(route('instructor.slidebooks.approve', $slidebook))
            ->assertSessionHas('success');

        $this->assertSame(Slidebook::STATUS_DRAFT, $slidebook->fresh()->status);
        $this->assertSame($this->teacher->id, $slidebook->fresh()->approved_by);
    }

    public function test_quality_engine_does_not_false_block_twelve_varied_learning_subjects(): void
    {
        $subjects = [
            'Basis Data',
            'Jaringan Komputer',
            'Sistem Operasi',
            'Matematika Diskrit',
            'Elektronika Dasar',
            'Keselamatan Kerja',
            'Desain Antarmuka',
            'Kecerdasan Buatan',
            'Algoritma',
            'Kewirausahaan',
            'Bahasa Indonesia',
            'Proyek Informatika',
        ];

        foreach ($subjects as $subjectIndex => $subject) {
            [, $slidebook] = $this->makeSlidebook($subjectIndex % 2 === 0 ? 'sma-smk' : 'general', $subject);
            $contents = [
                $subject.' memiliki konsep dasar yang perlu dipahami sebelum mempelajari topik lanjutan.',
                'Istilah penting dijelaskan secara ringkas agar siswa dapat mengenali fungsi dan ruang lingkup pembahasan.',
                'Penerapan konsep dipelajari melalui penjelasan yang tetap bersumber pada materi pembelajaran.',
                'Rangkuman membantu siswa mengingat gagasan utama setelah mempelajari seluruh bagian.',
            ];
            $layouts = ['cover', 'concept', 'key-points', 'summary'];

            foreach ($contents as $index => $content) {
                $slidebook->slides()->create([
                    'title' => $index === 0 ? $subject : 'Bagian '.($index + 1),
                    'content' => $content,
                    'layout' => $layouts[$index],
                    'order' => $index + 1,
                    'source_reference' => ['manual' => true],
                    'needs_review' => false,
                    'status' => 'active',
                ]);
            }

            $report = app(SlidebookQualityService::class)->evaluate($slidebook);
            $this->assertNotSame('blocked', $report['status'], 'False block on '.$subject);
            $this->assertSame(0, $report['summary']['critical_count'], 'Unexpected critical issue on '.$subject);
        }
    }

    /** @return array{LearningMaterial, Slidebook} */
    private function makeSlidebook(string $audience, string $title = 'Materi Uji'): array
    {
        $material = LearningMaterial::create([
            'section_id' => $this->section->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => 'Materi pembelajaran untuk pemeriksaan kualitas.',
            'content' => 'Materi sumber berisi konsep pembelajaran yang digunakan guru untuk menyusun Slidebook.',
            'duration_minutes' => 20,
            'order' => $this->section->materials()->count() + 1,
            'status' => LearningMaterial::STATUS_DRAFT,
        ]);

        $slidebook = Slidebook::create([
            'material_id' => $material->id,
            'title' => $title,
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->teacher->id,
            'design_settings' => ['preset' => 'indigo-dark', 'audience' => $audience],
        ]);

        return [$material, $slidebook];
    }
}
