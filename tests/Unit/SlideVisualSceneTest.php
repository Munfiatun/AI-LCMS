<?php

namespace Tests\Unit;

use App\Services\SlideVisualScene;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SlideVisualSceneTest extends TestCase
{
    #[DataProvider('sceneCases')]
    public function test_scene_is_selected_from_learning_semantics(string $title, string $content, string $layout, string $expected): void
    {
        $scene = (new SlideVisualScene())->sceneFor($title, $content, $layout);

        $this->assertNotNull($scene);
        $this->assertSame($expected, $scene['id']);
        $this->assertNotSame('', $scene['label']);
    }

    public static function sceneCases(): array
    {
        return [
            'web request' => ['Cara Kerja Internet', 'Browser mengirim HTTP request ke server lalu menerima response.', 'process', 'web-flow'],
            'database' => ['Basis Data', 'Primary key mengidentifikasi record pada tabel database.', 'concept', 'database'],
            'security' => ['Keamanan Akun', 'Authentication, password, session, dan authorization melindungi pengguna.', 'key-points', 'security'],
            'network' => ['Jaringan Komputer', 'Router menghubungkan node melalui jaringan WiFi.', 'diagram', 'network'],
            'ai' => ['Kecerdasan Buatan', 'Machine learning menggunakan data untuk melatih model AI.', 'concept', 'ai'],
            'code' => ['Algoritma', 'Kode pemrograman menggunakan variabel dan fungsi.', 'example', 'code'],
            'learning' => ['Tujuan Pembelajaran', 'Siswa mempelajari materi lalu mengerjakan kuis.', 'key-points', 'learning'],
        ];
    }

    public function test_cover_without_keyword_gets_learning_scene(): void
    {
        $scene = (new SlideVisualScene())->sceneFor('Topik Baru', 'Pengantar singkat.', 'cover');

        $this->assertSame('learning', $scene['id']);
    }

    public function test_plain_reading_without_visual_semantics_stays_text_first(): void
    {
        $this->assertNull((new SlideVisualScene())->sceneFor('Catatan', 'Sebuah paragraf umum tanpa relasi visual khusus.', 'reading'));
    }
}
