# R5 — Historical Research Data Migration & Integrity Audit

**Disusun:** 26 November 2026 (WIB)  
**Baseline:** `research/r5-ui-ux-simplification` commit `6d114e2c981a41a0e41a2110ffe65f41045f6df0`  
**Branch audit:** `fix/r5-historical-data-migration-integrity`  
**Tujuan:** membuktikan migrasi tambahan `research_events.deduplication_key` tidak mengubah data yang sudah tercatat.

## 1. Scope dan risiko yang ditemukan di repository

- `2026_10_23_000001_create_research_instrumentation_tables.php` membentuk event dengan UUID unik, treatment/assignment snapshot, dan FK.
- `2026_11_15_000001_add_deduplication_key_to_research_events_table.php` menambah kolom nullable UNIQUE. **Tidak boleh melakukan backfill dan tidak boleh menimpa event historis**.
- `ResearchEventRecorder::recordOnce()` mencari event legacy lalu melakukan `firstOrCreate` berdasarkan hash identitas; event `record()` dapat berulang.
- `AdaptiveAlgorithmRegistry` mencegah perubahan version, engine type, dan config lewat service, tetapi tidak memproteksi modifikasi SQL langsung.
- Constraint `cascadeOnDelete()` pada relasi pengguna/kursus masih berisiko menghapus data bila dilakukan penghapusan permanen. **Tidak diubah sebagai bagian audit migrasi ini; perlu kebijakan retensi dan governance terpisah**.
- MySQL berbeda dari SQLite dalam DDL/transaction behavior; tes migrasi **hanya pada database MySQL sementara**, bukan siswa riil.

## 2. Reproduksi migrasi pada data yang sudah terisi

Workflow: [`R5 Historical Research Data Integrity`](https://github.com/Munfiatun/AI-LCMS/actions/workflows/r5-historical-migration-integrity.yml).

- Engine: MySQL 8.4 pada GitHub Actions; hanya nama database `r5_history_source`, `r5_history_restore` dan `r5_history_recovered`.
- Pembatasan keamanan: `APP_ENV=testing`, host `127.0.0.1`, `GITHUB_ACTIONS=true` dan `R5_HISTORY_CI_ACK=synthetic-only`; skrip menolak koneksi lain.
- Menginstal seluruh skema menggunakan salinan migrasi **tanpa** `2026_11_15_000001` sehingga fixture benar-benar dibuat ketika kolom `deduplication_key` **belum ada**.
- Fixture: 3 akun sintetis (guru, adaptif, kontrol); 1 kursus; 1 learning objective; 2 learning concepts; 2 assignments; 6 research_events (termasuk **dua legacy milestones dengan identitas logis sama**, dua exposure berulang, event kontrol dengan snapshot versi assignment lama), 2 algorithm versions (Rule-Based/Fuzzy), 2 recommendations (kontrol superseded).
- Snapshot kanonis: **semua kolom lama** dari `users`, `courses`, `learning_objectives`, `learning_concepts`, `experiment_assignments`, `research_events`, `adaptive_algorithm_versions`, `adaptive_recommendations`, diurutkan berdasarkan ID, diserialisasi dengan urutan kunci konsisten dan dibandingkan dengan SHA-256. Kolom baru `deduplication_key` sengaja tidak ikut checksum baris lama.
- `migrate:status` dan `migrate --pretend` dijalankan sebelum migrasi aktual; output dipantau agar tidak mengandung `DROP TABLE`, `TRUNCATE`, atau `DELETE FROM research_events`.
- `mysqldump --single-transaction` dibuat **sebelum migrasi** dan tidak diunggah ke GitHub.
- Setelah `migrate` biasa, bandingkan count/sha seluruh tabel, pastikan legacy dedup keys NULL, dan verifikasi `recordOnce` + DB unique constraint + repeatable events + kontrol tanpa adaptive active.
- Kegagalan migrasi dibuat sengaja pada salinan restore database dengan menambahkan kolom yang hendak dimigrasikan; migrasi harus gagal. Pemulihan menggunakan backup yang sama pada **database baru yang kosong**, bukan melakukan rollback destruktif, lalu checksum dibandingkan kembali, migrasi diterapkan dan checksum diperiksa lagi.
- Artefak hasil berisi **jumlah baris dan checksum saja**, bukan nama/email/UUID/metadata peserta, bukan database dump.

## 3. Kriteria penerimaan

| Gate | Status | Bukti |
| --- | --- | --- |
| Pre-migration schema tanpa deduplication_key terbangun | **PASS — MySQL 8.4 CI** | Workflow historical migration |
| Populated history fixtures (6 events, 2 assignments, 2 algorithms) | **PASS — MySQL 8.4 CI** | Output fixture |
| Perbandingan checksum dan jumlah baris sebelum–sesudah | **PASS — MySQL 8.4 CI** | SHA-256 tabel |
| Legacy duplicate milestones dan repeatable events tidak dihapus | **PASS — MySQL 8.4 CI** | postcheck |
| UNIQUE key untuk event baru | **PASS — MySQL 8.4 CI** | postcheck |
| Kondisi kontrol dan versi algoritma tidak berubah | **PASS — MySQL 8.4 CI** | postcheck |
| Migrasi gagal terdeteksi dan recovery backup lolos | **PASS — MySQL 8.4 CI** | Recovery DB terpisah |
| Regresi Laravel SQLite & frontend | **PASS — MySQL 8.4 CI** | Existing Quality Gate |
| MySQL 8.4 concurrency acceptance | **PASS — MySQL 8.4 CI** | Existing MySQL workflow |
| PostgreSQL atau staging target berisi data historis yang sah | **NOT TESTED** | Butuh target dan otorisasi |
| Izin/studi dengan responden nyata | **NOT PART OF THIS AUDIT** | Protokol terpisah |

## 4. Interpretasi dan batas bukti

**Status terverifikasi 26 November 2026:** **READY untuk pengujian migrasi sintetis MySQL CI**, **BLOCKED untuk penerapan ke database penelitian nyata** sampai uji staging berizin dan bukti backup/restore lingkungan target tersedia. Ini bukan persetujuan deployment produksi. Bukti synthetic MySQL tidak membuktikan keamanan data historis pada DB produksi yang belum diamati; sebelum migrasi deployment riil, wajib backup/restore rehearsal, inventory engine+schema, izin operator dan validasi hash di lingkungan staging sebenarnya.

`php artisan migrate:rollback` bukan strategi utama recovery karena rollback migrasi dedup menghapus kolom dan kunci yang mungkin telah dipakai untuk event baru. Laporan ini mengutamakan restore backup terverifikasi.

**Tidak ada migrasi destruktif, dump student, atau real-data query yang dibuat oleh audit ini.**


## 5. Bukti aktual pada kode audit (26 November 2026)

**Verified audit commit:** `37fc5af131a062ba564641c4aa3c9e021851aadb`. **Github Actions:**
- [Historical Research Data Integrity #45741188765](https://github.com/Munfiatun/AI-LCMS/actions/runs/45741188765) — **PASS**. Fixture historis sebelum skema dedup dibuat, hash semua data historis sama setelah migrasi MySQL 8.4; backup, simulasi kegagalan terarah dengan error `Duplicate column`, pemulihan pada database kosong, **rollback pada restored clone tanpa kunci baru**, migrasi ulang dan perbandingan hash **PASS**.
- [Quality Gate #45741188749](https://github.com/Munfiatun/AI-LCMS/actions/runs/45741188749) — **PASS**, 574 passed / 3.827 assertions, **3 skipped MySQL-only** pada SQLite; frontend build dan independent Fuzzy oracle disertakan dalam workflow.
- [MySQL Two-Connection Acceptance #45741188860](https://github.com/Munfiatun/AI-LCMS/actions/runs/45741188860) — **PASS**, 3 passed / 14 assertions; menguji locking dan prasyarat FK/kolom.
- [ISO Instrument #45741188766](https://github.com/Munfiatun/AI-LCMS/actions/runs/45741188766) — **PASS**, 5 Python unittest.
- [Real Browser Readiness #45741188758](https://github.com/Munfiatun/AI-LCMS/actions/runs/45741188758) — merupakan suite E2E terpisah; hasilnya dicatat dari GitHub Actions, bukan dipakai untuk menyimpulkan integritas migrasi.

### 5.1 Data sebelum dan sesudah migrasi — checksum penuh kolom historis

Fixture memakai timestamp deterministik dan objek JSON nested. Setiap SHA-256 dihitung dari **seluruh kolom asli tiap baris**, tersortir berdasarkan `id`, dengan urutan kolom dikanonisasi. Kolom baru `deduplication_key` tidak ikut hash baris historis, dan seluruh nilainya harus tetap `NULL`. Tidak ada nama, email atau UUID pengguna disimpan pada artefak audit.

| Tabel | Jumlah sebelum | Jumlah sesudah | SHA-256 sebelum dan sesudah (identik) |
| --- | ---: | ---: | --- |
| users | 3 | 3 | `70f8ff02ea89967b6e9cb01dc2016fe5624a6a31469798d5a57ad0358802c556` |
| courses | 1 | 1 | `34dbb155896294ae6aad151724812f94312e274c00dd5be192994ef1561767b7` |
| learning_objectives | 1 | 1 | `9e2f4b594f158525c833d19cfcfe180242611b2de349f876f18c0ceef7aaded1` |
| learning_concepts | 2 | 2 | `233a5209c04cb44da4b883b7a9470b0b79e0966177feab581cbcae09ebf9466f` |
| experiment_assignments | 2 | 2 | `b7af26497088dbe040d2ba08eea22be709afb5c1b4f32a4b0d2db5ad3536c97a` |
| research_events | 6 | 6 | `97eda5ded2f5b3280992db35694ebd74337b06adfbde89fd38efc8642351ad87` |
| adaptive_algorithm_versions | 2 | 2 | `d4451f9562774026d6c2eed828839ea51810d8b911248ff74eac5ef4fffae2e9` |
| adaptive_recommendations | 2 | 2 | `75223b2864b42df596da82c4f47f51c3eadb05b5cac85757d16299a8d1fc2744` |

**Combined checksum** `447676f801ef36ed31707e15adb123b982439fdb22768f607adc4e94192950f8` pada fixture awal dan hasil verifikasi. Data lama tidak dihapus, termasuk dua legacy milestones yang punya identitas logis sama. Setelah perbandingan selesai, uji `postcheck` sengaja membuat event sintetis baru untuk memverifikasi idempotensi; **event baru tersebut bukan bagian dari checksum sebelum–sesudah migrasi**.

### 5.2 Backup, fault injection, rollback terbatas dan restore

1. Sebelum migrasi, lakukan `migrate:status`, `migrate --pretend`, dan `mysqldump --single-transaction`. Dump SQL hanya di disk sementara runner.
2. Migrasi aktual: tabel penelitian berisi data → kolom dedup baru → hash semua baris lama identik.
3. Restore backup ke `r5_history_restore`, verifikasi SHA-256 semua tabel identik.
4. Pada **salinan restore saja**, suntikkan skema kolom yang bertabrakan dengan migrasi yang pending; `migrate` gagal dengan pesan `Duplicate column` sesuai ekspektasi. Jangan membetulkan secara destruktif.
5. Restore dump yang sama pada DB baru kosong `r5_history_recovered`, verifikasi seluruh hash, terapkan migrasi, verifikasi ulang, rollback hanya migrasi terakhir saat **tidak ada nilai dedup key baru**, dan migrasi ulang + verifikasi ulang. Setiap tahap **PASS**.

Rollback ini **tidak** mengesahkan rollback database yang sudah menghasilkan event baru berkunci: rollback kolom dalam keadaan itu dapat menghilangkan kunci dan belum aman. **Restore backup terverifikasi tetap prosedur pemulihan utama** jika migrasi deployment gagal.

Artefak hanya memuat `synthetic-row-hashes.json`, `synthetic-backup-sha256.txt`, dan `acceptance-summary.txt`; tidak ada `.sql`, dump, atau PII siswa.

### 5.3 Kesimpulan audit dan residual risk

- **READY (synthetic MySQL 8.4 migration acceptance):** 8 tabel dengan 19 baris historis dipertahankan identik; event lama, treatment snapshots, versions, references, constraints, backup/restore dan kesalahan migrasi diujikan.
- **BLOCKED (data penelitian nyata):** tidak ada akses, otorisasi atau bukti staging dengan histori/data siswa sungguhan. Versi database target, isolasi transaksi lintas proses, kewenangan backup, dan proses retensi/larangan penghapusan permanen belum diverifikasi pada deployment.
- **NOT TESTED:** PostgreSQL, snapshot database penelitian nyata, pemulihan lintas host/cloud, retensi jangka panjang, dan validitas pedagogis ahli. Jangan mengklaim bagian ini PASS.

**Keputusan:** PR bisa direview sebagai tes integritas migrasi sintetis yang berhasil. **Jangan menjalankan migrasi pada database riil atau merge ke `main` hanya karena CI lulus.**
