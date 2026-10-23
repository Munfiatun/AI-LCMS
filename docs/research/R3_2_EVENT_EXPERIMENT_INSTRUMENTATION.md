# R3.2 — Research Event & Experiment Instrumentation

## Status

Implementation phase for research instrumentation only.

This phase does **not** implement mastery estimation, adaptive thresholds, recommendation generation, remediation, enrichment, or treatment-dependent UI behavior.

## Purpose

R3.2 creates a defensible process-data layer for Topic C so later mastery and adaptive mechanisms can be evaluated using actual learning events rather than inferred from final progress alone.

The implementation follows the R2 principle:

`Learning Objective → Evidence → Mastery → Decision → Intervention → Outcome`

R3.2 implements only the instrumentation needed before the `Mastery` step.

## 1. Experiment assignment

New table: `experiment_assignments`

One assignment is stored per:

`Course × Student`

Fields include:

- course;
- student;
- condition (`adaptive` / `non_adaptive`);
- optional class/cluster;
- assignment version;
- assigning instructor;
- assignment timestamp.

The condition is **metadata only** in R3.2.

The application does not yet behave differently for Adaptive and Non-Adaptive participants.

Instructor workspace:

`/instructor/courses/{course}/research-experiment`

Only the course owner can manage assignments, and only enrolled students can be assigned.

## 2. Research event store

New table: `research_events`

Each event stores:

- UUID event ID;
- internal student ID;
- course ID;
- optional Learning Objective ID;
- optional experiment-assignment reference;
- controlled event type;
- source type and source ID;
- treatment-condition snapshot;
- assignment-version snapshot;
- occurrence timestamp;
- limited JSON metadata.

The event stores a snapshot of the experiment condition at event time. This protects interpretation if an assignment is later corrected during development/pilot preparation.

## 3. Event taxonomy implemented in R3.2

### Content

- `material_opened`
- `material_completed`
- `slidebook_opened`

### Assessment

- `assessment_started`
- `assessment_submitted`

Future adaptive/intervention events from R2 are intentionally not implemented yet.

## 4. Event semantics

### Repeatable events

`material_opened` and `slidebook_opened` may occur repeatedly because repeated exposure is meaningful process data.

### Idempotent milestone events

The recorder uses `recordOnce()` for milestone events where duplicate HTTP actions should not create duplicate research meaning:

- `material_completed` per student/material;
- `assessment_started` per student/attempt;
- `assessment_submitted` per student/attempt.

## 5. Learning Objective snapshots

Where a material or assessment is aligned to Learning Objectives, the event metadata stores the relevant objective ID list.

If exactly one objective applies, `learning_objective_id` is also populated directly for easier querying.

If multiple objectives apply, the direct field remains null and the complete list is stored in metadata.

This prevents the system from falsely treating a multi-objective activity as evidence for only one objective.

## 6. Assessment event metadata

`assessment_started` records:

- quiz ID;
- attempt ID;
- mapped objective IDs.

`assessment_submitted` additionally records operational outcome values already calculated by the quiz subsystem:

- submission reason (`student_submit` / `expired`);
- score;
- percentage;
- correct count;
- wrong count;
- duration seconds.

These values are raw evidence/context. R3.2 does not convert them into mastery.

## 7. Instructor UX

Dashboard course cards expose:

`Research Setup →`

The setup page allows the instructor to assign each enrolled student to Adaptive or Non-Adaptive condition, class/cluster, and assignment version.

A visible guardrail states that the assignment does not yet activate adaptive behavior.

## 8. Privacy boundary

Operational tables retain internal authenticated student IDs because the application needs them.

R3.2 does not yet implement research export or participant pseudonymization. Those remain scheduled for the research export phase.

No free-text personal learning notes are written to `research_events`.

## 9. Not implemented in R3.2

The following remain explicitly out of scope:

- mastery score/state;
- evidence sufficiency threshold;
- adaptive decision engine;
- remediation recommendation;
- enrichment recommendation;
- recommendation exposure/compliance;
- teacher recommendation override;
- reassessment linkage;
- research export/de-identification;
- treatment-specific student experience.

## 10. R3.2 acceptance criteria

R3.2 is complete when:

1. experiment assignment exists independently from LMS enrollment;
2. assignment is limited to enrolled students and owning instructor;
3. student material open/completion produces controlled research events;
4. Slidebook opening produces a controlled research event;
5. assessment start/submission produces controlled research events;
6. events preserve treatment-assignment snapshots;
7. event metadata preserves relevant objective alignment;
8. milestone events are protected from accidental duplicate logging;
9. no mastery/adaptive recommendation behavior is introduced;
10. automated tests and the existing project quality gate pass.

## 11. Verification procedure

After switching to the R3.2 branch locally:

```bash
php artisan optimize:clear
php artisan migrate
php artisan test --filter=ResearchInstrumentationTest
```

For the full repository gate:

```bash
composer run release:check
```

Manual verification should use at least two enrolled student accounts:

1. assign one student to `Adaptive` and another to `Non-Adaptive` from `Research Setup`;
2. verify the student-facing experience is still identical in R3.2;
3. open an aligned material as a student;
4. open its Slidebook;
5. mark the material completed;
6. start and submit a quiz whose questions are mapped to Learning Objectives;
7. verify research events are written with the expected event type, source and treatment snapshot.

Repeated page opens are expected to create repeated exposure events. Repeating a completion/submit milestone must not create duplicate milestone events for the same source.

## 12. Next phase

The next research implementation phase is **R3.3 — Mastery Evidence & Estimation**.

R3.3 must use the objective alignment and evidence layer created in R3.1–R3.2. It must not simply reuse existing product thresholds such as progress `<50` or average quiz score `<70` as scientific mastery rules.
