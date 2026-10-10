<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCourseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('DemoCourseSeeder only runs in local/testing environments.');
            return;
        }

        $instructor = User::whereHas('role', fn ($q) => $q->where('name', 'instructor'))->first();
        if (! $instructor) {
            return;
        }

        $categories = [
            'Pemrograman Web' => Category::firstOrCreate(['name' => 'Pemrograman Web', 'slug' => 'pemrograman-web']),
            'Database' => Category::firstOrCreate(['name' => 'Database', 'slug' => 'database']),
            'Jaringan' => Category::firstOrCreate(['name' => 'Jaringan', 'slug' => 'jaringan']),
            'UI/UX' => Category::firstOrCreate(['name' => 'UI/UX', 'slug' => 'ui-ux']),
            'AI & IoT' => Category::firstOrCreate(['name' => 'AI & IoT', 'slug' => 'ai-iot']),
        ];

        $demoCourses = [
            [
                'title' => 'Dasar Pemrograman Web',
                'description' => 'Pelajari cara kerja web serta dasar HTML, CSS, dan JavaScript.',
                'category' => 'Pemrograman Web',
            ],
            [
                'title' => 'HTML & CSS Fundamental',
                'description' => 'Pelajari struktur dasar halaman web dan cara memberikan styling yang menarik menggunakan CSS.',
                'category' => 'Pemrograman Web',
            ],
            [
                'title' => 'JavaScript Dasar',
                'description' => 'Pelajari variabel, function, DOM, event, dan asynchronous JavaScript untuk interaktivitas web.',
                'category' => 'Pemrograman Web',
            ],
            [
                'title' => 'Laravel Fundamental',
                'description' => 'Pelajari routing, controller, Blade, database, dan autentikasi menggunakan framework Laravel.',
                'category' => 'Pemrograman Web',
            ],
            [
                'title' => 'Database MySQL',
                'description' => 'Pelajari desain database relasional, dasar query SQL, joins, dan optimasi query dasar.',
                'category' => 'Database',
            ],
            [
                'title' => 'Dasar Jaringan Komputer',
                'description' => 'Pelajari IP address, topologi, perangkat jaringan, dan konektivitas dasar jaringan komputer.',
                'category' => 'Jaringan',
            ],
            [
                'title' => 'UI/UX Dasar',
                'description' => 'Pelajari prinsip antarmuka dan pengalaman pengguna untuk merancang aplikasi yang intuitif.',
                'category' => 'UI/UX',
            ],
            [
                'title' => 'Internet of Things Dasar',
                'description' => 'Pelajari penggunaan sensor, mikrokontroler, dan komunikasi antar perangkat IoT.',
                'category' => 'AI & IoT',
            ],
            [
                'title' => 'Pemrograman Python',
                'description' => 'Pelajari sintaks Python, kontrol alur, function, dan struktur data yang paling sering digunakan.',
                'category' => 'Pemrograman Web',
            ],
            [
                'title' => 'Keamanan Aplikasi Web',
                'description' => 'Pelajari konsep autentikasi, authorization, validasi input, dan penanganan kerentanan keamanan dasar web.',
                'category' => 'Jaringan',
            ],
        ];

        foreach ($demoCourses as $index => $data) {
            $course = Course::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'instructor_id' => $instructor->id,
                    'category_id' => $categories[$data['category']]->id,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'status' => Course::STATUS_PUBLISHED,
                    'published_at' => now()->subDays(($index % 10) + 1),
                ]
            );

            if (! $course->enrollment_code) {
                $course->generateEnrollmentCode();
            }

            if ($course->sections()->count() === 0) {
                $section = $course->sections()->create([
                    'title' => 'Modul 1: Pendahuluan',
                    'description' => 'Pendahuluan dan konsep dasar dari kelas ini.',
                    'order' => 1,
                    'status' => 'active',
                ]);

                for ($i = 1; $i <= 3; $i++) {
                    $section->materials()->create([
                        'title' => 'Materi '.$i,
                        'slug' => Str::slug($data['title'].' materi '.$i),
                        'description' => 'Deskripsi materi ke-'.$i.' untuk kelas '.$data['title'],
                        'content' => '<p>Konten dari materi ke-'.$i.' berjalan di sini.</p>',
                        'duration_minutes' => 15 * $i,
                        'order' => $i,
                        'status' => 'published',
                        'published_at' => now(),
                    ]);
                }
            }
        }
    }
}
