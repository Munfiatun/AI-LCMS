<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\SlidebookQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlidebookQualityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_varied_slidebook_receives_good_quality_score(): void
    {
        $slidebook = $this->slidebook('sma-smk');
        $this->addSlides($slidebook, [
            ['title' => 'Pengantar Sistem Web', 'content' => 'Aplikasi web menghubungkan pengguna dengan layanan melalui browser.', 'layout' => 'cover'],
            ['title' => 'Frontend', 'content' => 'Frontend menangani antarmuka dan interaksi yang dilihat pengguna.', 'layout' => 'concept'],
            ['title' => 'Backend', 'content' => 'Backend menangani logika aplikasi dan pemrosesan data.', 'layout' => 'key-points'],
            ['title' => 'Rangkuman', 'content' => 'Frontend dan backend memiliki tanggung jawab berbeda tetapi saling melengkapi.', 'layout' => 'summary'],
        ]);

        $report = app(SlidebookQualityService::class)->evaluate($slidebook);

        $this->assertSame('good', $report['status']);
        $this->assertSame(100, $report['score']);
        $this->assertSame(0, $report['summary']['critical_count']);
        $this->assertSame(0, $report['summary']['warning_count']);
        $this->assertSame(100, $report['summary']['source_coverage_percent']);
        $this->assertSame(4, $report['summary']['unique_layouts']);
    }

    public function test_smp_slide_with_extreme_text_density_is_blocked(): void
    {
        $slidebook = $this->slidebook('smp');
        $this->addSlides($slidebook, [[
            'title' => 'Materi Terlalu Padat',
            'content' => implode(' ', array_fill(0, 170, 'penjelasan')),
            'layout' => 'reading',
        ]]);

        $report = app(SlidebookQualityService::class)->evaluate($slidebook);

        $this->assertSame('blocked', $report['status']);
        $this->assertContains('text_density_critical', collect($report['issues'])->pluck('code')->all());
        $this->assertNotEmpty($report['blocking_messages']);
    }

    public function test_smp_slide_with_eight_points_is_blocked(): void
    {
        $slidebook = $this->slidebook('smp');
        $points = collect(range(1, 8))->map(fn (int $number): string => '<li>Poin pembelajaran '.$number.'</li>')->implode('');
        $this->addSlides($slidebook, [[
            'title' => 'Terlalu Banyak Poin',
            'content' => '<ul>'.$points.'</ul>',
            'layout' => 'key-points',
        ]]);

        $report = app(SlidebookQualityService::class)->evaluate($slidebook);

        $this->assertSame('blocked', $report['status']);
        $this->assertContains('too_many_points_critical', collect($report['issues'])->pluck('code')->all());
    }

    public function test_missing_source_reference_is_warning_not_blocker_for_legacy_slidebook(): void
    {
        $slidebook = $this->slidebook('sma-smk');
        $slidebook->slides()->create([
            'title' => 'Slide Lama',
            'content' => 'Konten lama tetap dapat diperiksa dan disetujui guru.',
            'layout' => 'concept',
            'order' => 1,
            'status' => 'active',
            'needs_review' => false,
            'source_reference' => null,
        ]);

        $report = app(SlidebookQualityService::class)->evaluate($slidebook);

        $this->assertSame('needs_attention', $report['status']);
        $this->assertSame(0, $report['summary']['critical_count']);
        $this->assertContains('missing_source_reference', collect($report['issues'])->pluck('code')->all());
        $this->assertContains('source_coverage_missing', collect($report['issues'])->pluck('code')->all());
        $this->assertSame([], $report['blocking_messages']);
    }

    public function test_repeated_layout_and_visual_opportunities_are_reported_as_warnings(): void
    {
        $slidebook = $this->slidebook('sma-smk');
        $this->addSlides($slidebook, [
            ['title' => 'Alur', 'content' => 'Langkah pertama menerima request, selanjutnya server memproses request.', 'layout' => 'reading'],
            ['title' => 'Perbandingan', 'content' => 'Perbedaan frontend dan backend terlihat dari tanggung jawab masing-masing.', 'layout' => 'reading'],
            ['title' => 'Kode', 'content' => 'function prosesData() { return true; }', 'layout' => 'reading'],
            ['title' => 'Data', 'content' => 'Kelompok A mencapai 80% sedangkan kelompok B mencapai 65%.', 'layout' => 'reading'],
        ]);

        $report = app(SlidebookQualityService::class)->evaluate($slidebook);
        $codes = collect($report['issues'])->pluck('code')->all();

        $this->assertSame('needs_attention', $report['status']);
        $this->assertSame(0, $report['summary']['critical_count']);
        $this->assertContains('layout_monotony', $codes);
        $this->assertContains('process_visual_opportunity', $codes);
        $this->assertContains('comparison_visual_opportunity', $codes);
        $this->assertContains('code_visual_opportunity', $codes);
        $this->assertContains('data_visual_opportunity', $codes);
    }

    public function test_normal_rich_text_markup_is_not_mistaken_for_source_code(): void
    {
        $slidebook = $this->slidebook('sma-smk');
        $this->addSlides($slidebook, [[
            'title' => 'Konsep Web',
            'content' => '<p>Frontend menampilkan antarmuka pengguna.</p><ul><li>Browser menampilkan halaman.</li><li>Backend memproses data.</li></ul>',
            'layout' => 'concept',
        ]]);

        $report = app(SlidebookQualityService::class)->evaluate($slidebook);

        $this->assertNotContains('code_visual_opportunity', collect($report['issues'])->pluck('code')->all());
    }

    /** @return Slidebook */
    private function slidebook(string $audience): Slidebook
    {
        $role = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $teacher = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'content' => 'Aplikasi web memiliki frontend, backend, browser, server, data, dan antarmuka pengguna.',
        ]);

        return Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Slidebook Uji',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $teacher->id,
            'design_settings' => ['preset' => 'indigo-dark', 'audience' => $audience],
        ]);
    }

    /** @param list<array{title:string,content:string,layout:string}> $slides */
    private function addSlides(Slidebook $slidebook, array $slides): void
    {
        foreach ($slides as $index => $slide) {
            $slidebook->slides()->create([
                ...$slide,
                'order' => $index + 1,
                'status' => 'active',
                'needs_review' => false,
                'source_reference' => ['manual' => true],
            ]);
        }
    }
}
