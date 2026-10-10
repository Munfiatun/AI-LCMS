<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('CourseSeeder demo content only runs in local/testing environments.');
            return;
        }

        $instructor = User::where('email', 'instructor@example.com')->first();
        if (! $instructor) {
            return;
        }

        $webDevCategory = Category::where('slug', 'web-development')->first();
        $aiCategory = Category::where('slug', 'artificial-intelligence')->first();

        // 1. Published Course: Mastering Modern Laravel 13 & AI Architecture
        $course1 = Course::updateOrCreate(
            ['slug' => 'mastering-modern-laravel-13-ai-architecture'],
            [
                'instructor_id' => $instructor->id,
                'category_id' => $aiCategory?->id,
                'title' => 'Mastering Modern Laravel 13 & AI Architecture',
                'description' => 'Pelajari cara merancang arsitektur aplikasi Laravel bertenaga AI dengan prinsip Human-in-the-Loop, pemrosesan antrean asinkron, dan sistem penilaian kuis server-authoritative.',
                'status' => Course::STATUS_PUBLISHED,
                'published_at' => now()->subDays(3),
            ]
        );

        // Section 1
        $section1 = $course1->sections()->updateOrCreate(
            ['order' => 1],
            [
                'title' => 'Bab 1: Pengenalan & Prinsip Inti LCMS',
                'description' => 'Memahami fondasi arsitektur sistem dan batas kewenangan AI.',
                'status' => 'active',
            ]
        );

        $section1->materials()->updateOrCreate(
            ['slug' => 'arsitektur-sistem-dan-prinsip-human-in-the-loop'],
            [
                'title' => 'Arsitektur Sistem & Prinsip Human-in-the-Loop',
                'description' => 'Mengapa AI tidak boleh mempublikasikan konten secara otomatis tanpa review pengajar.',
                'content' => '<p>Pada bab ini kita mempelajari bahwa AI bertindak sebagai <em>drafting partner</em>. Tanggung jawab kurasi dan validasi kebenaran materi tetap berada di tangan pengajar.</p>',
                'duration_minutes' => 15,
                'order' => 1,
                'status' => LearningMaterial::STATUS_PUBLISHED,
                'published_at' => now()->subDays(3),
            ]
        );

        $section1->materials()->updateOrCreate(
            ['slug' => 'ekstraksi-dokumen-dan-storage-privat'],
            [
                'title' => 'Ekstraksi Dokumen & Storage Privat',
                'description' => 'Mekanisme penyimpanan file PDF/DOCX yang aman pada disk privat.',
                'content' => '<p>Dokumen materi harus disimpan dalam direktori privat <code>storage/app/private</code> dan tidak boleh diakses langsung tanpa otorisasi Policy.</p>',
                'duration_minutes' => 20,
                'order' => 2,
                'status' => LearningMaterial::STATUS_PUBLISHED,
                'published_at' => now()->subDays(2),
            ]
        );

        // Section 2
        $section2 = $course1->sections()->updateOrCreate(
            ['order' => 2],
            [
                'title' => 'Bab 2: Slidebook Interaktif & Kuis Cerdas',
                'description' => 'Penyusunan slide materi dan pencegahan kecurangan asesmen.',
                'status' => 'active',
            ]
        );

        $section2->materials()->updateOrCreate(
            ['slug' => 'otomasi-slidebook-dan-review-side-by-side'],
            [
                'title' => 'Otomasi Slidebook & Review Side-by-Side',
                'description' => 'Bagaimana guru meninjau hasil generasi slide dari teks sumber dokumen.',
                'content' => '<p>Antarmuka review menyandingkan teks dokumen asli dengan draft slide AI untuk menjamin fidelitas sumber.</p>',
                'duration_minutes' => 25,
                'order' => 1,
                'status' => LearningMaterial::STATUS_PUBLISHED,
                'published_at' => now()->subDays(1),
            ]
        );

        // 2. Draft Course: Arsitektur Microservices dengan PHP 8.5
        Course::updateOrCreate(
            ['slug' => 'arsitektur-microservices-dengan-php-8-5'],
            [
                'instructor_id' => $instructor->id,
                'category_id' => $webDevCategory?->id,
                'title' => 'Arsitektur Microservices dengan PHP 8.5',
                'description' => 'Panduan komprehensif memisahkan monolit menjadi modular services dengan queue dan event-driven messaging.',
                'status' => Course::STATUS_DRAFT,
                'published_at' => null,
            ]
        );
    }
}
