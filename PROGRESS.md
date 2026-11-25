# LAPORAN KEMAJUAN PROYEK APLIKASI WEB

> **Ketentuan pelaporan:** `PROGRESS.md` berada di *root* repository, diperbarui setiap Jumat, dan laporan minggu terbaru ditaruh paling atas tanpa menghapus riwayat. Pekerjaan belum selesai, kendala, dan hasil pengujian harus dicatat apa adanya. Jangan memasukkan data rahasia atau data pribadi siswa.

> **Catatan arsip:** laporan pertama ini disusun **Minggu, 15 November 2026** untuk merangkum periode yang berakhir **Jumat, 13 November 2026**, sehingga **melewati tenggat Jumat malam** yang ditetapkan panduan. Bukti kode/CI terakhir yang dapat diverifikasi untuk penelitian R5 berasal dari **6 November 2026**; tidak diklaim sebagai aktivitas baru pada 9–13 November.

## Identitas Proyek

| Keterangan | Isi |
|---|---|
| Nama mahasiswa / tim | Munfiatun Nairoh |
| Nama aplikasi | **AI-LCMS — AI-assisted Learning Content Management System** |
| Repository GitHub | https://github.com/Munfiatun/AI-LCMS |
| Branch yang direview | `docs/progress-weekly-20261115` (**laporan/README**); `research/r5-ui-ux-simplification` (**kode penelitian terbaru**) |
| Status umum proyek | **Dalam proses** — implementasi dan regresi teknis telah tersedia; validasi akademik dan R6 belum final |
| Terakhir diperbarui | **2026-11-25** (pembaruan retrospektif, target 20 Nov terlewat) |

## Ringkasan Kemajuan Saat Ini

- **Fitur utama yang sudah tersedia pada pengembangan:** pengelolaan kursus, materi, Slidebook AI, bank soal dan kuis, progres belajar, dashboard, pencatatan peristiwa penelitian, pembaruan *mastery*, serta mesin rekomendasi Rule-Based v1 dan Fuzzy Mamdani v2. Klaim ini berarti fitur/implementasi dan tes yang terkait sudah ada, **bukan** bukti efektivitas pembelajaran lapangan.
- **Fitur/pekerjaan yang masih dikerjakan:** validasi pedagogis *mastery* dan rekomendasi oleh pakar, kelayakan parameter aturan/fuzzy, pemetaan item–konsep–materi, pengendalian kontaminasi kelompok eksperimen, serta persiapan instrumen dan protokol R6.
- **Kendala utama:** belum tersedia hasil *expert judgement*, *gold standard*, dan uji coba siswa/eksperimen nyata. Pengujian konkurensi database produksi juga belum diverifikasi. **Tidak ada bug produksi baru yang dinyatakan terkonfirmasi hanya berdasarkan audit ini.**
- **PR laporan aktif:** [PR #28 — laporan kemajuan mingguan](https://github.com/Munfiatun/AI-LCMS/pull/28), tetap menuju `main` hanya dokumentasi; [Draft PR #35](https://github.com/Munfiatun/AI-LCMS/pull/35) menyimpan instrumen 16 skenario, audit gate R5 dan protokol R6 pada branch penelitian.
- **PR teknis yang sudah di-merge:** [#25 revisi dosen](https://github.com/Munfiatun/AI-LCMS/pull/25), [#26 isolasi assignment](https://github.com/Munfiatun/AI-LCMS/pull/26), [#27 validasi numerik](https://github.com/Munfiatun/AI-LCMS/pull/27).
- **Rujukan terbaru yang terverifikasi (25 Nov):** branch `research/r5-ui-ux-simplification` SHA `9708706`; [PR #33](https://github.com/Munfiatun/AI-LCMS/pull/33) kandidat integrasi 13 file SHA `7746a649` (QA gabungan PASS), tetapi **belum di-merge ke R5 maupun main**. Riwayat `ad0d130` tetap dicatat di bagian minggu sebelumnya.
- **Bukti CI kandidat integrasi terbaru:** [Quality Gate 16 November 2026](https://github.com/Munfiatun/AI-LCMS/actions/runs/43822773343) pada PR #33 SHA `7746a649`: **571 tes PASS, 3.794 assertions**, frontend build dan oracle fuzzy PASS. Ini belum membuktikan uji DB nyata/validitas pedagogis. Bukti historis 6 November tetap tersedia pada laporan minggu lama.

---

## Riwayat Laporan Mingguan (terbaru di atas)

### Minggu berjalan — 23–25 November 2026 (serah terima review Issue #34)

**Tanggal pembaruan:** Rabu, **25 November 2026**, mengikuti tanggal serah terima pada [Issue #34](https://github.com/Munfiatun/AI-LCMS/issues/34). Minggu ini **belum selesai** (Jumat berikutnya 27 November); jangan disalahartikan sebagai laporan pekan penuh.  
**PIC pengembangan/laporan:** Munfiatun Nairoh. **Reviewer/gate:** dosen pengampu.  
**Branch laporan:** docs/progress-weekly-20261115 (dokumentasi → main). **Branch kode:** research/r5-ui-ux-simplification; kandidat integrasi di PR #33.  
**Baseline:** main 39f34f4; R5 9708706; PR #33 7746a649.

#### 1. Target dan bukti nyata

- [x] **Rekonsiliasi status PR** #29/#30/#32 sebagai input PR #33; komentar rekomendasi *satu jalur merge* telah ditempatkan di [PR #33](https://github.com/Munfiatun/AI-LCMS/pull/33) pada 25 November. Status keputusan: **MENUNGGU DOSEN**, tidak ada merge.
- [x] **Menyiapkan paket dokumen review** [Draft PR #35](https://github.com/Munfiatun/AI-LCMS/pull/35): matriks gate R5, checklist migrasi/concurrency dan E2E, lembar **16 kasus blind expert review**, dan draft protokol R6. Ini adalah **rancangan**, bukan validasi ahli atau hasil eksperimen.
- [x] **Memisahkan hasil QA kode dan validitas ilmiah:** [CI PR #33 run #43822773343](https://github.com/Munfiatun/AI-LCMS/actions/runs/43822773343), commit 7746a649, **571 tests passed, 3.794 assertions; build Vite dan oracle fuzzy PASS**. Pengujian berlangsung **16 November**, tidak diklaim dijalankan ulang 25 November.
- [ ] **Audit migrasi nyata:** belum ada bukti backup, migrate --pretend/forward, dan strategi restore pada database staging engine target.
- [ ] **Uji concurrency 2 koneksi MySQL/PostgreSQL:** belum ada bukti empiris; SQLite feature test tidak membuktikan row lock pada database tujuan.
- [ ] **Manual E2E dan audit UX/aksesibilitas** pada browser, termasuk treatment isolation direct URL dan status enrollment: belum ada screenshot/log manual tersanitasi yang bisa diverifikasi.
- [ ] **Panel ahli dan gold standard:** belum ada skor penilaian nyata 16 skenario maupun adjudikasi.
- [ ] **R6 eksperimen:** protokol masih DRAFT, izin, instrumen pre/post eksternal, dan version-freeze belum disetujui. **Eksperimen tidak dimulai.**

#### 2. Tabel status DONE / BLOCKED / PENDING

| Target review dosen | Status aktual per 25 November | Bukti / tindakan berikut |
| --- | --- | --- |
| Rekonsiliasi PR dan baseline | **DONE — dokumentasi** | PR #33 dan matriks di PR #35; tunggu keputusan reviewer |
| QA kandidat integrasi | **DONE — untuk SHA 7746a649** | CI #43822773343 pada 16 Nov; **belum merged** |
| Laporan mingguan | **UPDATED 25 Nov (terlambat untuk target Jumat 20 Nov)** | Perubahan dalam PR #28 |
| Migrasi/preservasi research_events | **BLOCKED — belum diverifikasi di staging** | Staging aman, backup, migrate --pretend, verifikasi historis |
| Isolasi assignment dan lock dua koneksi | **BLOCKED — belum diverifikasi DB riil** | Pengujian 2 koneksi dan audit kontrol |
| Model mastery/validasi algoritma dengan ahli | **PENDING** | Formulir di PR #35; isi hasil **oleh pakar**, bukan data buatan |
| UX/E2E browser dan aksesibilitas | **PENDING** | Jalankan skenario manual, simpan bukti anonim |
| Protokol, izin, freeze R6 | **DRAFT / NOT FROZEN** | Review dosen dan izin institusi sebelum pilot |

#### 3. Hasil pengujian yang bisa dipertanggungjawabkan

| Jenis | Bukti | Status |
| --- | --- | --- |
| Laravel PHPUnit + frontend + oracle Fuzzy pada kandidat integrasi | GitHub Actions [run #43822773343](https://github.com/Munfiatun/AI-LCMS/actions/runs/43822773343), SHA 7746a649, 16 Nov | **PASS 571 tests / 3.794 assertions** |
| Pengujian PHP/Laravel lokal pada commit final 25 Nov | Belum ada log/output lokal yang dapat diverifikasi | **BELUM DIKLAIM** |
| Uji MySQL/PostgreSQL 2 koneksi dan migration staging | Belum ada bukti terlampir | **PENDING** |
| Validasi pedagogis dan studi R6 pada peserta | Belum ada bukti nilai pakar/siswa | **PENDING** |

#### 4. Keputusan yang diminta dosen

1. Terima satu PR integrasi #33 menuju branch R5 setelah review migrasi dan treatment-fidelity, atau pilih urutan PR asal dan ulangi QA? **Jangan merge keduanya.**
2. Setujui panel/rubrik 16 skenario, LO dan kriteria expert judgement sebelum pilot?
3. Apakah rancangan kelas/cluster, instrumen pre/post, SOP override, izin dan tanggal version-freeze R6 sudah layak dilanjutkan?

#### 5. Langkah berikutnya

Minta review **PR #28** (dokumentasi → main), **PR #35** (protokol dan gate → R5), dan **PR #33** (kode kandidat → R5; **belum merge**). Lakukan staging test, E2E manual dan penilaian pakar bersama PIC yang berwenang. Semua data penelitian asli harus tetap di luar GitHub.

---

### Periode 16–20 November 2026 (laporan retrospektif, tidak diunggah Jumat 20 Nov)

**Tanggal pencatatan:** 25 November 2026 (**terlambat dari tenggat Jumat 20 November; tidak dibackdate**).  
**Target:** QA gabungan patch R5, stabilitas workflow, penanganan laporan dan penyiapan reviewer.

#### 1. Capaian yang terbukti pada periode

- [x] PR #32 dibuat 16 November, dengan revisi empty-state rekomendasi yang netral terhadap peserta non-adaptive. CI untuk kandidat terpisah **559 tests PASS / 3.732 assertions**: [run #43817569016](https://github.com/Munfiatun/AI-LCMS/actions/runs/43817569016).
- [x] PR #33 menggabungkan patch #29, #30, #32 **tanpa mengubah main/R5**; CI GitHub Actions [#43822773343](https://github.com/Munfiatun/AI-LCMS/actions/runs/43822773343) pada 16 November: **571 PASS / 3.794 assertions**, build frontend dan oracle fuzzy PASS.
- [x] Pembaruan GitHub Actions pada branch R5 16 November: actions/checkout@v5, actions/setup-node@v5, runner ubuntu-24.04; CI terbaru branch R5 di [run #43820399403](https://github.com/Munfiatun/AI-LCMS/actions/runs/43820399403) PASS.
- [x] 19 November: dosen menerbitkan [Issue #34](https://github.com/Munfiatun/AI-LCMS/issues/34) berisi target 7 hari dan review progres.
- [ ] **Laporan Jumat 20 November tidak dipublikasikan saat tenggat**. Penulisan ulang retrospektif dilakukan 25 November dan dinyatakan jujur.

#### 2. Kendala/pekerjaan belum selesai

| Item | Status pada akhir periode | Penjelasan |
| --- | --- | --- |
| PR #29/#30/#32/#33 | **OPEN** | Gabungan lulus CI, reviewer/merge belum |
| Penilaian ahli Rule-Based vs Fuzzy | **PENDING** | Protokol contoh ada, data penilai belum |
| Database concurrency & migrasi staging | **PENDING** | SQLite/CI tidak menguji dua koneksi DB produksi |
| R6/desain eksperimen | **DRAFT** | Penetapan kelas, LO, pre/posttest eksternal, izin dan freeze belum disetujui |

#### 3. Bukti dan tugas berikutnya

Gunakan PR #33 dan CI #43822773343 sebagai **bukti engineering**. Jangan menyatakan keberhasilan pembelajaran dari jumlah test. Prioritaskan rekonsiliasi merge, PROGRESS.md, dokumentasi riset dan pengumpulan bukti validasi asli.

---

### Minggu ke-02 (periode pelaporan) — 2026-11-09 s.d. 2026-11-13

**Tanggal pembaruan:** 2026-11-15 (**terlambat dari Jumat, 2026-11-13**)  
**Branch:** `docs/progress-weekly-20261115` (laporan); `research/r5-ui-ux-simplification` (implementasi R5)  
**Commit terakhir kode R5 yang terverifikasi:** [`ad0d130`](https://github.com/Munfiatun/AI-LCMS/commit/ad0d130fb247a29722efec8d85b5fccc1b80af07) (6 November 2026)  
**Pull Request:** [Draft PR #28 — laporan kemajuan](https://github.com/Munfiatun/AI-LCMS/pull/28); PR teknis #25–27 sudah *merged*

#### 1. Target minggu ini

> Target tindak lanjut yang dibawa dari hasil audit R5; **belum ada bukti target berikut selesai pada periode 9–13 November**.

- [ ] Menyiapkan *expert judgement* atas 16 skenario keputusan adaptif dan mendokumentasikan hasil penilaian individual serta kesepakatan ahli.
- [ ] Memvalidasi reliabilitas/keterwakilan butir dan parameter pengukuran *mastery*, termasuk sensitivitas bobot dan ambang.
- [ ] Menyusun protokol R6 yang siap direview: kelompok pembanding, instrumen pretest–posttest eksternal, kriteria kontaminasi, dan prosedur pilot.

#### 2. Pekerjaan yang sudah selesai

- **Belum ada commit kode R5 baru atau hasil validasi ahli yang terverifikasi pada 9–13 November.** Capaian teknis terakhir berasal dari periode sebelumnya dan dicatat pada Minggu ke-01 di bawah.
- **Administrasi pelaporan, 15 November (di luar rentang minggu ini):** penyusunan `PROGRESS.md`, tautan pada `README.md`, dan Draft PR dokumentasi untuk review dosen. Tanggalnya dicatat terpisah agar tidak keliru dianggap pekerjaan yang selesai sebelum Jumat.

#### 3. Pekerjaan yang belum selesai / masih error

| Fitur / pekerjaan | Kondisi saat ini | Kendala atau pesan error ringkas | Upaya yang sudah dicoba | Bantuan yang dibutuhkan |
|---|---|---|---|---|
| Validasi pedagogis rekomendasi | **Belum selesai** | Penilaian ahli dan *gold standard* belum tersedia; **bukan error aplikasi** | Protokol 16 skenario disusun pada PR #27 | Konfirmasi rubrik, panel ahli, dan kriteria penerimaan |
| Validasi pengukuran *mastery* | **Belum selesai** | Bobot, cakupan butir, reliabilitas, dan ambang masih `research_draft` | Rumus dan batas keputusan telah didokumentasi, tes numerik ditambahkan | Arahan desain validasi butir dan analisis sensitivitas |
| Protokol dan uji coba R6 | **Belum selesai** | Belum ada hasil eksperimen riil atau pembekuan versi penelitian | Audit substantif dan usulan protokol tersedia dalam `docs/research/` | Keputusan jumlah kelompok, instrumen hasil belajar, dan kelayakan pilot |
| Uji konkurensi assignment pada database produksi | **Belum diuji** | `lockForUpdate()` belum dibuktikan pada transaksi bersamaan MySQL/PostgreSQL | Regresi isolasi dijalankan pada SQLite in-memory | Kesepakatan DB target dan skenario uji aman |
| PR penelitian lama #21–24 | **Masih Draft (administratif)** | PR belum di-merge; riwayat commit sudah tercakup di branch R5 | Perbandingan riwayat branch sudah ditinjau | Keputusan dosen: pertahankan untuk arsip atau tutup dengan penjelasan |

#### 4. Hasil pengujian

| Pengujian | Hasil | Bukti / catatan |
|---|---|---|
| Tes Laravel **khusus 9–13 November** | **BELUM DIUJI ULANG** | Tidak ada run baru yang diverifikasi untuk periode ini |
| GitHub Actions **baseline teknis 6 November** | **PASS (historis)** | [Run #42249592998](https://github.com/Munfiatun/AI-LCMS/actions/runs/42249592998): 559 passed / 3.730 assertions, untuk head PR #27 |
| Frontend build dan oracle numerik (6 November) | **PASS (historis)** | Job Quality Gate yang sama; bukan pengujian UI oleh pengguna nyata |
| Expert judgement dan hasil belajar R6 | **BELUM DIUJI** | Belum ada data lapangan yang dapat dilaporkan |

#### 5. Tindak lanjut review dosen sebelumnya

- [x] Revisi regresi dan audit kode dosen tercatat sebagai [PR #25](https://github.com/Munfiatun/AI-LCMS/pull/25) — *merged* 6 November.
- [x] Isolasi `adaptive` / `non_adaptive` dan tes regresi pada [PR #26](https://github.com/Munfiatun/AI-LCMS/pull/26) — *merged* 6 November.
- [x] Tambahan boundary test, referensi centroid independen, dan protokol validasi pada [PR #27](https://github.com/Munfiatun/AI-LCMS/pull/27) — *merged* 6 November.
- [ ] Bukti validasi isi/pedagogis dan outcome pembelajaran **belum tersedia**; tindak lanjut tetap terbuka.

#### 6. Rencana lima hari kerja berikutnya (2026-11-16 s.d. 2026-11-20)

| Prioritas | Tugas | Kriteria selesai |
|---|---|---|
| **P0** | Bekukan dan audit protokol *treatment fidelity* sebelum pilot; cek assignment, pergantian kelompok, serta akses rekomendasi | Matriks akses dan protokol kontaminasi direview, skenario gagal/berhasil tercatat |
| **P1** | Siapkan dan lakukan telaah 16 skenario rekomendasi oleh pakar pendidikan/Informatika | Rubrik disetujui, lembar penilaian dan alasan pakar terdokumentasi tanpa data siswa nyata |
| **P1** | Evaluasi cakupan item–konsep/prasyarat serta sensitivitas model mastery dan Fuzzy | Tabel contoh, parameter/versi, analisis perubahan keputusan dan batasan tersedia |
| **P2** | Siapkan dokumen R6 dan bukti siklus aplikasi pada data uji terisolasi | Draft protokol, instrumen eksternal, serta log/screenshot yang disamarkan ditautkan pada PR/Issue |

#### 7. Pertanyaan untuk dosen

- Apakah parameter ambang mastery (39,99/79,99) dan minimum dua evidence dapat digunakan untuk pilot awal, atau perlu kalibrasi melalui panel pakar terlebih dahulu?
- Apakah rancangan 16 skenario cukup untuk tahap validasi keputusan, dan siapa saja peran pakar yang diharapkan?
- Apakah PR historis #21–24 sebaiknya ditutup sebagai dokumentasi pekerjaan yang sudah tercakup di branch R5, sementara integrasi kode R5 ke `main` menunggu persetujuan?
- Apakah rancangan pengambilan data R6 (kelompok kelas, pre/post eksternal, dan penanganan perubahan assignment) sudah dapat mulai ditinjau?

---

### Minggu ke-01 (rekonstruksi bukti GitHub) — 2026-11-02 s.d. 2026-11-06

**Tanggal pembaruan historis yang direkonstruksi:** 2026-11-15 (berdasarkan PR/CI bertanggal 6 November; **bukan** klaim laporan ini dipublikasikan pada 6 November)  
**Branch pekerjaan:** `research/r5-ui-ux-simplification`, `adi/r5-regression-review-20261104`, `fix/r5-fallback-test-isolation`, `test/r5-assignment-switch`, `research/r5-adaptive-numeric-validation-20261106`  
**Commit terakhir pada akhir periode:** [`ad0d130`](https://github.com/Munfiatun/AI-LCMS/commit/ad0d130fb247a29722efec8d85b5fccc1b80af07)  
**Pull Request:** [#25](https://github.com/Munfiatun/AI-LCMS/pull/25), [#26](https://github.com/Munfiatun/AI-LCMS/pull/26), [#27](https://github.com/Munfiatun/AI-LCMS/pull/27) — semuanya **merged** ke R5; belum ke `main`.

#### 1. Target minggu ini

> Target direkonstruksi dari perubahan kode, isi PR, dan catatan audit, bukan dari pernyataan bahwa target sudah disepakati sebelum minggu berjalan.

- [x] Menangani regresi AI dan isolasi rekomendasi pada kelompok non-adaptif.
- [x] Menambah kasus pengujian perpindahan assignment dan *research event*.
- [x] Menyiapkan batas numerik Rule-Based dan verifikasi centroid Fuzzy yang dapat direproduksi.

#### 2. Pekerjaan yang sudah selesai

- [x] [PR #25](https://github.com/Munfiatun/AI-LCMS/pull/25): perbaikan regresi kandidat dosen, kontrak output AI, audit dan treatment boundary.
- [x] [PR #26](https://github.com/Munfiatun/AI-LCMS/pull/26): penguatan guard di service/controller serta tiga file tes isolasi assignment. Tes mencakup perpindahan ke kontrol, akses workspace, assessment formative, event snapshot, dan persistensi keputusan.
- [x] [PR #27](https://github.com/Munfiatun/AI-LCMS/pull/27): penambahan tes batas numerik, oracle Python terpisah, dokumen protokol validasi akademik, dan konfigurasi CI untuk PR R5.
- [x] Semua PR tersebut digabungkan ke `research/r5-ui-ux-simplification` dan telah tercatat dalam riwayat GitHub.

#### 3. Pekerjaan yang belum selesai / masih error

| Fitur / pekerjaan | Kondisi saat ini | Kendala atau pesan error ringkas | Upaya yang sudah dicoba | Bantuan yang dibutuhkan |
|---|---|---|---|---|
| Validitas keputusan *mastery*/Rule-Based/Fuzzy | **Belum selesai** | Lulus uji numerik tidak membuktikan validitas pedagogis | Tabel oracle, 16 skenario dan dokumentasi versi disiapkan | Review pakar/rubrik dan analisis kasus tidak sepakat |
| Uji akhir dengan siswa dan data empiris R6 | **Belum diuji** | Belum ada data pre/post atau hasil uji lapangan | Protokol awal teridentifikasi dalam `docs/research/` | Persetujuan desain pilot, etika, serta instrumen hasil belajar |
| Konkurensi dan perubahan assignment di DB produksi | **Belum diuji** | Suite memakai SQLite in-memory; uji transaksi database produksi tidak dilaporkan | Proteksi transaksi + regresi isolasi diimplementasikan | Rencana uji integrasi database target |

#### 4. Hasil pengujian

| Pengujian | Hasil | Bukti / catatan |
|---|---|---|
| PHPUnit suite, commit head PR #27 | **PASS** | [GitHub Actions run #42249592998](https://github.com/Munfiatun/AI-LCMS/actions/runs/42249592998): **559 passed, 3.730 assertions** |
| Frontend production build | **PASS** | [Job Quality Gate yang sama](https://github.com/Munfiatun/AI-LCMS/actions/runs/42249592998) |
| Oracle numerik Fuzzy | **PASS** | [Quality Gate](https://github.com/Munfiatun/AI-LCMS/actions/runs/42249592998); 8 kasus acuan dan grid numerik pada `scripts/research/fuzzy_reference_oracle.py` |
| Uji lapangan dan validasi pakar | **BELUM DIUJI** | Tidak ada bukti empiris untuk diklaim |
| CI ulang **setelah** merge PR #27 | **BELUM TERVERIFIKASI** | PASS di atas berlaku pada commit sebelum merge |

#### 5. Tindak lanjut review dosen sebelumnya

- [x] Tindak lanjut perubahan yang diajukan dosen melalui PR #25 dengan pemeriksaan dan integrasi ke R5.
- [x] Tambahan proteksi kelompok kontrol melalui PR #26.
- [x] Pendalaman bukti numerik dan instrumen validasi melalui PR #27.
- [ ] Persetujuan ahli terhadap ketepatan pedagogis rekomendasi dan parameter belum diperoleh.

#### 6. Rencana lima hari kerja berikutnya

| Prioritas | Tugas | Kriteria selesai |
|---|---|---|
| **P0** | Pertahankan *treatment isolation* dan audit jalur akses selama perubahan assignment | Tidak ada paparan rekomendasi adaptif ke kelompok kontrol pada skenario yang diuji |
| **P1** | Siapkan instrumen validasi ahli dan evaluasi butir/mastery | Skenario dan rubrik siap diberi penilaian serta tercatat versinya |
| **P2** | Persiapkan protokol R6 dan dokumentasi bukti proses | Dokumen rancangan, isu terbuka dan tautan PR tercatat |

#### 7. Pertanyaan untuk dosen

- Bukti validasi pakar dan jenis analisis apa yang perlu diprioritaskan untuk menyatakan mesin adaptif siap diuji pada siswa?
- Apakah algoritma tetap dipertahankan pada parameter `research_draft` sampai expert judgement selesai?

---

<!-- Laporan mingguan selanjutnya diletakkan di atas Minggu ke-02 tanpa menghapus riwayat. Umpan balik dosen dicatat pada komentar PR atau GitHub Issue. Data login/credential dan data siswa tidak boleh masuk ke repository. -->
