# R1 — Baseline & Gap Audit (Synchronized)

## Research direction

AI-LCMS is being evaluated as the engineering baseline for the selected thesis direction:

**Pengembangan AI-LCMS dengan Mekanisme Pembelajaran Adaptif Berbasis Learning Analytics pada Pembelajaran Pemrograman di SMK.**

This document separates existing product capability from the new research contribution. Existing product features are not automatically thesis novelty.

## Reproducibility anchor

| Item | Value |
| --- | --- |
| Canonical repository | `Munfiatun/AI-LCMS` |
| Canonical baseline commit | `39f34f411ccd68cdb54f47a1e8644808f7aedb4d` |
| Legacy audit commit | `f469135848d78e4ebb1ff1bcd41aacaaa312ae1e` |
| Research branch | `research/r1-2-baseline-sync` |
| Audit date | 21 October 2026 |
| Synchronization status | Remote baseline revalidated; research documentation rebuilt on current canonical baseline |
| Runtime test result | Not executed by this documentation-only remote synchronization step |
| Frontend build result | Not executed by this documentation-only remote synchronization step |

The canonical baseline is 83 commits ahead of the legacy audit commit. The delta includes analytics, usability, quality, accessibility, performance/stress, and demo-readiness work. Those additions were reclassified below without treating them as thesis novelty.

## Current product architecture relevant to Topic C

The synchronized baseline already provides substantial supporting infrastructure:

- authentication, roles, policies and authorization;
- courses, sections, enrollment and publication lifecycle;
- learning materials and document extraction;
- AI Slidebook generation, review, versioning and publication;
- question banks, AI-generated questions, source evidence and teacher review;
- quizzes, randomized attempts, answers, scoring and retry guidance;
- material completion/progress;
- instructor analytics and student learning dashboard;
- AI provider abstraction;
- product-quality, usability, accessibility and stress-testing infrastructure.

These capabilities support the research but do not by themselves constitute adaptive learning.

## Synchronized analytics baseline

### `CourseAnalyticsService`

The service summarizes course-level and student-level evidence from enrollment, published material progress, submitted quiz attempts and quiz pass/fail status. It also emits teacher-facing intervention labels.

Current product heuristics include:

- completed/stable when enrollment is completed or progress reaches 100%;
- high-priority intervention when progress is below 50%;
- high-priority intervention when at least one attempt exists and average quiz score is below 70%;
- medium-priority intervention when materials or quizzes are still incomplete;
- severity ordering of `high`, `medium`, `low`, `stable` for priority display.

**Research status:** Existing Product Heuristic — Requires Research Validation.

These values must not be reused automatically as mastery thresholds or adaptive-learning policies.

### `LearningAnalyticsInsightService`

The newer baseline contains a dedicated learning-insight service. It derives teacher-facing signals from the latest submitted attempt for each student/quiz pair and question-level responses.

Current product heuristics include:

- minimum item responses = 3;
- correct rate below 50% = `review`;
- correct rate below 70% = `monitor`;
- otherwise = `healthy` when evidence is sufficient;
- fewer than 3 quiz participants = limited evidence;
- participant coverage below 70% = developing evidence;
- participant coverage below 50% generates a low-participation signal;
- at least 3 representative attempts with latest pass rate below 60% generates a high-priority signal;
- average course progress below 50% can generate a progress signal;
- priority questions are limited for display.

**Research status:** Existing Product Heuristic — Requires Research Validation.

The service is valuable as an evidence/analytics layer, but it does not estimate competency mastery and it does not implement a validated adaptive recommendation policy.

## Other important baseline additions

### `CourseWorkflowReadinessService`

Provides product workflow readiness and next-action guidance for instructors. It checks whether a course has materials, published Slidebooks, quizzes, students and submitted attempts.

Classification: **Supporting Infrastructure / REUSE**.

It is not a learning-personalization mechanism.

### `SlidebookQualityService`

Provides content-quality warnings and blocking rules for Slidebook authoring, including title/content checks, source coverage, text density and visual-layout opportunities.

Classification: **Supporting Infrastructure / REUSE**.

Its rules evaluate content authoring quality, not student mastery.

### `UsabilityReportService`

Supports task-based usability metrics such as completion rate, time, errors and observed confusion, and also contains legacy SUS scoring.

Classification: **Supporting Infrastructure**.

For the thesis, task-based evidence may be reused where appropriate, while SUS is not designated as the sole or primary product-quality evaluation instrument.

### Demo, accessibility and stress-testing infrastructure

The newer baseline includes demo readiness, accessibility support and performance/stress tests.

Classification: **Supporting Infrastructure**.

These features improve product readiness but are not the research novelty for Topic C.

## Topic C research gap after synchronization

The 83 newer commits do **not** close the core Topic C gap.

The current system still does not provide a validated persistent pipeline of:

```text
Learning Objective
        ↓
Learning Evidence
        ↓
Mastery Estimation
        ↓
Adaptive Decision
        ↓
Recommendation
        ↓
Intervention
        ↓
Outcome / Reassessment
```

Specifically, the synchronized baseline still lacks a research-grade mechanism to persistently answer:

1. Which learning objective is addressed by a given material?
2. Which learning objective is measured by a given question?
3. What mastery estimate exists for one student-objective pair?
4. Which evidence produced that mastery estimate?
5. Which adaptive recommendation was generated and why?
6. Was the recommendation actually exposed to the student?
7. Did the student follow the recommendation?
8. Was remediation/enrichment completed?
9. What was the learner state before and after intervention?
10. Did a teacher accept, modify or reject a recommendation?
11. Was the learner assigned to adaptive or non-adaptive treatment?

## Model-level gaps confirmed

### `LearningMaterial`

The current model manages content, lifecycle and Slidebook relationships, but it has no learning-objective/competency relationship.

### `Question`

The current model includes topic, difficulty, points, source slide/excerpt, answer provenance and review state, but no learning-objective/competency relationship.

### `MaterialProgress`

The current model records student, learning material, completion status and completion time. This is insufficient as a complete learning-event history.

### `QuizAttempt`

The current model already provides valuable evidence: start/expiry/submission time, score, percentage, correct/wrong counts and duration. This should be reused as assessment evidence rather than rebuilt.

## Conclusion

The synchronized AI-LCMS baseline is sufficiently mature to serve as the engineering platform for the thesis. The new research contribution should not be framed as creating an LMS, analytics dashboard, Slidebook quality checker or SUS report.

The defensible Topic C contribution remains the design, implementation and empirical evaluation of a validated adaptive-learning mechanism that connects objective alignment, evidence, mastery estimation, adaptive decisions, interventions and outcomes.

No adaptive-learning runtime feature was implemented during R1/R1.1/R1.2.
