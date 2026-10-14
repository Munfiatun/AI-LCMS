# Performance & Stress Testing

R7 replaces row-heavy readiness/analytics paths with bounded queries and locks those assumptions into the test suite.

## Instructor Dashboard

Course readiness must not eager-load complete enrollment or Quiz-attempt histories. The dashboard uses aggregate counts for:

- active/completed students per course;
- submitted Quiz attempts per course.

It still loads the smaller authoring structures needed for teacher actions: sections, materials, published Slidebooks, and Quiz metadata.

The stress test creates 120 submitted attempts and verifies that the dashboard does not execute full-history `SELECT *` loads for attempts or enrollments and stays within a bounded query budget.

## Learning Signals

R5 originally de-biased retries after loading all submitted attempts into PHP. R7 moves representative-attempt selection into SQL. Only the latest submitted attempt per student/Quiz pair is materialized, ordered by:

1. `submitted_at DESC`
2. `id DESC` as deterministic tie-breaker.

A composite index supports the lookup:

`(quiz_id, student_id, status, submitted_at)`

The stress test creates 80 retries for one student/Quiz pair and verifies that Learning Signals still receives one representative attempt and that query count does not grow with retry history length.

## Slidebook Quality

The Slidebook quality engine must operate with a query count that depends on relations, not the number of slides. The stress suite evaluates a 100-slide Slidebook and enforces a small query budget.

## What the budgets mean

These are regression budgets, not production latency SLAs. CI runners vary in CPU and I/O, so R7 intentionally avoids brittle millisecond assertions. Query shape, bounded query count, and avoiding full-history materialization are more reliable automated guards.

## Existing protections retained

- R4 AI retry remains hard-capped at three provider calls.
- Existing Student dashboard/material-navigation N+1 tests remain active.
- R5 item-analysis thresholds and grading/progress rules are unchanged.
