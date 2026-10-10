# Deployment Guide — AI-LCMS

Dokumen ini menjadi checklist minimum sebelum AI-LCMS dipasang pada server production.

## 1. Runtime

Minimum yang direkomendasikan untuk release terkunci saat ini:

- PHP 8.4.1+ (`composer.lock` menggunakan Symfony 8.1)
- Composer 2
- Node.js 22 untuk proses build frontend
- Database yang didukung Laravel; SQLite cocok untuk demo kecil, sedangkan deployment multi-user sebaiknya memakai database server yang dikelola dengan backup rutin
- Web server seperti Nginx atau Apache yang mengarah ke folder `public/`

`composer.json` masih mengizinkan PHP `^8.3`, tetapi dependency yang terkunci pada release ini memerlukan PHP minimal 8.4.1. Gunakan PHP 8.4+ untuk deployment yang reproducible.

Document root **harus** mengarah ke `public/`, bukan root repository.

## 2. Environment Production

Buat `.env` langsung di server dan jangan commit file tersebut.

Konfigurasi dasar:

```env
APP_NAME="AI-LCMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.example
```

Generate key pada instalasi baru:

```bash
php artisan key:generate
```

Jangan mengganti `APP_KEY` pada aplikasi production yang sudah memiliki session/data terenkripsi kecuali Anda memahami dampaknya.

Atur database, cache, session, mail, filesystem, dan queue sesuai infrastruktur server. Gunakan password database dan secret yang berbeda dari environment lokal.

## 3. AI Provider

Provider yang didukung aplikasi:

```env
AI_PROVIDER=mock
# atau: openai, groq, gemini
```

`mock` cocok untuk testing/demonstrasi deterministik, tetapi generator AI Quiz membutuhkan provider nyata.

Contoh Groq:

```env
AI_PROVIDER=groq
GROQ_API_KEY=your-secret
GROQ_MODEL=openai/gpt-oss-20b
```

Contoh OpenAI:

```env
AI_PROVIDER=openai
OPENAI_API_KEY=your-secret
OPENAI_MODEL=gpt-4o-mini
```

Contoh Gemini:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=your-secret
GEMINI_MODEL=gemini-1.5-flash
```

Jangan menyimpan API key di source code, GitHub issue, log publik, screenshot, atau frontend JavaScript.

Untuk source fidelity assessment, pertahankan:

```env
AI_ALLOW_EXTERNAL_KNOWLEDGE=false
```

kecuali ada kebutuhan yang secara eksplisit telah diuji.

## 4. Install Dependency

Pada release baru:

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
```

Folder `vendor/`, `node_modules/`, dan `public/build/` tidak perlu di-commit.

## 5. Migration & Seeder Safety

Backup database sebelum migration production.

```bash
php artisan migrate --force
```

Jangan menjalankan `migrate:fresh`, `db:wipe`, atau reset database di production.

Demo user tidak boleh diaktifkan di production. Pastikan:

```env
DEMO_USER_PASSWORD=
```

`DatabaseSeeder` pada environment production hanya memuat data struktural dasar seperti role dan kategori. Data demo hanya dijalankan pada environment `local`/`testing`. Seeder demo berikut juga memiliki guard environment dan akan berhenti tanpa menulis data ketika `APP_ENV=production`:

```text
UserSeeder
CourseSeeder
DemoCourseSeeder
LmsDemoSeeder
WebLearningDummySeeder
```

Meskipun sudah memiliki guard, jangan gunakan seeder demo sebagai bagian dari proses release production. Gunakan seeding production hanya bila memang diperlukan dan sudah direview.

## 6. Laravel Optimization

Setelah `.env`, dependency, migration, dan asset selesai:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika konfigurasi `.env` diubah setelah deployment, cache harus dibangun ulang.

## 7. Storage dan Permission

User web server harus dapat menulis ke:

```text
storage/
bootstrap/cache/
```

Jangan memberi permission `777` secara permanen. Gunakan owner/group server yang tepat.

Jika deployment menggunakan file pada disk `public`, jalankan sekali:

```bash
php artisan storage:link
```

Dokumen privat harus tetap dilayani melalui authorization aplikasi dan tidak dipindahkan sembarangan ke direktori publik.

## 8. Queue

Default `.env.example` memakai:

```env
QUEUE_CONNECTION=database
```

Jika deployment menjalankan job asynchronous, jalankan worker menggunakan process manager (misalnya Supervisor/systemd) dan restart worker setelah release:

```bash
php artisan queue:restart
```

Jika tidak ada worker yang digunakan, pastikan flow yang membutuhkan queue telah diuji dengan konfigurasi deployment yang dipilih.

## 9. Release Quality Gate

Sebelum release, jalankan gate lengkap:

```bash
composer run release:check
```

Gate ini menjalankan seluruh backend test dan frontend production build. Untuk validasi metadata/dependency secara eksplisit, jalankan juga:

```bash
composer validate --strict --no-check-publish
```

Pull Request ke `main` harus melewati GitHub Actions quality gate yang menjalankan validasi Composer, PHPUnit, dan build Vite pada PHP 8.4 dan Node.js 22.

Checklist manual minimum:

- Login Admin, Instructor, dan Student berhasil.
- Instructor dapat membuka kursus, materi, Slidebook, Bank Soal, dan Kuis.
- AI provider production dapat menghasilkan response tanpa menampilkan secret pada error UI.
- AI Quiz tetap menjadi draft sampai review guru selesai.
- Student hanya melihat quiz published pada kursus yang diikuti.
- Retry assessment tidak membocorkan kunci sebelum solusi boleh ditampilkan.
- Upload/download dokumen menghormati authorization.
- Dashboard dan halaman utama responsive pada desktop serta mobile.
- Tidak ada akun, kursus, atau konten demo yang terbentuk di environment production.

## 10. Release Command Example

Contoh urutan release sederhana:

```bash
git pull origin main
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Gunakan strategi deployment yang menyediakan backup dan rollback untuk sistem yang sudah digunakan pengguna nyata.
