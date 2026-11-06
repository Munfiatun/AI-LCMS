# Pendampingan Dosen — Audit Regresi R5 AI-LCMS

Tanggal: 2026-11-04 (Asia/Jakarta)
Basis: `research/r5-ui-ux-simplification` @ `c11fba6979983c034108f517b5458d0eb6cbb4c0`
Branch review: `adi/r5-regression-review-20261104`

## Tujuan dan batasan

Dokumentasi audit independen untuk membantu mahasiswa Munfiatun memperbaiki regresi pada R5. Perubahan tidak langsung dilakukan pada `main` atau branch riset mahasiswa. Mahasiswa tetap menjadi pemilik keputusan penerimaan dan merge. Dokumen ini mencatat baseline pengujian dan patch kandidat yang ditambahkan setelah audit awal. Patch **belum diverifikasi dengan PHPUnit di mesin dosen**.

## Bukti pengujian dosen (Mac lokal)

- Source code R5 sama dengan GitHub pada SHA basis (perbandingan 0/0, working tree bersih).
- SQLite R5: 7 migration berhasil; `PRAGMA integrity_check=ok`, 46 tabel. Backup lokal dibuat sebelum migration.
- `php artisan test --compact`: 531 passed, 3 failed; 3214 assertions; exit 2.
- `npm run build`: PASS, exit 0 (peringatan opsional `fontaine`).
- Ketiga pengujian gagal tetap gagal saat dijalankan sendiri.

## Defect/regresi yang perlu ditangani

1. **AIQuestionAuthoringQualityTest::test_incomplete_reference_to_program_is_rejected**. Harapan: pesan 'tanpa menyertakan objek tersebut'; aktual: pesan soal kode satu baris. Di `AIQuestionAuthoringService::validate...`, pemeriksaan rujukan ke kode terpicu sebelum pemeriksaan objek yang benar-benar absen. Perjelas klasifikasi kasus: artefak hilang vs kode hadir satu baris; pertahankan penolakan soal ambigu dan tambah pengujian kedua kasus.
2. **AIQuizGenerationTest::test_groq_request_sizes_output_budget_by_question_count**. `Http::assertSent` mensyaratkan `response_format.type=json_object`; implementasi `GroqProvider` dapat menggunakan `json_schema` strict untuk `openai/gpt-oss-20b` bila kompatibel. Verifikasi kontrak format provider yang disengaja, besaran token, fallback, reasoning effort, lalu perbaiki implementasi atau ekspektasi test sesuai spesifikasi (jangan hanya melemahkan assertion).
3. **PhaseZeroSecurityTest::test_ai_provider_failure_does_not_persist_or_rethrow_sensitive_message**. `Log::error` dipanggil dua kali, sementara test mengharapkan sekali. Di `AIContentService` terdapat logging di penanganan bagian dalam dan luar untuk RuntimeException yang terbungkus ulang. Perbaiki agar penanganan kegagalan tidak menggandakan log; pertahankan sanitasi pesan, keamanan kredensial, dan pembatasan fallback.

## Acceptance criteria

- Ketiga pengujian spesifik PASS ketika dijalankan sendiri.
- Seluruh test suite R5 PASS tanpa tes di-skip/dihapus atau assertion hanya dilemahkan.
- `npm run build` PASS.
- Data lokal dosen tidak dimodifikasi melalui `migrate:fresh`, `db:wipe`, atau `db:seed`.
- Mahasiswa menyertakan root cause, daftar file berubah, output tes, commit SHA dan risiko yang tersisa.
- Dosen mengaudit perubahan, mahasiswa mereview PR dan **melakukan merge sendiri** jika disetujui.

## Pekerjaan mahasiswa setelah regresi

- Audit fitur end-to-end diagnostik → mastery → rekomendasi → pembelajaran → asesmen formatif → readaptasi.
- Tunjukkan pengujian validitas dan explainability Rule-Based v1 serta Fuzzy Mamdani v2.
- Selesaikan R5 UX, role/access control, integritas data, dan evaluasi kualitas (ISO/IEC 25010/25019).
- Siapkan desain, instrumen, dan bukti evaluasi empiris R6, bukan hanya jumlah tes otomatis.

## Patch kandidat yang di-push ke branch review (belum diverifikasi)

- `app/Services/AI/AIQuestionAuthoringService.php`: prioritaskan pesan rujukan artefak yang hilang sebelum pesan kode satu baris; pertahankan penolakan kedua kasus.
- `tests/Feature/AIQuizGenerationTest.php`: validasi ukuran output dan reasoning effort tetap wajib, sambil mengizinkan kontrak `json_schema` strict untuk model Groq yang kompatibel atau `json_object` untuk fallback yang tidak kompatibel.
- `app/Services/AI/Exceptions/LoggedAIProcessingException.php` dan `app/Services/AI/AIContentService.php`: marker internal untuk error yang sudah dicatat, sehingga boundary luar tidak mencatat error dua kali dan tetap melempar pesan aman tanpa exception chain.
- `tests/Feature/PhaseZeroSecurityTest.php`: periksa kontrak log yang memang mencakup `provider=mock`.

**Belum diuji:** konektor GitHub hanya mengubah source repository; tidak menjalankan PHPUnit dalam lingkungan Laravel milik dosen. Angka 531 passed/3 failed adalah baseline **sebelum** patch. Jangan laporkan PASS sampai tes lokal dijalankan.

## Tugas verifikasi dosen dan mahasiswa

1. `git fetch origin` dan `git switch --track origin/adi/r5-regression-review-20261104` pada clone yang branch lokalnya belum ada. Hindari perubahan lokal yang belum di-commit.
2. Jalankan ulang ketiga tes terisolasi, lalu `php artisan test --compact`, `npm run build` dan cek `git status --short`.
3. Jika PASS, periksa secara manual pesan kualitas soal dan sanitasi error. Jika gagal, kirim log penuh sebelum menyetujui PR.
4. Mahasiswa mereview diff, memperbarui dokumentasi dan melanjutkan pengujian R3/R4/R5/R6.

Status: **PATCH CANDIDATE PUSHED — TEST VERIFICATION PENDING**.
