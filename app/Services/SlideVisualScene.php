<?php

namespace App\Services;

class SlideVisualScene
{
    /**
     * Educational scenes are intentionally deterministic and asset-free.
     * They describe relationships that the Blade renderer can draw as SVG.
     *
     * @var array<string, array{label: string, keywords: list<string>}>
     */
    private const SCENES = [
        'web-flow' => [
            'label' => 'Alur browser, internet, server, dan data',
            'keywords' => ['browser', 'website', 'web', 'http', 'request', 'response', 'server', 'api', 'url', 'route', 'client'],
        ],
        'database' => [
            'label' => 'Hubungan aplikasi dengan basis data',
            'keywords' => ['database', 'basis data', 'sql', 'mysql', 'postgresql', 'tabel', 'record', 'primary key'],
        ],
        'security' => [
            'label' => 'Perlindungan akun dan aplikasi',
            'keywords' => ['keamanan', 'security', 'password', 'authentication', 'authorization', 'autentikasi', 'otorisasi', 'csrf', 'enkripsi', 'session'],
        ],
        'network' => [
            'label' => 'Perangkat yang saling terhubung melalui jaringan',
            'keywords' => ['jaringan', 'network', 'internet', 'router', 'wifi', 'wi-fi', 'ip address', 'subnet', 'node'],
        ],
        'ai' => [
            'label' => 'Manusia, data, dan sistem kecerdasan buatan',
            'keywords' => ['kecerdasan buatan', 'artificial intelligence', 'machine learning', 'deep learning', 'neural', ' ai ', 'model ai'],
        ],
        'code' => [
            'label' => 'Kode diproses menjadi keluaran aplikasi',
            'keywords' => ['pemrograman', 'programming', 'kode', 'code', 'algoritma', 'variabel', 'fungsi', 'function', 'laravel', 'php', 'javascript'],
        ],
        'learning' => [
            'label' => 'Siswa menjelajah materi dan aktivitas belajar',
            'keywords' => ['belajar', 'pembelajaran', 'siswa', 'guru', 'materi', 'sekolah', 'kelas', 'kompetensi', 'tujuan pembelajaran', 'quiz', 'kuis'],
        ],
    ];

    /**
     * Infer one meaningful educational scene from the slide source.
     * Returns null when a visual relationship would not add learning value.
     *
     * @return array{id: string, label: string}|null
     */
    public function sceneFor(string $title, ?string $content, ?string $layout = null): ?array
    {
        $layout = strtolower(trim((string) $layout));
        $text = ' '.mb_strtolower(trim($title.' '.($content ?? ''))).' ';

        $bestId = null;
        $bestScore = 0;

        foreach (self::SCENES as $id => $scene) {
            $score = 0;
            foreach ($scene['keywords'] as $keyword) {
                if (str_contains($text, mb_strtolower($keyword))) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $id;
            }
        }

        if ($bestId !== null && $bestScore > 0) {
            return ['id' => $bestId, 'label' => self::SCENES[$bestId]['label']];
        }

        // Hero slides still benefit from a simple learning journey visual, while
        // prose-heavy slides stay text-first when there is no semantic match.
        if (in_array($layout, ['cover', 'section-divider', 'closing'], true)) {
            return ['id' => 'learning', 'label' => self::SCENES['learning']['label']];
        }

        return null;
    }
}
