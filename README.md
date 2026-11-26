# AI-LCMS

AI-LCMS adalah Learning Content Management System berbasis Laravel untuk pengelolaan kursus, materi, Slidebook berbantuan AI, bank soal, kuis, progres belajar, dan analitik pembelajaran. Sistem memiliki tiga role utama: **Admin**, **Instructor/Guru**, dan **Student/Siswa**.

## Fitur Utama

- Manajemen kategori, kursus, section, materi, enrollment, dan progres belajar.
- Upload serta ekstraksi dokumen pembelajaran.
- AI Slidebook dengan sistem tema, layout, authoring, dan review guru.
- Bank soal dan ekstraksi soal berbantuan AI dengan provenance jawaban.
- AI Quiz dari Slidebook dengan quality gate sebelum publikasi.
- Source evidence untuk membantu guru memverifikasi soal AI terhadap slide sumber.
- Randomisasi soal/pilihan, attempt, timer, penilaian, dan retry guidance.
- Dashboard analitik Instructor serta learning dashboard Student.
- Provider AI: `mock`, `OpenAI`, `Groq`, dan `Gemini`.

## Stack

- PHP 8.4.1+ untuk release terkunci saat ini (`composer.lock` menggunakan Symfony 8.1)
- Laravel 13
- SQLite untuk setup lokal default; database lain dapat digunakan melalui konfigurasi Laravel
- Tailwind CSS 4
- Vite 8
- Alpine.js pada layer UI
- PHPUnit 12

`composer.json` masih mengizinkan PHP `^8.3`, tetapi dependency yang terkunci pada release saat ini membutuhkan PHP minimal 8.4.1. Gunakan PHP 8.4+ agar hasil lokal, CI, dan deployment konsisten.

Untuk frontend build, Node.js 22 direkomendasikan agar konsisten dengan quality gate repository.

## Setup Lokal

Clone repository lalu jalankan:

```bash
git clone https://github.com/Munfiatun/AI-LCMS.git
cd AI-LCMS
composer install
cp .env.example .env
php artisan key:generate
```

Setup default menggunakan SQLite. Pastikan file database tersedia lalu jalankan migration:

```bash
mkdir -p database
touch database/database.sqlite
php artisan migrate
```

Install dependency frontend dan build asset:

```bash
npm ci
npm run build
```

Jalankan aplikasi:

```bash
php artisan serve
```

Aplikasi lokal tersedia secara default di `http://127.0.0.1:8000`.

Alternatif setup satu perintah tersedia melalui:

```bash
composer run setup
```

## Konfigurasi AI

Secara default:

```env
AI_PROVIDER=mock
```

Provider `mock` digunakan untuk alur lokal/testing yang deterministik dan bebas biaya. **AI Quiz membutuhkan provider nyata**: `openai`, `groq`, atau `gemini`.

Contoh Groq:

```env
AI_PROVIDER=groq
GROQ_API_KEY=
GROQ_MODEL=openai/gpt-oss-20b
GROQ_TIMEOUT=60
```

Contoh OpenAI:

```env
AI_PROVIDER=openai
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
```

Contoh Gemini:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=
GEMINI_MODEL=gemini-1.5-flash
```

Setelah mengubah `.env`, bersihkan cache konfigurasi:

```bash
php artisan optimize:clear
```

Jangan commit API key atau isi `.env`. File `.env` sudah diabaikan oleh Git.

## Demo Lokal

Demo user hanya dibuat pada environment `local` atau `testing` ketika `DEMO_USER_PASSWORD` diisi.

```env
DEMO_USER_PASSWORD=your-local-demo-password
```

Kemudian:

```bash
php artisan db:seed
```

Akun demo yang tersedia:

| Role | Email |
| --- | --- |
| Admin | `admin@example.com` |
| Instructor | `instructor@example.com` |
| Student | `student@example.com` |

Semua akun menggunakan nilai `DEMO_USER_PASSWORD` yang Anda tentukan sendiri. Jangan aktifkan demo credential di production.

`DatabaseSeeder` hanya menjalankan data demo pada environment `local`/`testing`. Seeder demo eksplisit seperti `CourseSeeder`, `DemoCourseSeeder`, `LmsDemoSeeder`, dan `WebLearningDummySeeder` juga memiliki guard environment sehingga tidak menulis data demo ketika aplikasi berjalan sebagai `production`.

## R5 — Paket Demo Siap Uji *Quality in Use* (2011 dan 2023)

Untuk melakukan pengujian pengalaman pengguna, gunakan **branch penelitian R5**, **bukan \`main\`**. Data yang disediakan di bawah hanya **sintetis** untuk memastikan fitur berfungsi sebelum peserta nyata dilibatkan.

**Instalasi baru di Mac/komputer penguji:**

1. Checkout \`research/r5-ui-ux-simplification\` dan ikuti **Setup Lokal** di atas (PHP 8.4+, Node 22, Composer).
2. Gunakan **database SQLite baru yang kosong khusus demo**, bukan database berisi catatan siswa. Pada \`.env\` set \`APP_ENV=local\`, \`DB_CONNECTION=sqlite\`, \`DB_DATABASE=/path/absolut/ke/database/r5-demo.sqlite\`, \`DEMO_USER_PASSWORD\` dengan password demo buatan sendiri, dan \`AI_PROVIDER=mock\`. Buat file SQLite kosong dengan \`touch\` pada lokasi tersebut.
3. Jalankan:

\`\`\`bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan demo:check --json
npm ci
npm run build
php artisan serve
\`\`\`

4. Kunjungi \`http://127.0.0.1:8000/login\`, lalu gunakan salah satu akun berikut dan password yang kamu set pada \`DEMO_USER_PASSWORD\`:

| Peran uji | Akun demo | Kondisi |
| --- | --- | --- |
| Guru | \`instructor@example.com\` | Membuat/meninjau kursus, materi, kuis, diagnostik dan mastery |
| Siswa adaptif | \`adaptive-demo@example.com\` | Terdaftar pada **LMS Feature Showcase**, mendapat rekomendasi yang mengarah ke materi |
| Siswa kontrol | \`control-demo@example.com\` | Terdaftar pada kursus **yang sama**, tidak melihat intervensi adaptif |
| Siswa biasa | \`student@example.com\` | Golden demo lama dan histori pembelajaran |

**Sebelum uji pengguna:** pastikan akun guru dan dua akun siswa dapat login; materi Slidebook terbit dan Quiz Web Fundamentals tampil; siswa adaptif melihat rekomendasi *Alur HTTP Request dan Response*; siswa kontrol tetap bisa belajar tetapi halaman rekomendasinya kosong. Login, klik jalur belajar, uji akses role serta viewport ponsel sudah diverifikasi otomatis oleh [R5 Real Browser Readiness](https://github.com/Munfiatun/AI-LCMS/actions/workflows/r5-browser-readiness.yml).

**Keamanan:** \`R5PilotDemoSeeder\` dan akun demo hanya berjalan di \`local/testing\` dengan \`DEMO_USER_PASSWORD\`; tidak aktif pada \`production\`. Mastery yang ditanam pada akun demo adalah **nilai ilustrasi**, bukan hasil asesmen riil. Jangan menjalankan \`migrate:fresh\`, \`db:wipe\` atau \`db:seed\` ulang pada database pengujian yang sudah berisi catatan hasil pengguna. Untuk reset demo, gunakan database **terpisah yang sengaja dikosongkan**.

Protokol observasi ISO ada di \`docs/research/R5_ISO_QUALITY_IN_USE_2011_2023_PILOT_PROTOCOL_20261126.md\`. Evaluasi *quality in use* membandingkan **ISO/IEC 25010:2011** dengan **ISO/IEC 25019:2023**. Pengumpulan dan analisis hasil responden merupakan tahap yang terpisah dari kesiapan software; seluruh pengukuran awal harus merekam SHA aplikasi dan konteks pengujian.

## Quality Gate

Sebelum merge atau deployment, jalankan gate lengkap:

```bash
composer run release:check
```

Perintah tersebut membersihkan cache konfigurasi untuk test, menjalankan seluruh PHPUnit suite, lalu menjalankan production frontend build. Alternatif manual:

```bash
php artisan test
npm run build
```

Test suite menggunakan SQLite in-memory melalui `phpunit.xml`, sehingga tidak menggunakan database development lokal.

Repository juga memiliki GitHub Actions quality gate untuk memvalidasi metadata Composer, menjalankan backend test, dan menjalankan frontend production build pada Pull Request ke `main`.

## Deployment

Panduan deployment dan checklist production tersedia di [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

Ringkasnya, production wajib menggunakan:

```env
APP_ENV=production
APP_DEBUG=false
```

Gunakan secret yang berbeda dari environment lokal, jalankan migration dengan `--force`, build asset production, lalu cache konfigurasi Laravel setelah semua environment variable benar.

## Arsitektur AI Assessment

AI diposisikan sebagai **assistant**, bukan final authority. Untuk quiz yang dibuat dari Slidebook:

1. AI membuat draft berdasarkan materi Slidebook.
2. Sistem memvalidasi struktur output dan menyimpan bukti slide sumber.
3. Soal hasil inferensi diberi status review.
4. Guru memeriksa pertanyaan, opsi, kunci, pembahasan, dan referensi materi.
5. Quiz baru dapat diterbitkan setelah quality gate terpenuhi.
6. Siswa tidak melihat solusi lengkap selama masih memiliki kesempatan retry; sistem mengarahkan siswa kembali ke materi sumber.

## Branch Development

Gunakan branch fitur dan Pull Request ke `main`. Branch `main` ditujukan sebagai baseline yang sudah melewati regression test dan production build.
