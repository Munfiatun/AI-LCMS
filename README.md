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

- PHP 8.3+
- Laravel 13
- SQLite untuk setup lokal default; database lain dapat digunakan melalui konfigurasi Laravel
- Tailwind CSS 4
- Vite 8
- Alpine.js pada layer UI
- PHPUnit 12

Untuk frontend build, Node.js 22 direkomendasikan agar konsisten dengan quality gate repository.

## Setup Lokal

Clone repository lalu jalankan:

```bash
git clone https://github.com/Munfiatun/LMS-WEB.git
cd LMS-WEB
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
