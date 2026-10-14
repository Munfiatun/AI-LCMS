# R9 — Demo & Academic Readiness

R9 menyiapkan LMS-WEB agar dapat didemonstrasikan secara konsisten untuk presentasi produk, evaluasi dosen, atau dokumentasi akademik tanpa bergantung pada data acak atau state yang tidak diketahui.

## Prinsip

Golden demo harus:

- memakai data demo yang deterministik dan aman untuk local/testing;
- menunjukkan alur Instructor dan Student secara end-to-end;
- memperlihatkan human-in-the-loop pada AI Slidebook dan AI Quiz;
- menunjukkan perbedaan antara content published dan working revision;
- memiliki enrollment, progress, Quiz attempt, dan analytics yang nyata;
- tidak mengubah production seeder atau membuka akun demo di production;
- tidak membuat klaim hasil usability yang belum benar-benar dikumpulkan pada R8.

## Persiapan lingkungan

Gunakan environment local/testing. Isi password demo pada `.env` lokal:

```env
DEMO_USER_PASSWORD=your-local-demo-password
```

Kemudian siapkan data demo:

```bash
php artisan migrate:fresh
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=LmsDemoSeeder
```

Periksa readiness sebelum presentasi:

```bash
php artisan demo:check
```

Untuk output machine-readable:

```bash
php artisan demo:check --json
```

`demo:check` hanya membaca kondisi aplikasi. Command ini tidak melakukan seeding dan tidak memperbaiki data secara diam-diam.

## Golden demo accounts

Akun demo mengikuti seeder existing:

- Instructor: `instructor@example.com`
- Student: `student@example.com`

Password berasal dari `DEMO_USER_PASSWORD`; jangan menaruh password demo tetap ke repository atau slide presentasi publik.

## Golden course

Course utama:

- Slug: `lms-feature-showcase`
- Title: `LMS Feature Showcase`

Course ini harus memiliki:

- minimal dua learning material published;
- published Slidebook dengan banyak variasi layout;
- revision Slidebook yang masih editable;
- published Quiz dengan jumlah soal konsisten;
- Student demo yang enrolled;
- submitted Quiz attempt;
- progress Student lebih dari 0%;
- data yang cukup untuk membuka analytics tanpa angka palsu.

## Script demo 7–10 menit

### 1. Instructor workflow

1. Login sebagai Instructor.
2. Buka dashboard dan tunjukkan journey pembuatan pembelajaran.
3. Masuk ke `LMS Feature Showcase`.
4. Tunjukkan material published dan draft yang tidak terlihat oleh Student.
5. Buka Slidebook published sebagai preview siswa.
6. Tunjukkan working revision untuk menjelaskan versioning dan human review.
7. Buka Quiz dan tunjukkan bahwa soal AI tetap membutuhkan verifikasi guru.
8. Buka analytics dan Learning Signals untuk memperlihatkan hasil belajar berbasis data nyata.

### 2. Student workflow

1. Login sebagai Student.
2. Buka course yang sudah diikuti.
3. Buka material dan Slidebook published.
4. Tunjukkan Quiz/history hasil belajar.
5. Tunjukkan progress course dan feedback yang tersedia.

### 3. Closing

Tekankan tiga nilai produk:

- **authoring**: guru dapat mengubah materi menjadi pengalaman belajar terstruktur;
- **assessment with human oversight**: AI membantu pembuatan soal tetapi guru tetap memverifikasi;
- **evidence-based follow-up**: hasil belajar dan Learning Signals membantu guru melihat area yang perlu perhatian.

## Academic evidence map

| Klaim | Bukti produk yang dapat ditunjukkan |
| --- | --- |
| AI tidak menggantikan guru | Slidebook/Quiz review state, verification flow, publish guard |
| Materi memiliki versioning | Published Slidebook + working revision |
| Student hanya melihat konten valid | Draft material/Quiz/Slidebook tidak muncul pada flow Student |
| Assessment memiliki provenance | Source slide/source excerpt pada review AI Quiz |
| Sistem mencatat hasil belajar | Quiz attempts, score/history, course progress |
| Analytics tidak sekadar dummy | Instructor analytics dan Learning Signals dihitung dari data attempt/progress |
| UX diuji secara terstruktur | R8 protocol, task metrics, error/confusion observation, SUS report |
| Produk memiliki quality controls | Slidebook Quality Engine, AI question quality guard, release tests, stress guards |

## Batas klaim akademik

Demo menunjukkan bahwa fitur tersedia dan alur dapat dijalankan. Demo bukan bukti bahwa sistem meningkatkan hasil belajar, meningkatkan motivasi, atau unggul secara statistik dibanding sistem lain. Klaim efektivitas pendidikan memerlukan desain penelitian tersendiri.

R8 usability data juga harus dilaporkan sesuai data nyata. Jangan mengisi SUS atau task success dengan data simulasi lalu menyebutnya sebagai hasil responden.

## Pre-presentation checklist

Sebelum presentasi:

1. `git pull` pada `main` terbaru.
2. Jalankan `composer run release:check`.
3. Pastikan `.env` menggunakan local/testing dan `APP_DEBUG` sesuai kebutuhan demo lokal.
4. Seed golden demo bila database demo baru dibuat.
5. Jalankan `php artisan demo:check` sampai semua pemeriksaan PASS.
6. Login sekali sebagai Instructor dan Student untuk memastikan browser/session normal.
7. Pastikan provider AI nyata hanya digunakan bila memang ingin menunjukkan live generation; golden demo utama tidak boleh bergantung pada jaringan/provider agar presentasi tetap stabil.
8. Siapkan jalur fallback berupa data golden demo yang sudah tersimpan, bukan screenshot palsu atau hasil yang dimanipulasi.

R9 dianggap selesai ketika golden demo dapat direproduksi dari repository, readiness check lulus, dan presenter memiliki script serta batas klaim akademik yang jelas.
