# UX Workflow Refinement

R1 focuses on making the LMS learning-creation journey understandable without technical documentation.

## Teacher journey

1. Buat Kursus
2. Tambahkan Materi
3. Buat Slidebook
4. Buat Quiz
5. Publikasikan Kursus
6. Ajak Siswa
7. Pantau Hasil Belajar

## Product language

Student- and teacher-facing labels should describe the user's action or outcome rather than implementation details.

Preferred wording:

- `AI Generation` → `Buat dengan AI`
- `Create Revision` / `Revision` → `Buat Versi Baru`
- `Source Reference` → `Sumber Materi`
- `needs_review` → `Perlu Dicek Guru`
- `Layout` → `Tampilan Slide`
- `Approve` → `Setujui`
- `Publish` → `Terbitkan` / `Publikasikan`

Technical identifiers, database values, and internal status constants are not changed by this wording guideline.

## Course readiness

The instructor dashboard must expose real readiness signals per course:

- total learning materials;
- published Slidebooks;
- published Quizzes;
- enrolled students;
- missing Slidebook warning;
- unpublished Quiz warning;
- contextual next action.

This guidance is informational and must not weaken the existing publication validation rules.
