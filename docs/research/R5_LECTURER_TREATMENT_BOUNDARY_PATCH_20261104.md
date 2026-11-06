# Dosen Patch Review — Experimental Treatment Isolation R5

Date: 2026-11-04, Asia/Jakarta
Base review branch: `adi/r5-regression-review-20261104`

## Problem grounded in code

Earlier automated tests passed (534 total, and focused 14/76), but tested algorithm type (baseline vs experimental), not research condition (`adaptive` vs `non_adaptive`). The student recommendation workspace read active baseline rows for enrolled users without filtering explicit control-group assignment, and generation service did not block recommendations for those assigned `non_adaptive`.

This is a possible contamination route in the documented R2 quasi-experimental design.

## Candidate fix (awaiting independent execution)

- `AdaptiveRecommendationController::index()`: exclude courses having the student’s `ExperimentAssignment::CONDITION_NON_ADAPTIVE` before loading/displaying current recommendations.
- `AdaptiveRecommendationService::generateForCourse()`: return an empty collection for students explicitly assigned `non_adaptive` in that course; do not generate or persist new adaptive recommendations for that call.
- Add tests in `StudentAdaptiveRecommendationWorkspaceTest` for control-group non-exposure even with existing active recommendations and for adaptive-group visibility.
- Add tests in `AdaptiveRecommendationPersistenceTest` for no generation for control and unchanged generation for adaptive.

**Compatibility policy:** learners with **no** research assignment retain their existing LMS adaptive workflow. Students explicitly marked `adaptive` also retain it. This choice prevents disruptions to ordinary LMS use but must be documented in the field study protocol: **do not leave participants unassigned** during an experiment.

**Scope limits:** This guard addresses the student workspace and the primary `generateForCourse()` service. It does not claim exhaustive isolation across manual DB writes, direct service calls to `persistDecision()`, research logging, other UI/API views, teacher recommendations, manually provided tutoring, or contamination outside the platform. Audit them before field deployment.

## Verification commands (Mac, isolated tests)

```bash
php artisan test tests/Feature/StudentAdaptiveRecommendationWorkspaceTest.php tests/Feature/AdaptiveRecommendationPersistenceTest.php tests/Feature/ResearchInstrumentationTest.php --compact
php artisan test --compact
npm run build
git status --short
```

Do **not** run migration, seeder or `migrate:fresh` on real local data. Tests use SQLite in-memory per checked `phpunit.xml`.

## Acceptance and student actions

- The four additional tests and original suite pass on lecturer Mac **after this patch**; record actual test and assertion totals, git SHA, build status.
- Student reviews assignment semantics, implications when changing group condition mid-study and protects intervention-exposure trace.
- Student audits alternate/direct routes, events, assignment lifecycle, and source data for leakage/contamination.
- Authorize pilot only after research design, quality instruments, consent and instructor protocol have been frozen.
- This branch remains draft PR; **no merge without student review**.

Status: **CANDIDATE PATCH PUSHED — RUNTIME VERIFICATION PENDING**.
