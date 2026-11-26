# R6 — Draft Protokol Kuasi-Eksperimen Pembelajaran Adaptif AI-LCMS

**Tanggal:** 25 November 2026 (WIB).  
**Rujukan internal:** R2_EXPERIMENT_DESIGN.md, R2_DATA_INSTRUMENT_PLAN.md, R5_ADAPTIVE_NUMERIC_PEDAGOGICAL_VALIDATION_PROTOCOL_20261106.md, dan Issue #34.  
**Status:** **DRAFT / NOT FROZEN / NOT APPROVED / NO-GO UNTUK STUDI LAPANGAN**.

## A. Pertanyaan dan perlakuan penelitian

Pertanyaan awal: apakah hasil belajar eksternal siswa SMK pada tujuan pemrograman dasar berbeda antara penggunaan AI-LCMS dengan mekanisme adaptif yang dibekukan dan AI-LCMS tanpa mekanisme adaptif, setelah mempertimbangkan kemampuan awal?

- **Eksperimen:** aplikasi AI-LCMS dengan treatment rekomendasi adaptif, bisa berupa remediasi, penguatan, pengayaan dan reassessment menurut policy versi yang disetujui.
- **Kontrol:** **platform AI-LCMS yang sama** dengan sistem rekomendasi adaptif dimatikan, tetapi materi/kuis/fungsi LMS umum tetap tersedia secara sebanding.
- **Outcome primer:** skor **posttest eksternal** dari tujuan pemrograman dasar, bukan mastery internal (yang merupakan bagian dari mesin treatment).
- **Baseline:** skor pretest eksternal.
- **Outcome sekunder/proses:** mastery internal, recommendation generated vs exposed, action followed, reassessment, completion/progress, waktu bila valid, keputusan guru dan usability; tidak menggantikan outcome primer.

## B. Desain dan sampel yang harus ditetapkan dosen/sekolah

Default dari R2: **quasi-experimental intact-class pretest–posttest** bila randomisasi individu tidak dapat dilakukan. Pastikan guru (bila memungkinkan), tujuan pembelajaran, materi, kesempatan berlatih, waktu dan cakupan asesmen setara.

| Parameter | Usulan | Status |
| --- | --- | --- |
| Populasi/sekolah/kriteria inklusi | SMK pemrograman dasar; tentukan institusi dan syarat peserta | PENDING |
| Jumlah kelas/cluster per kondisi | Usahakan beberapa kelas per kondisi bila tersedia | PENDING |
| Besar sampel dan justifikasi power | Disesuaikan struktur cluster dan outcome primer | PENDING |
| Alokasi kelas dan jadwal | Catat mekanisme penetapan kelompok, kelas, guru dan jadwal | PENDING |
| Materi, LO, durasi dan guru | Setara semaksimal mungkin | PENDING |
| Jadwal tes dan treatment | Pretest sebelum dan posttest setelah intervensi | PENDING |

**Peringatan validitas:** satu kelas adaptif vs satu kelas kontrol membuat perlakuan tercampur dengan efek kelas. Signifikansi statistik atau ANCOVA tidak menghilangkan confounding tersebut; laporan harus membatasi kesimpulan atau memilih desain lain yang dibenarkan.

## C. Instrumen outcome eksternal

Kisi-kisi disusun dari tujuan belajar dan kompetensi yang sah. Pretest/posttest mengukur domain tujuan sama namun tidak berasal dari output internal mastery. Instrumen memerlukan validitas isi, analisis butir, kejelasan skor, pemeriksaan kesetaraan dan reliabilitas yang sesuai. Jika butir sama, bahas efek ingatan; jika bentuk berbeda, uji ekuivalensinya.

| LO | Indikator | Materi/tingkat kognitif | Butir pre | Butir post | Bobot | Validasi ahli | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| BELUM DITENTUKAN | BELUM DITENTUKAN | BELUM DITENTUKAN | - | - | - | BELUM | DRAFT |

Tetapkan aturan nilai hilang/tidak hadir sebelum studi; data dummy hanya untuk testing software, bukan hasil penelitian.

## D. SOP fidelity, assignment, override, kontaminasi

1. Assignment student×course, kondisi adaptive/non_adaptive, class_cluster, dan assignment_version diputuskan sebelum treatment berdasarkan protokol yang disetujui.
2. Adaptive: simpan bukti rekomendasi **generated** dan **exposed** secara terpisah; catat keputusan diikuti, reassessment serta snapshot policy/version.
3. Kontrol: tidak boleh menerima paparan rekomendasi adaptif melalui UI, direct URL, background generator, event atau instruksi guru yang membocorkan perlakuan.
4. Guru dapat memberi bantuan yang diperlukan demi pendidikan siswa, tetapi override/reassignment selama studi harus dicatat *siapa, kapan, alasan, dampak pada treatment* sebagai deviation. **Kebijakan perpindahan kelompok setelah treatment dimulai belum disetujui**.
5. Rekam potensi kontaminasi: rekomendasi dibagikan antarsiswa/kelas, bantuan berbeda dari guru, jadwal, akses materi silang, dan perbedaan asesmen.
6. Uji concurrency nyata dua koneksi pada DB staging dan E2E control isolation sebelum pilot; lulusnya tes SQLite saja tidak cukup.

## E. Strategi analisis awal (menunggu sampel dan asumsi)

- Analisis utama: hasil posttest eksternal dengan pretest sebagai kovariat pada kelompok penugasan, bila desain dan asumsi mendukung. Kandidat: **Posttest = Condition + Pretest + kovariat terencana** (ANCOVA/regresi).
- Bila cukup banyak cluster, pertimbangkan model multilevel/mixed-effects untuk korelasi intrakelas.
- Periksa linearitas, residual, kemiringan regresi, pencilan, missingness, floor/ceiling, class effects dan sensitivitas.
- Laporan meliputi jumlah siswa **dan kelas** per kondisi, pre/post dengan dispersi, estimasi selisih teradjust, 95% CI, ukuran efek, attrition dan kepatuhan treatment; N-gain dapat dilaporkan deskriptif, bukan satu-satunya uji.
- Pisahkan hasil efektivitas belajar dari validasi rekomendasi pakar dan audit kualitas teknis.

## F. Data, izin, dan etik

Minimal empat tabel ekspor beridentitas pseudonim: (1) peserta/assignment/cluster/pre/post/missingness, (2) objective evidence/item outcomes/timestamps, (3) adaptive recommendation generated vs exposed/action followed/reassessment/version, (4) keputusan guru dan alasan override. Identitas akun operasional tidak perlu dipublikasikan. Peta identitas dijaga terpisah; akses, retensi dan penghapusan disepakati.

**Sebelum data siswa nyata:** izin sekolah/institusi, proses persetujuan etika dan partisipasi sesuai regulasi/kebijakan yang berlaku, consent/assent bila relevan, instrumen dan akses siswa tervalidasi. Tidak mengunggah data siswa, kredensial, nilai nyata atau dump DB ke GitHub.

## G. Freeze checklist — BELUM DIPENUHI

| Komponen | Kandidat yang terlihat di repo | Status |
| --- | --- | --- |
| Kode R5 | PR #33 commit 7746a649, belum merge | NOT FROZEN |
| Baseline R5 | 9708706336c4e501a2a21511c56ebb94a938e6d3 | NOT FROZEN |
| Rule-Based/Fuzzy policy | R5 v1/v2, parameter research_draft | NOT FROZEN |
| Mastery/prereq | Batas 39,99 / 79,99; evidence keputusan Rule-Based 2; prerequisite minimum 70 | NOT FROZEN |
| LO, mapping item, versi materi/kuis | Persetujuan akademik dan versi belum dilampirkan | NOT FROZEN |
| Rubrik 16 skenario, gold standard | Formulir draft; data pakar tidak ada | NOT FROZEN |
| Pre/posttest eksternal dan analisis | Kisi-kisi dan estimand butuh keputusan | NOT FROZEN |
| Assignment dan override SOP | Perlu persetujuan, concurrency dua koneksi masih terbuka | NOT FROZEN |
| Izin, etik dan tata kelola data | Bukti belum tersedia di repository | PENDING |

## H. Tiga keputusan yang diminta kepada dosen

1. Apakah desain kelas/cluster dan jumlah sampel potensial memungkinkan inferensi yang layak, atau perlu revisi?
2. Apakah panel ahli dan lembar 16 skenario disetujui sebagai instrumen sebelum pilot, dan apa kriteria penerimaannya?
3. Kapan aturan treatment, kebijakan guru override, instrument pre/post, dan versi perangkat lunak dapat dinyatakan **FROZEN** dan eksperimen boleh dimulai?

**Kesimpulan:** rancangan R6 **tersedia untuk review**, bukan protokol yang sudah disahkan. Status eksperimen per 25 Nov tetap **NO-GO** sampai review dan persetujuan tersebut.