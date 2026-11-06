# R5 — Protokol Validasi Numerik dan Pedagogis Adaptive Learning

Tanggal penyusunan: 6 November 2026 (WIB)  
Basis implementasi: `research/r5-ui-ux-simplification` @ `0012cf728c81df811a8ec1fd09c66eaa851aca8e`  
Cakupan: `ConceptMasteryService`, Rule-Based v1, Fuzzy Mamdani v2, dan persiapan expert gold standard.  
Status: **VALIDATION IN PROGRESS**. Berkas ini merupakan rancangan pengujian dan contoh perhitungan independen, **bukan** klaim algoritma sudah valid secara pedagogis.

## 1. Pemisahan empat jenis bukti

| Jenis bukti | Pertanyaan utama | Bukti yang diperlukan |
| --- | --- | --- |
| Kebenaran implementasi | Apakah keputusan sesuai parameter dan kode? | Feature test, boundary test, reproducibility, lintasan eksekusi |
| Kebenaran numerik | Apakah fuzzy inference menghasilkan centroid dan firing strength yang benar? | Perhitungan referensi independen, nilai crisp dengan toleransi, rule coverage |
| Validitas isi/pedagogis | Apakah skenario, item-konsep, prasyarat dan rekomendasi masuk akal bagi siswa SMK? | Panel ahli, rubrik, konsensus/gold standard dan alasan koreksi |
| Efektivitas pembelajaran | Apakah treatment meningkatkan hasil belajar? | Pilot/eksperimen R6 dengan instrumen outcome eksternal dan kontrol kontaminasi |

PASS otomatis pada dua baris pertama **tidak** menetapkan hasil pada dua baris terakhir.

## 2. Rumus dan batas model mastery yang dipakai saat ini

**Dari kode:** `app/Services/Learning/ConceptMasteryService.php`; konfigurasi `config/mastery.php`.

Untuk konsep `c`, jawaban valid `i` memberi bobot `w_i = max(0, bobot_item-konsep) × max(0, bobot_jenis_asesmen)`. Skor:

`mastery_c = round(100 × (Σ_i w_i × I(jawaban_i benar)) / (Σ_i w_i), 2)`, bila `Σ_i w_i > 0`.

`evidence_count` menghitung banyaknya jawaban terkait konsep dengan bobot positif; **bukan** jumlah soal unik lintas semua percobaan yang dijamin independen. Konfigurasi saat ini memakai bobot diagnostic=1.0, formative=1.0; summative tidak ikut serta. Diagnostic membentuk baseline, formative berikutnya boleh memperbarui baseline; formative tanpa baseline tidak membentuk mastery. Kode mencatat `MasteryHistory`, versi model, skor sebelum/sesudah, bobot dan jumlah evidence.

**Contoh perhitungan tangan (untuk verifikasi rumus, bukan data siswa nyata):**

- Dua jawaban pada konsep yang sama: satu benar berbobot 0,5; satu salah berbobot 1,0 → `100 × 0,5/(0,5 + 1,0) = 33,33`; `evidence_count = 2`.
- Dua diagnostic salah diikuti dua formative benar dengan bobot item=1 dan bobot assessment=1 → `100 × 2/4 = 50,00`; `evidence_count = 4`.
- Skor dapat sama untuk item dengan kesulitan dan cakupan berbeda. **Persentase ini bukan estimasi penguasaan psikometrik tervalidasi** sampai kualitas item, cakupan konstruk, reliabilitas dan sensitivitas parameter diuji.

**Catatan penting:** `config/mastery.php` memakai `minimum_evidence_count=1` untuk menandai status learner model; Rule-Based menggunakan `minimum_evidence_count=2` untuk izin mengambil keputusan (dua fungsi berbeda). Parameter masih `research_draft`.

## 3. Tabel oracle batas Rule-Based v1

Konfigurasi efektif saat dokumen dibuat: minimum evidence=2; `low_max=39.99`; `medium_max=79.99`; `prerequisite_minimum=70.00`. Untuk baris tanpa prasyarat/tanpa konsep berikut yang siap:

| Mastery | Evidence | Kondisi | Expected action | Expected reason |
| ---: | ---: | --- | --- | --- |
| 39,99 | 2 | tanpa prasyarat | `remediation` | `mastery_low` |
| 40,00 | 2 | tanpa prasyarat | `reinforcement` | `mastery_medium` |
| 79,99 | 2 | tanpa prasyarat | `reinforcement` | `mastery_medium` |
| 80,00 | 2 | tanpa prasyarat dan tanpa next ready | `enrichment` | `mastery_high_no_next_ready` |
| 90,00 | 1 | evidence saat ini kurang | `additional_diagnostic` | `current_evidence_insufficient` |
| 90,00 | 2 | prasyarat evidence=1 | `additional_diagnostic` | `prerequisite_evidence_insufficient` |
| 90,00 | 2 | prasyarat score=69,99 dan evidence=2 | `prerequisite_remediation` | `prerequisite_below_threshold` |
| 90,00 | 2 | prasyarat score=70,00 dan evidence=2, tanpa next ready | `enrichment` | `mastery_high_no_next_ready` |

Tambahan yang wajib ditinjau bersama ahli: konsep dengan **lebih dari satu** prasyarat (prioritas target), kemungkinan graf prasyarat siklik, konsep berbeda course, urutan konsep setara, dan efek perubahan batas `low_max`/`medium_max` ±5 poin. Tidak semua kasus ini ditutup oleh test baru pada PR ini.

## 4. Oracle numerik Fuzzy Mamdani v2

**Dari kode/config:** `FuzzyAdaptiveEngine` memakai membership triangle/trapezoid; AND = **minimum**; agregasi antarrule output = **maximum**; implikasi output = **minimum(aktivasi, membership output)**; centroid diskret `x=0..100` step=1.

Rumus acuan:

`α_r = min( μ_i(x_i) untuk semua kondisi rule r )`

`μ_output(x) = max_r min(α_r, μ_consequent_r(x))`

`crisp = Σ_{x=0}^{100} x μ_output(x) / Σ_{x=0}^{100} μ_output(x)`

Jika penyebut nol, kode memakai `0.0`. Setelah crisp dihitung, `selected_output` dipilih lewat fungsi membership **pada titik crisp**, bukan langsung dari keluaran dengan aktivasi maksimum; bila perlu ada fallback. Hasil akhir `action` dapat berbeda dari `selected_output` jika target `next_concept` tidak tersedia.

**Nilai acuan independen:** dihitung terpisah dari implementasi Laravel dengan evaluasi numerik fungsi keanggotaan dan 15 rules pada `config/adaptive.php`. Angka berikut adalah *reference cases*, belum hasil pengujian PHPUnit baru.

| Mastery | Evidence | Prerequisite | Crisp (4 desimal) | Selected fuzzy output |
| ---: | ---: | ---: | ---: | --- |
| 20 | 3 | 100 (tidak ada prasyarat) | 45,0000 | `remediation` |
| 40 | 3 | 100 | 50,8528 | `remediation` |
| 60 | 3 | 100 | 60,0000 | `reinforcement` |
| 80 | 3 | 100 | 77,3333 | `next_concept` |
| 90 | 1 | 100 | 8,1111 | `additional_diagnostic` |
| 90 | 4 | 100 | 77,2222 | `next_concept` |
| 90 | 5 | 100 | 93,4444 | `enrichment` |
| 90 | 3 | 30 | 27,3333 | `prerequisite_remediation` |

Toleransi pengujian yang disarankan untuk keluaran yang dibulatkan empat desimal: ±0,00015. Untuk contoh `selected_output=next_concept` yang **tidak memiliki konsep lanjutan**, hasil `action` aktual bisa `enrichment` dengan metadata `fallback_current_concept`.

**Keterbatasan:** angka acuan berasal dari aturan/parameter yang sama dengan aplikasi (namun implementasi aritmetika terpisah), jadi membuktikan konsistensi numerik terhadap spesifikasi yang dibekukan, **bukan** kebenaran pedagogis parameter. Uji sensitivitas (perubahan membership ±5 poin), rule coverage, selected-label ambigu, defuzzification denominator=0, serta order/target harus tetap dilaksanakan.

## 5. Rancangan expert gold standard (belum dilaksanakan)

Unit penilaian adalah **skenario profil siswa–konsep–prasyarat** yang dianonimkan; jangan tampilkan nama algorithm atau keputusan mesin terlebih dahulu kepada pakar.

Skenario minimum yang disiapkan:

1. Mastery 0, evidence 0, tanpa prasyarat.
2. Mastery 39,99, evidence 2.
3. Mastery 40,00, evidence 2.
4. Mastery 79,99, evidence 2.
5. Mastery 80,00, evidence 2.
6. Mastery 90, evidence 1.
7. Mastery 90, evidence 2, satu prasyarat belum dinilai.
8. Mastery 90, evidence 2, prasyarat 69,99.
9. Mastery 90, evidence 2, prasyarat 70,00.
10. Mastery 90, evidence 3, prasyarat 30.
11. Mastery 90, evidence 5, prasyarat 100.
12. Mastery 60, evidence 3, dua prasyarat dengan score 40 dan 85.
13. Mastery 90, evidence 3, konsep akhir tanpa jalur lanjut.
14. Mastery 90, evidence 3, konsep selanjutnya berprasyarat ganda.
15. Mastery 60, evidence 3, prasyarat 65 (overlap partial/mastered).
16. Mastery 40, evidence 3, prasyarat 100 (overlap membership low/medium).

**Instrumen per skenario (diisi tiap ahli):** ID kode tanpa identitas siswa; tujuan pembelajaran; ringkasan hasil diagnostic/formative, jumlah/tingkat kesulitan item; status prasyarat; rekomendasi action terbaik yang dipilih ahli (enam pilihan sistem atau `lainnya + alasan`); target concept; alasan pedagogis; tingkat keyakinan (1–5); kecukupan data (1–5); kesesuaian rekomendasi mesin setelah *unblinding* (1–5); dan flag berisiko/ambigu.

**Prosedur yang disarankan:** minimal tiga pakar relevan (misalnya guru Informatika SMK, ahli evaluasi pembelajaran dan ahli teknologi pembelajaran), penilaian individual dan *blinded* dulu, diskusi ketidaksepakatan kemudian. Simpan keputusan individual serta hasil adjudikasi dengan timestamp dan versi rubrik. Jangan mengubah `gold standard` secara retroaktif berdasarkan output algoritma.

**Analisis yang direncanakan (belum ada nilai aktual):** agreement exact untuk action/target terhadap hasil adjudikasi; confusion matrix; kelompok kasus dengan divergence Rule-Based–Fuzzy; ringkasan rating relevansi/kejelasan; inter-rater agreement sesuai skala penilaian; serta interval ketidakpastian yang sesuai jumlah kasus. Jangan menyebut `full_agreement_rate` dua algoritma sebagai accuracy.

## 6. Bukti pelaksanaan dan gate penerimaan

- Jalankan secara terisolasi: `php artisan test --compact tests/Feature/AdaptiveNumericBoundaryValidationTest.php`.
- Lanjutkan `php artisan test --compact` dan `npm run build`. Simpan commit SHA, environment PHP/Laravel, database test, total tests/assertions, tanggal dan log.
- Catat apakah angka centroid referensi disepakati oleh perhitungan independen kedua dan apakah ada hasil FAIL; revisi **parameter** hanya dengan alasan teoritis/hasil expert dan versioning.
- Untuk validitas isi: lampirkan 16 formulir skenario × penilaian ahli, konsensus, perbaikan mapping item–konsep/prasyarat, serta ringkasan perbedaan algoritma.
- Untuk R6: bekukan versi algoritma, assignment, rubrik, instrumen eksternal dan prosedur menangani perubahan kondisi eksperimen; minta peninjauan etika sesuai prosedur institusi.

## 7. Hasil yang **belum** boleh diklaim

Dokumen dan tes pada branch ini tidak membuktikan: skor mastery reliabel/valid, threshold ideal, membership fuzzy optimal, rekomendasi dinilai benar oleh pakar, peningkatan hasil belajar siswa, atau superioritas fuzzy atas rule-based. Klaim-klaim itu menunggu expert judgement, pilot lapangan, dan desain evaluasi R6 yang disetujui.
