# R5.1 — Research Data Integrity Hardening

Tanggal: 15 November 2026 (WIB)  
Basis: `research/r5-ui-ux-simplification` @ `ad0d130`  
Lingkup PR: **version freeze dan idempotency event**. Tidak mengubah konfigurasi algoritma aktif, kelompok siswa, maupun parameter pembelajaran.

## 1. Perlindungan versi algoritma

Masalah: `AdaptiveAlgorithmRegistry::syncConfiguredVersions()` sebelumnya akan mengubah `version`, `engine_type` dan `configuration` dari record ber-`code` sama. Karena `adaptive_recommendations.algorithm_version_id` mengacu pada record tersebut, perubahan ini bisa mengubah definisi historis tanpa jejak versi yang cukup.

Perbaikan: sesudah algoritma terdaftar, tiga bagian definisi komputasional (version, engine type, configuration) tidak boleh berubah memakai `code` sama. Sistem melempar `InvalidArgumentException` yang meminta **kode algoritma baru**. Perubahan label, deskripsi, dan flag lifecycle `status`/`is_baseline` tetap diizinkan karena bukan definisi perhitungannya.

Cara membuat versi baru: tambahkan entri kode unik dalam `config/adaptive.php`, lengkap dengan `version` baru; jangan mengubah definisi `rule-based-v1` atau `fuzzy-v2` yang telah digunakan. Uji referensi lama tetap dipertahankan sebelum versi baru dibandingkan/digunakan.

Batasan: ini perlindungan pada **jalur registrasi service**, bukan trigger database yang melarang penulisan manual lewat SQL. Jika perlu audit ketat perubahan lifecycle, tambah peristiwa aktivasi/retirement dan persetujuan version freeze sebelum R6.

## 2. Keunikan milestone event

Masalah: `ResearchEventRecorder::recordOnce()` sebelumnya mencari event lama kemudian membuat event. Pada dua permintaan bersamaan, keduanya dapat melihat hasil kosong dan membuat event ganda.

Perbaikan:
- Kolom `research_events.deduplication_key` (string 64, nullable, unique). Nilai NULL hanya untuk event yang boleh berulang dan baris historis.
- `recordOnce()` menggunakan SHA-256 dari identitas terstruktur (student, course, event type, source type, source ID), lalu melakukan `firstOrCreate` dengan constraint unik database.
- Event lama tetap direuse dari pencarian berdasarkan identitas seperti perilaku lama. Snapshot kondisi eksperimen, `event_id`, dan metadata awal **tidak ditulis ulang** saat permintaan diulang.
- `record()`, misalnya event `material_opened`, tidak diberi kunci, sehingga tetap boleh muncul berkali-kali.

**Kompabilitas migrasi:** baris lama otomatis mempunyai `deduplication_key=NULL`. Migrasi **tidak** melakukan backfill yang berisiko menimpa atau menghapus data. Pastikan staging backup dan jalankan `php artisan migrate --force` melalui proses deployment yang disetujui, tidak pada database kelas nyata tanpa izin.

## 3. Pengujian regresi

- `tests/Feature/AdaptiveAlgorithmDefinitionIntegrityTest.php`: idempotensi versi sama, perubahan konfigurasi ditolak, perubahan version/engine type ditolak, kode baru mengizinkan definisi baru.
- `tests/Feature/ResearchEventIdempotencyIntegrityTest.php`: event once-only diulang dengan perubahan assignment, event repeatable tetap berulang, event legacy tetap terpakai, kunci berbeda per siswa/sumber.
- Full Laravel suite dan frontend build dijalankan di GitHub Actions pada PR ini; catat run id serta jumlah tes setelah pipeline selesai.

## 4. Gate yang belum ditutup

1. **P0 — Assignment concurrency:** penguncian saat update assignment versus persist rekomendasi, terutama transaksi pertama tanpa record, masih memerlukan pengujian pada MySQL/PostgreSQL dengan dua koneksi nyata.
2. **P1 — Event concurrency multi-connection:** unique index merupakan pengaman DB, namun uji beban/konkurensi terpisah tetap diperlukan; pengujian PHPUnit standar di SQLite in-memory tidak membuktikan seluruh perilaku database produksi.
3. **P1 — Lifecycle/version freeze R6:** rencana kapan algoritma dan peristiwa pengaktifan dibekukan harus ditentukan bersama dosen.
4. **P1 — UI E2E dan validasi pedagogis:** tetap perlu pembuktian terpisah.

Dokumen ini membedakan hasil regresi otomatis dengan validitas pedagogis dan uji lapangan. **Jangan menyatakan R6 selesai hanya karena R5.1 PASS.**
