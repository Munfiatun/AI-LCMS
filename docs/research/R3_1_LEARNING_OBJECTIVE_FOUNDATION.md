# R3.1 — Learning Objective & Alignment Foundation

## Status

R3.1 implements the first runtime research foundation defined by R2.

It introduces explicit course-level Learning Objectives and persistent alignment between:

- Learning Material ↔ Learning Objective
- Question ↔ Learning Objective

R3.1 does **not** implement mastery estimation, adaptive thresholds, recommendations, remediation, enrichment, reassessment, experiment assignment, or research event logging.

## Why this phase exists

Topic C requires evidence to be interpreted at the level of a learning objective rather than only at quiz/course level. Before mastery can be estimated, AI-LCMS must be able to answer:

1. Which objective does a material teach?
2. Which objective does a question assess?
3. Are both objects aligned inside the same course context?

R3.1 provides that traceability only.

## New persistence model

### `learning_objectives`

Fields:

- `course_id`
- `code`
- `title`
- `description`
- `order`
- `is_active`
- timestamps

`code` is unique inside a course, but the same code may be reused by another course.

### `learning_material_learning_objective`

Many-to-many mapping between learning materials and objectives.

### `learning_objective_question`

Many-to-many mapping between questions and objectives.

All alignment records cascade when the related objective/material/question is deleted.

## Domain relationships

```text
Course
  └── LearningObjective
          ├── LearningMaterial (many-to-many)
          └── Question (many-to-many)
```

Course now also exposes its linked Question Banks so the alignment workspace can present questions in course context.

## Instructor workflow

Workspace route:

`/instructor/learning-objectives`

The instructor can:

1. select one of their courses;
2. create/update/deactivate/delete Learning Objectives;
3. align each course material to one or more objectives;
4. align each question from course-linked question banks to one or more objectives.

Global question banks are intentionally excluded from the course alignment workspace because they do not provide a course context.

## Security and integrity guardrails

- Instructor middleware is required.
- Existing Course/Material/Question authorization policies remain authoritative.
- Alignment validation only accepts Learning Objective IDs belonging to the same course as the material/question.
- Objectives belonging to another course are rejected.
- Objective codes are normalized to uppercase before persistence.
- No cross-instructor course data is displayed by the workspace.

## Research guardrails

The following are intentionally absent:

- mastery score;
- mastery status;
- remediation threshold;
- continue threshold;
- enrichment threshold;
- recommendation reason;
- learning-event data;
- treatment assignment.

The presence of an alignment does not mean that a student has mastered the objective. It only establishes semantic traceability for later evidence processing.

## Test coverage

`LearningObjectiveAlignmentTest` covers:

- instructor workspace access;
- objective create/update;
- course-scoped code uniqueness;
- authorization against another instructor's objective;
- material alignment;
- question alignment;
- cross-course objective rejection;
- global-bank alignment rejection;
- cascade cleanup of pivot records;
- student access restriction.

## Gate to R3.2

R3.2 may introduce research event/experiment instrumentation only after R3.1 migration and regression tests pass.

R3.2 must not yet reinterpret existing product analytics heuristics as validated mastery rules.
