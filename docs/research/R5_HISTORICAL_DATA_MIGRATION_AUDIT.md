# R5 — Historical Research Data Migration & Integrity Audit

**Disusun:** 26 November 2026 (WIB)  
**Baseline:** \`research/r5-ui-ux-simplification\` commit \`6d114e2c981a41a0e41a2110ffe65f41045f6df0\`  
**Branch audit:** \`fix/r5-historical-data-migration-integrity\`  
**Tujuan:** membuktikan migrasi tambahan \`research_events.deduplication_key\` tidak mengubah data yang sudah tercatat.

## 1. Scope dan risiko yang ditemukan di repository

- \`2026_10_23_000001_create_research_instrumentation_tables.php\` membentuk event dengan UUID unik, treatment/assignment snapshot, dan FK.
- \`2026_11_15_000001_add_deduplication_key_to_research_events_table.php\` menambah kolom nullable UNIQUE. **Tidak boleh melakukan backfill dan tidak boleh menimpa event historis**.
- \`ResearchEventRecorder::recordOnce()\` mencari event legacy lalu melakukan \`firstOrCreate\` berdasarkan hash identitas; event \`record()\` dapat berulang.
- \`AdaptiveAlgorithmRegistry\` mencegah perubahan version, engine type, dan config lewat service, tetapi tidak memproteksi modifikasi SQL langsung.
- Constraint \`cascadeOnDelete()\` pada relasi pengguna/kursus masih berisiko menghapus data bila dilakukan penghapusan permanen. **Tidak diubah sebagai bagian audit migrasi ini; perlu kebijakan retensi dan governance terpisah**.
- MySQL berbeda dari SQLite dalam DDL/transaction behavior; tes migrasi **hanya pada database MySQL sementara**, bukan siswa riil.

## 2. Reproduksi migrasi pada data yang sudah terisi

Workflow: [\`R5 Historical Research Data Integrity\`](https://github.com/Munfiatun/AI-LCMS/actions/workflows/r5-historical-migration-integrity.yml).

- Engine: MySQL 8.4 pada GitHub Actions; hanya nama database \`r5_history_source\`, \`r5_history_restore\` dan \`r5_history_recovered\`.
- Pembatasan keamanan: \`APP_ENV=testing\`, host \`127.0.0.1\`, \`GITHUB_ACTIONS=true\` dan \`R5_HISTORY_CI_ACK=synthetic-only\`; skrip menolak koneksi lain.
- Menginstal seluruh skema menggunakan salinan migrasi **tanpa** \`2026_11_15_000001\` sehingga fixture benar-benar dibuat ketika kolom \`deduplication_key\` **belum ada**.
- Fixture: 3 akun sintetis (guru, adaptif, kontrol); 1 kursus; 1 learning objective; 2 learning concepts; 2 assignments; 6 research_events (termasuk **dua legacy milestones dengan identitas logis sama**, dua exposure berulang, event kontrol dengan snapshot versi assignment lama), 2 algorithm versions (Rule-Based/Fuzzy), 2 recommendations (kontrol superseded).
- Snapshot kanonis: **semua kolom lama** dari \`users\`, \`courses\`, \`learning_objectives\`, \`learning_concepts\`, \`experiment_assignments\`, \`research_events\`, \`adaptive_algorithm_versions\`, \`adaptive_recommendations\`, diurutkan berdasarkan ID, diserialisasi dengan urutan kunci konsisten dan dibandingkan dengan SHA-256. Kolom baru \`deduplication_key\` sengaja tidak ikut checksum baris lama.
- \`migrate:status\` dan \`migrate --pretend\` dijalankan sebelum migrasi aktual; output dipantau agar tidak mengandung \`DROP TABLE\`, \`TRUNCATE\`, atau \`DELETE FROM research_events\`.
- \`mysqldump --single-transaction\` dibuat **sebelum migrasi** dan tidak diunggah ke GitHub.
- Setelah \`migrate\` biasa, bandingkan count/sha seluruh tabel, pastikan legacy dedup keys NULL, dan verifikasi \`recordOnce\` + DB unique constraint + repeatable events + kontrol tanpa adaptive active.
- Kegagalan migrasi dibuat sengaja pada salinan restore database dengan menambahkan kolom yang hendak dimigrasikan; migrasi harus gagal. Pemulihan menggunakan backup yang sama pada **database baru yang kosong**, bukan melakukan rollback destruktif, lalu checksum dibandingkan kembali, migrasi diterapkan dan checksum diperiksa lagi.
- Artefak hasil berisi **jumlah baris dan checksum saja**, bukan nama/email/UUID/metadata peserta, bukan database dump.

## 3. Kriteria penerimaan

| Gate | Status | Bukti |
| --- | --- | --- |
| Pre-migration schema tanpa deduplication_key terbangun | **PENDING CI** | Workflow historical migration |
| Populated history fixtures (6 events, 2 assignments, 2 algorithms) | **PENDING CI** | Output fixture |
| Perbandingan checksum dan jumlah baris sebelum–sesudah | **PENDING CI** | SHA-256 tabel |
| Legacy duplicate milestones dan repeatable events tidak dihapus | **PENDING CI** | postcheck |
| UNIQUE key untuk event baru | **PENDING CI** | postcheck |
| Kondisi kontrol dan versi algoritma tidak berubah | **PENDING CI** | postcheck |
| Migrasi gagal terdeteksi dan recovery backup lolos | **PENDING CI** | Recovery DB terpisah |
| Regresi Laravel SQLite & frontend | **PENDING CI** | Existing Quality Gate |
| MySQL 8.4 concurrency acceptance | **PENDING CI** | Existing MySQL workflow |
| PostgreSQL atau staging target berisi data historis yang sah | **NOT TESTED** | Butuh target dan otorisasi |
| Izin/studi dengan responden nyata | **NOT PART OF THIS AUDIT** | Protokol terpisah |

## 4. Interpretasi dan batas bukti

**Status awal: BLOCKED — menunggu hasil CI pada commit audit.** Jangan menulis PASS sebelum GitHub Actions benar-benar berhasil. Bukti synthetic MySQL tidak membuktikan keamanan data historis pada DB produksi yang belum diamati; sebelum migrasi deployment riil, wajib backup/restore rehearsal, inventory engine+schema, izin operator dan validasi hash di lingkungan staging sebenarnya.

\`php artisan migrate:rollback\` bukan strategi utama recovery karena rollback migrasi dedup menghapus kolom dan kunci yang mungkin telah dipakai untuk event baru. Laporan ini mengutamakan restore backup terverifikasi.

**Tidak ada migrasi destruktif, dump student, atau real-data query yang dibuat oleh audit ini.**
