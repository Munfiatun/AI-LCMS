# R5 — Review Gate dan Rekonsiliasi Issue #34

**PIC teknis:** Munfiatun Nairoh. **Reviewer persetujuan:** dosen pengampu.  
**Tanggal penyusunan:** 25 November 2026 (WIB).  
**Status:** DRAFT FOR REVIEW, bukan pengesahan integrasi atau penelitian.  
**Sumber:** [Issue #34](https://github.com/Munfiatun/AI-LCMS/issues/34).

## A. Baseline yang dapat diaudit

| Ref | SHA | Makna |
| --- | --- | --- |
| main | 39f34f411ccd68cdb54f47a1e8644808f7aedb4d | Produk dasar; kode R5 belum ada di main |
| research/r5-ui-ux-simplification | 9708706336c4e501a2a21511c56ebb94a938e6d3 | Basis penelitian, pembaruan workflow |
| PR #33 / branch integrasi | 7746a649bad84159f383d8ef4051b4b6bcdbd8e9 | Kandidat gabungan, **belum merge** |

| PR | Perubahan | File | Status/relasi |
| --- | --- | ---: | --- |
| [#29](https://github.com/Munfiatun/AI-LCMS/pull/29) | Algorithm-definition immutability, event deduplication, migration + tests | 7 | OPEN; terkandung di #33 |
| [#30](https://github.com/Munfiatun/AI-LCMS/pull/30) | Lock order assignment → recommendation + tests | 4 | OPEN; terkandung di #33 |
| [#32](https://github.com/Munfiatun/AI-LCMS/pull/32) | Student empty-state non-adaptive + test | 2 | OPEN/DRAFT; terkandung di #33 |
| [#33](https://github.com/Munfiatun/AI-LCMS/pull/33) | **Gabungan tepat 13 file di atas** | 13 | OPEN/DRAFT; [QA run #88](https://github.com/Munfiatun/AI-LCMS/actions/runs/43822773343) PASS |
| [#28](https://github.com/Munfiatun/AI-LCMS/pull/28) | PROGRESS.md dan README (dokumentasi saja) | 2 awal | OPEN; target main, **terpisah** dari fitur R5 |

**Bukti gabungan terakhir:** GitHub Actions Quality Gate #88, commit **7746a649**, 16 November 2026: **571 tests passed / 3.794 assertions**, Vite build PASS, fuzzy numeric reference oracle PASS. Ini QA pada CI berbasis SQLite in-memory, bukan hasil uji database MySQL/PostgreSQL atau efektivitas siswa.

**Strategi disarankan:** setelah review dosen dan gate kritis, hanya **PR #33** yang diintegrasikan ke branch R5. Jangan merge lagi #29/#30/#32 bila seluruh patch sudah masuk lewat #33; beri komentar *superseded* dan tutup tanpa merge setelah disetujui. Alternatif merge berurutan PR asal mewajibkan meniadakan PR #33 dan menguji ulang gabungan pada SHA akhir. **Tidak menggabungkan ke main** tanpa keputusan terpisah. PR #28 bisa diterima secara terpisah karena hanya dokumentasi.

## B. Audit migrasi idempotensi — BELUM DIUJI PADA DB STAGING

Migrasi kandidat pada #29/#33: **database/migrations/2026_11_15_000001_add_deduplication_key_to_research_events_table.php**. Menambahkan kolom nullable varchar(64) dengan unique index, **tidak melakukan backfill** data historis. Method down menghapus index beserta kolom, sehingga nilai kunci yang tercatat kemudian dapat hilang bila rollback dilakukan.

Checklist yang harus dijalankan oleh operator berizin **hanya pada staging/sandbox berisi data sintetis**, tidak pada DB siswa:

- [ ] Tentukan DB engine/version/isolation level, akun operator, dan bukti lingkungan staging.
- [ ] Backup terverifikasi dan demonstrasikan prosedur restore; jangan unggah backup/dump ke GitHub.
- [ ] Ambil statistik sebelum: jumlah baris research_events, event_id unik, snapshot timestamp, assignment, dan event sintetis historis.
- [ ] Jalankan **php artisan migrate:status** lalu **php artisan migrate --pretend** pada koneksi staging; simpan SQL dan SHA.
- [ ] Setelah review serta persetujuan operator, jalankan **php artisan migrate --force** pada staging dan catat hasilnya.
- [ ] Periksa jumlah baris tidak berkurang, snapshot historis tidak berubah, deduplication_key legacy NULL, index UNIQUE berlaku dan recordOnce baru tidak menduplikasi.
- [ ] Uji recordOnce dengan dua koneksi DB sungguhan dan record() berulang; catat hasil, penanganan konflik, waktu.
- [ ] Untuk pemulihan, prioritaskan restore backup yang sudah diuji. Rollback menghapus kolom/key; jangan melakukan rollback di sistem berisi data nyata tanpa izin.

**Status:** pemeriksaan struktur migrasi dan tes SQLite tersedia, tetapi backup/dry-run/forward-backward di staging **belum ada buktinya**.

## C. Penerimaan konkurensi assignment — BELUM DIUJI DUA KONEKSI

Rujukan: **R5_1_ASSIGNMENT_CONCURRENCY_ACCEPTANCE_20261115.md** pada PR #30/#33. Lock order harus konsisten: **user → experiment assignment → recommendation**.

| Skenario dua proses/koneksi staging | Kondisi lulus yang diharapkan | Hasil aktual |
| --- | --- | --- |
| A menahan lock saat assignment pertama; B menulis assignment siswa sama | B menunggu; tetap satu assignment student×course | BELUM DIISI |
| A mengalihkan ke non_adaptive; B mencoba persist rekomendasi | Tidak ada rekomendasi adaptif aktif setelah A commit | BELUM DIISI |
| A persist rekomendasi dahulu; B beralih ke kontrol | B menunggu lalu menandai rekomendasi superseded | BELUM DIISI |
| Siswa berbeda diproses bersama | Tidak ada blocking lock antaruser yang tak relevan | BELUM DIISI |
| Akses UI / direct URL / generator kontrol | Tidak terjadi paparan treatment atau event exposure adaptif | BELUM DIISI |

Gunakan **fixture anonim**, rekam waktu, isolation level, deadlock/timeout, jumlah assignment/rekomendasi dan log tersanitasi. **Tes SQLite deterministik pada #33 tidak membuktikan SELECT FOR UPDATE di MySQL/PostgreSQL.** Dosen harus meninjau SOP perubahan kelompok selama treatment.

## D. Audit E2E dan UX — TIDAK ADA BUKTI MANUAL YANG DILAMPIRKAN

| Peran/Jalur | Skenario yang wajib direkam | Bukti |
| --- | --- | --- |
| Guru | Tujuan/konsep → mapping materi/soal → terbitkan materi dan kuis | Screenshot anonim + log |
| Siswa adaptif | Diagnostik → mastery → rekomendasi → materi → formative → readaptasi | Riwayat keputusan dan perubahan evidence |
| Siswa kontrol | Fitur belajar sama tanpa rekomendasi adaptif (termasuk direct URL) | Tidak ada exposure/rekomendasi aktif |
| Keamanan | Siswa lain, instruktur lain, enrollment dropped/completed sesuai kebijakan | Hasil 403/redirect dan konsistensi tes |
| Responsive | 375px, 768px, 1280px | Layout, overflow, target klik |
| Aksesibilitas | Keyboard Tab/Shift+Tab, Escape sidebar, visible focus, slidebook arrows/Home/End, label soal | Audit manual, bukan hanya tes string/CSS |
| Empty/error state | Kursus kosong, kuis kosong, rekomendasi tidak ada, network failure | Pesan netral tanpa janji treatment |

Tes yang perlu dijalankan ulang pada SHA final: **php artisan test --compact**, **npm run build**, **python3 scripts/research/fuzzy_reference_oracle.py**. Jika belum ada bukti output lokal, cantumkan hanya bukti CI yang benar-benar ada.

## E. Status gate serah terima (25 November 2026)

| Gate | Keputusan |
| --- | --- |
| CI integrasi 13 file, PR #33 | **DONE (kandidat teknis)** — run #88 |
| Approval / merge #33 → R5 | **PENDING** reviewer |
| Migrasi dan backup di DB target/staging | **NOT VERIFIED** |
| Locking dua koneksi, treatment isolation nyata | **NOT VERIFIED** |
| E2E/browser/keyboard/manual responsive | **PENDING** |
| Lembar 16 skenario | **DRAFT PREPARED**, belum disahkan |
| Gold standard ahli dan validitas pedagogis | **PENDING** — penilaian nyata belum terkumpul |
| R6 protocol, izin, version freeze | **NOT FROZEN / NO-GO** |

Tiga agenda keputusan dosen: **(1)** terima strategi satu PR #33 untuk R5? **(2)** apakah panel ahli dan instrumen/hasilnya memadai sebelum pilot? **(3)** apakah desain R6 dapat diajukan setelah izin dan freeze?

**Kontribusi:** audit regresi awal serta Issue #34 berasal dari dosen; penyiapan kandidat PR dan berkas tindak lanjut berada di repository mahasiswa. Dokumen ini bukan pengesahan dosen. Jangan menaruh nama siswa, token, kredensial, atau dump database pada Issue/PR.