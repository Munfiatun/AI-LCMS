<?php

namespace Tests\Unit;

use App\Services\SlideVisualEnrichment;
use PHPUnit\Framework\TestCase;

class SlideVisualEnrichmentTest extends TestCase
{
    public function test_it_extracts_multiple_explicit_labelled_percentages(): void
    {
        $service = new SlideVisualEnrichment;

        $this->assertSame([
            ['label' => 'Memahami materi', 'value' => 82.5],
            ['label' => 'Aktif berdiskusi', 'value' => 76.0],
            ['label' => 'Tuntas evaluasi', 'value' => 91.0],
        ], $service->percentageStats("- Memahami materi: 82,5%\n- Aktif berdiskusi = 76%\n3. Tuntas evaluasi — 91%"));
    }

    public function test_it_does_not_turn_unlabelled_or_single_percentage_into_chart_data(): void
    {
        $service = new SlideVisualEnrichment;

        $this->assertSame([], $service->percentageStats('Sebanyak 75% siswa menyelesaikan latihan.'));
        $this->assertSame([], $service->percentageStats("Ketuntasan: 75%\nPenjelasan lain tanpa angka."));
    }

    public function test_it_rejects_out_of_range_values_and_duplicate_labels(): void
    {
        $service = new SlideVisualEnrichment;

        $this->assertSame([
            ['label' => 'A', 'value' => 80.0],
            ['label' => 'B', 'value' => 60.0],
        ], $service->percentageStats("A: 80%\nA: 90%\nB: 60%\nC: 101%"));
    }
}
