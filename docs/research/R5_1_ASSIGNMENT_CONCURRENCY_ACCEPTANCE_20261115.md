# R5.1 — Assignment Lock Ordering and Concurrency Acceptance

**Tanggal:** 15 November 2026  
**Branch dasar:** `research/r5-ui-ux-simplification` (`ad0d130`)  
**Status:** Kandidat perbaikan untuk review, tidak otomatis menyatakan *production concurrency verified*.

## Masalah yang diperbaiki

`ResearchExperimentController::updateAssignment()` sebelumnya memakai `lockForUpdate()` pada baris `experiment_assignments` yang ditemukan melalui `firstOrNew()`. Saat peserta belum mempunyai assignment, belum ada baris yang bisa dikunci, sehingga dua transaksi *first write* dapat bersaing. `AdaptiveRecommendationService::persistDecision()` juga memeriksa assignment yang dapat belum ada sebelum menyimpan rekomendasi.

Perubahan kecil pada kedua jalur:

1. Di awal transaksi, lakukan `User::query()->whereKey($studentId)->lockForUpdate()->firstOrFail()` pada **baris siswa yang sudah ada**.
2. Setelah itu kunci dan baca baris `experiment_assignments` bila tersedia.
3. Lakukan perubahan kondisi/pensupersedan rekomendasi atau persistensi keputusan seperti sebelumnya.
4. Kedua jalur harus mematuhi urutan kunci **user → experiment assignment → recommendation**. Jika menambah penulis assignment baru, jadikan urutan tersebut sebagai persyaratan review.

Dengan demikian baris siswa menjadi *serialization point* saat assignment belum ada. Unique constraint `experiment_assignment_course_student_unique` tetap digunakan sebagai perlindungan duplikasi kedua. Pendekatan ini sengaja membatasi konkurensi untuk **satu siswa lintas kursus**, bukan mengunci seluruh kursus.

## Pengujian otomatis yang ditambahkan

`tests/Feature/ExperimentAssignmentLockOrderingTest.php`:

- Assignment `non_adaptive` pertama setelah rekomendasi dibuat mengubah rekomendasi aktif menjadi *superseded*, menolak generasi baru, dan tetap memiliki satu baris assignment.
- Perpindahan ke kontrol **setelah pemeriksaan awal tetapi sebelum persistDecision** diuji dengan interleaving yang disengaja melalui engine palsu; keputusan lama tidak dapat disimpan.
- Perubahan ulang assignment tidak menggandakan baris dan tidak mengubah kondisi assignment kursus lain.

Pengujian otomatis memakai SQLite in-memory (`phpunit.xml`). **SQLite tidak membuktikan perilaku row lock `SELECT ... FOR UPDATE` pada MySQL atau PostgreSQL.** Oleh karena itu, hasil PASS hanya membuktikan regresi fungsional dan interleaving deterministik yang diujikan.

## Gate pengujian konkurensi database sebenarnya (masih terbuka)

Gunakan database **staging kosong** dengan jenis dan versi DB sesuai target deployment, dua proses/koneksi nyata dan satu siswa yang sudah memiliki enrollment. Jangan menggunakan database peserta penelitian atau data pribadi.

| Skenario dua koneksi | Ekspektasi yang harus diobservasi |
| --- | --- |
| A memulai *first assignment* dan menahan transaksi; B mencoba assignment untuk siswa yang sama | B menunggu user row lock A; setelah A commit, tidak ada assignment duplikat |
| A memegang lock saat mengubah kondisi ke kontrol; B mencoba `persistDecision()` | B menunggu; setelah A commit, B membaca kontrol dan tidak menulis rekomendasi adaptif |
| A menyimpan keputusan adaptif; B mengubah ke kontrol pada siswa yang sama | B menunggu; setelah commit, B men-*supersede* keputusan aktif A |
| Dua siswa berbeda ditulis secara bersamaan | Tidak terjadi lock lintas siswa karena mekanisme ini; cek isolasi data |
| Penulis assignment lain yang tidak mengikuti lock order | Audit sebagai risiko tersisa; wajib diperbaiki sebelum pilot |

Rekam waktu awal/akhir transaksi, isolation level, hasil unique index, jumlah rekomendasi aktif untuk kondisi kontrol (harus **nol**), serta screenshot/log tersanitasi. Jika lock timeout/deadlock muncul, kaji urutan penguncian, retry yang aman, dan kebijakan last-writer-wins untuk penugasan peserta.

## Persyaratan sebelum R6

- Keputusan protokol apakah instruktur boleh memindahkan peserta dari `adaptive` ke `non_adaptive` setelah treatment berjalan; persyaratan *audit trail* dan persetujuan harus ditetapkan.
- Semua generator/persistor rekomendasi dan mutator assignment harus memakai locking yang konsisten; audit tidak terbatas pada dua lokasi yang ditambal.
- Validasi pada MySQL/PostgreSQL atau database deployment sebenarnya, lalu dokumentasikan hasil acceptance.
- Hasil tes ini **tidak** membuktikan efektivitas pedagogis maupun menyelesaikan seluruh agenda R6.
