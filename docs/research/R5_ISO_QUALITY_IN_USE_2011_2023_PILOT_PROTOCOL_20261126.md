# R5 — Protokol Uji Coba Quality-in-Use AI-LCMS: Model 2011 dan 2023

**Tanggal:** 26 November 2026 (WIB). **PIC teknis:** Munfiatun Nairoh.  
**Status:** INSTRUMEN PILOT SIAP DIUJI / BELUM ADA HASIL RESPONDEN.  
**Objek:** R5 AI-LCMS, branch \`research/r5-ui-ux-simplification\`; evaluasi tidak melibatkan rilis \`main\`.  
**Rujukan internal:** \`docs/research/R2_QUALITY_EVALUATION_PLAN.md\` dan R5 Issue #34.

## 1. Ketepatan standar

- **ISO/IEC 25010:2011** memiliki *quality-in-use* dengan lima karakteristik: **effectiveness, efficiency, satisfaction, freedom from risk, context coverage**.
- **ISO/IEC 25010:2023** mengatur *product quality*, bukan lagi model *quality-in-use*.
- **ISO/IEC 25019:2023** mengatur *quality-in-use* dengan tiga karakteristik: **beneficialness, freedom from risk, acceptability**. Dalam penggunaan langsung, beneficialness mencakup fokus usability (effectiveness, efficiency, satisfaction), accessibility dan suitability; acceptability mencakup experience/trustworthiness/compliance; freedom from risk dinilai sesuai konteks dan jenis risiko.
- **ISO/IEC 25022:2016** adalah acuan pendamping untuk memilih *measures* quality-in-use. Penggunaan model ISO bukan sertifikasi kepatuhan.

Sumber resmi:
- 2011: https://www.iso.org/standard/35733.html
- Model 2023 untuk kualitas produk: https://www.iso.org/standard/78176.html
- Model 2023 untuk kualitas dalam penggunaan: https://www.iso.org/standard/78177.html
- Pengukuran: https://www.iso.org/standard/35746.html
- ISO 9241-11:2018 (usability dan context of use): https://www.iso.org/standard/63500.html

**Jangan menamai penelitian ini "ISO 25010:2023 quality-in-use"** karena nomor tersebut kini untuk *product quality*. Sebut secara lengkap: *perbandingan kerangka quality-in-use ISO/IEC 25010:2011 dan ISO/IEC 25019:2023*.

## 2. Tujuan, pengguna, konteks, dan batas evaluasi

**Tujuan:** mengidentifikasi kualitas interaksi pengguna dengan tugas AI-LCMS pada peran guru dan siswa, lalu memetakan bukti yang sama ke dua model ISO dengan interpretasi yang tepat.

Unit observasi: **satu peserta tersandi × satu tugas × satu sesi** (tidak boleh ada dua baris ganda untuk unit yang sama). Observasi tugas dapat dilakukan menggunakan **akun demo dan data sintetis** lebih dahulu. Sebelum melibatkan guru/siswa nyata, dapatkan izin pengelola/sekolah dan prosedur persetujuan yang relevan; tidak mengunggah identitas, hasil belajar individual, log sensitif atau kredensial ke GitHub.

Konteks minimum yang perlu **ditentukan sebelum pengambilan data**:
1. Guru: desktop/laptop browser dalam skenario persiapan/memonitor pembelajaran.
2. Siswa: laptop/browser dalam skenario belajar, diagnostik, dan kuis.
3. Siswa: ponsel browser (mis. viewport 375px) pada tugas esensial.
4. Untuk accessibility, dokumentasikan penggunaan navigasi keyboard dan, bila tersedia, teknologi asistif dalam konteks yang relevan.

Catat browser/OS, device, jaringan dan versi aplikasi, jangan menyamakan perbedaan konteks dengan perbedaan intervensi. Karakteristik konteks yang belum diuji harus ditandai **NOT OBSERVED**, bukan diperlakukan sukses.

**Pemisahan studi:** eksperimen adaptive versus non-adaptive dan efektivitas hasil belajar R6 **tidak** dibuktikan oleh uji *quality-in-use*. Pada uji role siswa yang belum mendapat otorisasi protokol, gunakan data **demo**, bukan peserta penelitian asli.

## 3. Paket tugas observasi yang dapat direplikasi

Instruktur/guru:
- **G01:** login dengan akun demo dan buka dashboard; identifikasi kursus target.
- **G02:** buka materi dan periksa kaitan tujuan pembelajaran (LO) dengan materi/soal.
- **G03:** publikasikan atau tinjau kuis demo dan temukan status kesiapan.
- **G04:** buka pemantauan progres/mastery peserta demo, lalu interpretasikan satu indikator.
- **G05:** buka rekomendasi untuk siswa demo adaptif, lalu jelaskan target/alasan tanpa melihat expected answer; operator menilai ketepatan interpretasi.

Siswa:
- **S01:** login akun demo dan menemukan kursus yang diikuti.
- **S02:** buka materi/Slidebook demo dan maju/kembali menggunakan kontrol.
- **S03:** mulai/selesaikan asesmen demo atau diagnostik (skor bukan outcome penelitian).
- **S04:** temukan hasil umpan balik/progres setelah asesmen demo.
- **S05A:** dalam **akun demo adaptif**, lihat rekomendasi dan pilih kegiatan yang disarankan.
- **S05C:** dalam **akun demo non-adaptive**, lanjutkan aktivitas biasa tanpa paparan rekomendasi adaptif.

**Penjagaan R5:** S05A dan S05C adalah *kondisi demo terpisah*, tidak boleh memindahkan siswa riil lintas kelompok, dan akses kontrol via direct URL serta event harus diinspeksi secara terpisah. Kesesuaian rekomendasi secara pedagogis tetap membutuhkan validasi ahli (PR #35).

Untuk tiap tugas tetapkan *success condition* tertulis dan waktu mulai/berakhir. Jika fitur tidak ditemukan/tersedia, catat gagal atau N/A dengan alasan; **jangan mengubah fitur saat sesi pengukuran** tanpa mencatat versi baru.

## 4. Matriks operasional dan pemetaan dua model

| Hasil/observasi sama | ISO/IEC 25010:2011 | ISO/IEC 25019:2023 | Ukuran/kriteria yang harus didefinisikan |
| --- | --- | --- | --- |
| Tugas selesai sesuai kondisi sukses dan tanpa bantuan kritis | Effectiveness | Beneficialness → Usability | *Completion rate* = jumlah tugas berhasil / jumlah tugas valid; uraikan kegagalan |
| Durasi tugas dan kesalahan langkah | Efficiency | Beneficialness → Usability | Median waktu (detik), IQR/mean jika relevan, error/assistance count; bandingkan hanya tugas setara |
| Kepuasan pascatugas (1–5) | Satisfaction | Beneficialness → Usability | Distribusi/median dan alasan, bukan label otomatis "lulus ISO" |
| Kebutuhan keyboard/assistive dan hambatan akses | Konteks dan kepuasan/effectiveness terkait | Beneficialness → Accessibility | Task accessibility rating, barrier count, observasi pengguna dengan kebutuhan relevan |
| Kesesuaian fitur dengan tujuan tugas | Effectiveness (terkait) | Beneficialness → Suitability | Rating suitability (1–5) + catatan masalah task-fit |
| Peristiwa kehilangan akses, kebocoran, kesalahan nilai, ketidakamanan | Freedom from risk | Freedom from risk | Jumlah dan severity insiden, tindak korektif; cakupan risiko yang dipilih terdokumentasi |
| Tugas dinilai per lingkungan yang ditentukan | Context coverage | Context of use sebagai **prasyarat**, bukan characteristic yang identik | Matriks context × task; sukses/gagal/belum dites per konteks |
| Kejelasan, kepercayaan dan pengalaman menggunakan AI-LCMS | Satisfaction (sebagian) | Acceptability → Experience / Trustworthiness | Rating pengalaman dan trust (1–5), wawancara, alasan peran; jangan menyamakan trust dengan reliabilitas terbukti |
| Penerimaan prosedur dan tata kelola (izin, batas data) | Freedom from risk (sebagian) | Acceptability → Compliance | Checklist audit tata kelola yang disepakati sebelum sesi, bukan skor Likert semata |

**Tidak ada pemetaan satu-ke-satu** antara kelima karakteristik 2011 dan ketiga karakteristik 2023. Nilai agregat "ISO score 90%" **tidak valid** tanpa definisi dan pembobotan metodologis tersendiri. Model 2023 menuntut konteks, acceptability dan dampak terhadap stakeholder diperhatikan secara eksplisit.

## 5. Instrumen dan skema pengambilan data

Template CSV di root folder ini:
- \`ISO_QIU_TASK_OBSERVATIONS_TEMPLATE.csv\` — satu baris pengamatan per tugas per peserta tersandi, rating pascatugas, hasil, insiden.
- \`ISO_QIU_CONTEXT_COVERAGE_TEMPLATE.csv\` — tiap kombinasi role/task/context yang **direncanakan**, ada/tidaknya observasi dan keputusan menerima konteks.
- \`ISO_QIU_GOVERNANCE_CHECKLIST_TEMPLATE.csv\` — *reviewer-only* untuk persetujuan dan risiko data, tidak diisi sebagai data siswa.

Hanya *participant_id* pseudonim (mis. kode internal penelitian), role, condition demo, task_id, context_id, status, durasi, jumlah error/bantuan, rating 1–5 bila diberikan, severity insiden, dan catatan **tanpa nama** yang boleh dikumpulkan. Kosongkan rating yang tidak dijawab, jangan isi dengan angka nol. Pisahkan data identitas/persetujuan pada penyimpanan berizin, tidak di GitHub.

### Petunjuk skala dan waktu
- Completed: \`1\` bila kondisi sukses terverifikasi, \`0\` jika tidak; \`na\` hanya bila tugas memang tidak berlaku bagi peran/context.
- \`time_seconds\`: detik dari instruksi mulai hingga sukses/gagal/stop, diukur konsisten; kosong jika tidak diamati.
- \`errors\`: tindakan salah yang teramati, bukan semua klik.
- \`assistance_count\`: intervensi fasilitator setelah mulai, dicatat eksplisit.
- Rating \`satisfaction_1to5\`, \`accessibility_1to5\`, \`suitability_1to5\`, \`experience_1to5\`, \`trust_1to5\`: 1 paling rendah, 5 paling tinggi, hanya jika dijawab asli.
- \`risk_severity\`: none / low / medium / high / critical (ditentukan oleh rubrik operator; klasifikasi disetujui sebelum studi).
- \`context_id\`: hanya kode yang sudah ditentukan di matriks konteks; jangan merekayasa context yang tidak diamati.

## 6. Prosedur pelaksanaan

1. **Freeze kandidat aplikasi untuk sesi uji**: dokumentasikan commit SHA \`research/r5-ui-ux-simplification\` *setelah merge*, timestamp, \`config/adaptive.php\` dan browser. Uji pada server demo/staging dengan **data sintetis**. Jangan menjalankan \`migrate:fresh\` pada database sekolah/produksi.
2. **Pilot instrumen** dengan data demo dan operator internal. Periksa apakah condition sukses, skala rating, timing dan skenario cukup jelas; revisi instrumen diberi versi.
3. Setelah persetujuan berwenang, **rekrut sampel relevan** untuk evaluasi guru dan siswa; catat jumlah, rekrutmen, tingkat pengalaman, izin, assistive needs tanpa mengekspos identitas.
4. **Briefing seragam**: peran, tugas, tujuan observasi, peserta dapat berhenti; tidak menjanjikan hasil belajar atau AI "benar".
5. **Observasi**: fasilitator mengukur waktu, kesalahan, tugas berhasil, kejadian tidak aman; peserta memberi rating pascatugas/komentar; observer tidak memberi bantuan kecuali dicatat.
6. **Beri konteks dengan benar**: akun demo adaptif dan kontrol dipisah; siswa kontrol tidak ditunjukkan rationale/rekomendasi adaptif. Hapus/reset hanya data **demo yang sengaja dibuat dan telah dipastikan isolated**, bukan data sekolah.
7. **Analisis** per role, task, context dan kondisi demo bila relevan; laporkan N, kegagalan, missingness, median durasi, rating, risk severity dan risiko kritis. Perbandingan silang ISO bersifat *mapping conceptual*, bukan dua angka langsung sebanding.
8. **Kriteria terima** ditetapkan **sebelum data peserta dikumpulkan** bersama dosen/operator. Untuk fitur kritis, nol kejadian kebocoran/akses tidak sah adalah target pengamanan; angka keberhasilan lainnya harus dibenarkan oleh konteks, bukan ambang ISO universal.
9. Catat revisi UI/UX dan lakukan *retest* pada SHA baru; jangan mencampur nilai dari versi aplikasi berbeda tanpa analisis.

## 7. Ringkasan hasil (template; jangan isi dengan data fiktif)

| Hasil | Guru | Siswa | Status saat dokumen dibuat |
| --- | --- | --- | --- |
| Peserta nyata (N) | — | — | NOT COLLECTED |
| Jumlah tugas valid dan sukses | — | — | NOT COLLECTED |
| Median waktu tugas | — | — | NOT COLLECTED |
| Median kepuasan | — | — | NOT COLLECTED |
| Hambatan aksesibilitas | — | — | NOT COLLECTED |
| Insiden risiko kritis | — | — | NOT COLLECTED |
| Matriks context coverage | — | — | NOT COLLECTED |
| Pengalaman / trust / suitability | — | — | NOT COLLECTED |

**Kesiapan teknis:** [Quality Gate PR #33](https://github.com/Munfiatun/AI-LCMS/actions/runs/45547243158) PASS 571 tests / 3.794 assertions; [MySQL two-connection CI](https://github.com/Munfiatun/AI-LCMS/actions/runs/45547243037) PASS 3 tests / 14 assertions pada database disposable. **Bukan** bukti quality-in-use pengguna. R5 merge untuk demo tidak sama dengan R6 main experiment.

## 8. Batas inferensi dan tanggung jawab

Uji *quality-in-use* menilai pengalaman pengguna mencapai tujuan pada konteks tertentu. Ini **bukan** pembuktian efek adaptasi terhadap pembelajaran, validitas pedagogis keputusan AI, ISO certification, maupun kesiapan produksi. Risiko terhadap kerahasiaan peserta harus ditinjau. Jika penilaian ahli terhadap rekomendasi belum diselesaikan, batasi task interpretasi AI pada **akun demo dan reviewer berwenang**.

**Sumber penelitian tambahan**: ISO/IEC 25022:2016; ISO 9241-11:2018; protokol internal R2 dan R5. Gunakan teks standar resmi berlisensi saat menyusun disertasi dan verifikasi terminologi/subkarakteristik; ringkasan ini merupakan pemetaan operasional yang diusulkan, bukan reproduksi standar lengkap.
