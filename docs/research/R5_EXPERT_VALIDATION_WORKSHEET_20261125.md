# R5 — Instrumen Validasi Ahli: 16 Skenario Rekomendasi (Blind Review)

**Disusun:** 25 November 2026. **Status:** DRAFT FORMULIR, belum mendapat hasil/izin/validasi ahli.  
**Sumber skenario:** bagian 5 dari **R5_ADAPTIVE_NUMERIC_PEDAGOGICAL_VALIDATION_PROTOCOL_20261106.md** pada branch R5.  
**Unit kasus:** profil sintetis siswa–konsep–prasyarat, tanpa nama/identitas pelajar nyata.

## A. Prosedur penilaian (jangan tunjukkan keputusan mesin saat blind)

1. Peneliti melengkapi tujuan pembelajaran pemrograman dasar, ringkasan evidence/jenis dan kualitas butir, serta peta prasyarat dan kandidat material **secara ekuivalen untuk semua kasus**. Angka pada tabel adalah sintetis; bukan hasil siswa.
2. Bentuk panel relevan (usulan minimal **3 pakar**, misalnya guru Informatika SMK, ahli evaluasi pembelajaran dan ahli teknologi pembelajaran; jumlah/komposisi final harus disetujui dosen).
3. Setiap pakar menerima **hanya profil kasus**, belum nama mesin Rule-Based/Fuzzy, expected action, centroid, atau reason_code.
4. Pakar secara independen menilai kecukupan data, action terbaik, target konsep, risiko, keyakinan, dan alasan pedagogis. Pakar boleh menyatakan informasi tidak cukup dan meminta revisi skenario.
5. Kumpulkan penilaian asli pakar lalu adjudikasi perbedaan secara transparan. **Kunci keputusan gold standard sebelum membuka keluaran algoritma**; simpan penilaian individual, tanggal, versi rubrik dan alasan adjudikasi.
6. Barulah bandingkan Rule-Based v1 dan Fuzzy Mamdani v2 pada skenario sama: action/target, reason, fuzzy membership, firing rule, centroid, selected output vs fallback action. Jangan menganggap kesesuaian dua algoritma sebagai akurasi.

## B. Matriks 16 skenario untuk pakar (tanpa jawaban mesin)

| ID | Mastery | Evidence | Prasyarat / alur | Fokus evaluasi |
| --- | ---: | ---: | --- | --- |
| S01 | 0 | 0 | Tanpa prasyarat | Tidak ada evidence |
| S02 | 39,99 | 2 | Tanpa prasyarat | Batas rendah bawah |
| S03 | 40,00 | 2 | Tanpa prasyarat | Batas rendah atas |
| S04 | 79,99 | 2 | Tanpa prasyarat | Batas tinggi bawah |
| S05 | 80,00 | 2 | Tanpa prasyarat | Batas tinggi atas |
| S06 | 90,00 | 1 | Tanpa prasyarat | Evidence minim |
| S07 | 90,00 | 2 | Satu prasyarat belum dinilai | Data prasyarat belum ada |
| S08 | 90,00 | 2 | Prasyarat 69,99 | Batas prasyarat bawah |
| S09 | 90,00 | 2 | Prasyarat 70,00 | Batas prasyarat atas |
| S10 | 90,00 | 3 | Prasyarat 30 | Prasyarat lemah |
| S11 | 90,00 | 5 | Prasyarat 100 | Mastery dan evidence tinggi |
| S12 | 60,00 | 3 | Dua prasyarat 40 dan 85 | Prioritas target |
| S13 | 90,00 | 3 | Konsep akhir tanpa next | Fallback |
| S14 | 90,00 | 3 | Konsep berikut punya dua prasyarat | Ketergantungan ganda |
| S15 | 60,00 | 3 | Prasyarat 65 | Overlap partial/mastered |
| S16 | 40,00 | 3 | Prasyarat 100 | Overlap low/medium |

**Sebelum disebarkan:** isi setiap kasus dengan LO, contoh butir, bobot/tingkat kesulitan dan kesesuaian materi, jenis asesmen diagnostic/formative, jumlah evidence sesuai tabel, prasyarat serta opsi intervensi yang tersedia. Jangan mengarang evidence pada kasus S01, S06, S07.

## C. Lembar individual per ahli × kasus (salin sebanyak 16 lembar per ahli)

| Kolom | Penilaian asli |
| --- | --- |
| ID pakar terkode, tanggal, versi rubrik dan kasus | ______ |
| Kode/tujuan pembelajaran yang dinilai | ______ |
| Kecukupan informasi profil kasus (1–5) | ______ |
| Kecukupan evidence untuk keputusan (1–5) | ______ |
| **Rekomendasi action terbaik** | additional_diagnostic / prerequisite_remediation / remediation / reinforcement / next_concept / enrichment / insufficient_information / lainnya + alasan |
| **Target concept** | ______ |
| Alasan pedagogis, bukti dan alternatif | ______ |
| Risiko over/under intervention | rendah / sedang / tinggi + alasan |
| Keyakinan (1–5) | ______ |
| Kesesuaian mapping item–LO dan prasyarat | (1–5) + catatan |
| Kategori bermasalah/ambigu yang perlu revisi | ya / tidak + alasan |

Skala 1 = sangat tidak memadai/tidak yakin; 5 = sangat memadai/sangat yakin. Jika memilih insufficient_information, jangan memaksa target atau action alternatif. Jangan mempublikasikan identitas personal pakar tanpa izin.

## D. Adjudikasi setelah blind (WAJIB tetap kosong sampai ada penilaian nyata)

| Case | Action gold standard | Target gold standard | Pakar setuju/total | Perbedaan/alasannya | Keputusan dan tanggal |
| --- | --- | --- | --- | --- | --- |
| S01–S16 (satu baris per kasus) | **BELUM DIISI** | **BELUM DIISI** | **BELUM DIISI** | **BELUM DIISI** | **BELUM DIISI** |

Simpan semua data penilai individual, bukan hanya consensus, untuk menghitung inter-rater agreement sesuai jenis skala dan jumlah penilai.

## E. Audit output mesin sesudah gold standard dikunci

Catat tiap algoritma per skenario: case_id, case_version, algorithm_code, algorithm_version, policy/config SHA, input mastery/evidence/prerequisite, firing_rules, membership degrees, crisp/centroid, selected_output, final_action_after_fallback, target, reason_code, gold_action, gold_target, agreement_action/target, dan penjelasan discrepancy.

Bahan pembanding numerik Fuzzy dari protokol R5 (bukan hasil ahli):

| Mastery / evidence / prerequisite | Crisp referensi | Selected output |
| --- | ---: | --- |
| 20 / 3 / 100 | 45,0000 | remediation |
| 40 / 3 / 100 | 50,8528 | remediation |
| 60 / 3 / 100 | 60,0000 | reinforcement |
| 80 / 3 / 100 | 77,3333 | next_concept |
| 90 / 1 / 100 | 8,1111 | additional_diagnostic |
| 90 / 4 / 100 | 77,2222 | next_concept |
| 90 / 5 / 100 | 93,4444 | enrichment |
| 90 / 3 / 30 | 27,3333 | prerequisite_remediation |

Fuzzy selected_output bisa berbeda dari action akhir bila tidak tersedia next concept. Analisis yang diusulkan: exact agreement action dan target terhadap adjudicated gold standard, confusion matrix, kasus divergence Rule-Based vs Fuzzy, inter-rater agreement yang dipilih sesuai skala, sensitivitas ambang 39,99/40,00/79,99/80,00, evidence minimal 2, prerequisite 69,99/70,00 serta perubahan membership Fuzzy ±5 poin di konfigurasi **uji** (bukan mengganti parameter produksi diam-diam).

## F. Gate instrumen

- [ ] Peneliti melengkapi LO dan contoh item serta prasyarat per kasus.
- [ ] Dosen mereview rubrik, jumlah dan kompetensi pakar.
- [ ] Pakar bersedia dan melakukan penilaian individual **tanpa melihat jawaban mesin**.
- [ ] Data asli, komentar, adjudikasi dan cap waktu disimpan aman.
- [ ] Hasil agreement dan perbandingan algoritma dihitung; deviasi dibahas.
- [ ] Jika konfigurasi revisi, definisikan identitas algoritma baru, jalankan regression ulang, minta persetujuan versi.

**Status 25 November:** TEMPLATE PREPARED; belum ada skor, agreement, penilaian ahli atau pengesahan validitas pedagogis.