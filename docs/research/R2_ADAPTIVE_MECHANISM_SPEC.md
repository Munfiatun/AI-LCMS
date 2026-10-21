# R2 — Adaptive Mechanism Specification

## Purpose

This document specifies the future adaptive-learning mechanism at a research-design level. It defines inputs, outputs, safeguards and validation steps without introducing production code or arbitrary thresholds.

## Design choice

The initial thesis mechanism should be **interpretable and auditable** rather than unnecessarily complex.

R2 therefore favors a transparent rule-based adaptive policy built on objective-level mastery evidence, with parameters validated through literature review, expert judgement and pilot testing.

Machine-learning prediction is not required for Topic C and should not be added merely to increase technical complexity.

## Separation from existing product analytics

Existing services such as `CourseAnalyticsService` and `LearningAnalyticsInsightService` may provide contextual evidence and teacher-facing signals.

However, existing product rules such as progress `<50%`, average score `<70%`, question correct-rate cutoffs and participation thresholds are **not** automatically adopted as mastery or adaptive-policy thresholds.

## Input model

The future adaptive mechanism may consume:

### Primary input

- objective-aligned scored item responses;
- objective-aligned reassessment results;
- validity/status of the assessment evidence.

### Contextual input

- material completion;
- attempt count;
- timing/duration where reliable;
- remediation exposure/completion;
- previous recommendation history.

Contextual input may affect explanation, evidence sufficiency or intervention logistics, but should not directly raise mastery unless explicitly justified and validated.

## Candidate mastery estimation approach

The implementation should support a simple, reproducible objective-level estimate based on aligned assessment evidence.

A generic form is:

```text
Mastery(student, objective)
    = aggregate(valid scored evidence aligned to objective)
```

Possible candidate aggregation strategies for expert/pilot comparison include:

1. proportion of weighted points earned on objective-aligned items;
2. weighted mean of aligned item outcomes;
3. recency-aware weighted evidence where later reassessment may contribute more than earlier evidence;
4. separate diagnostic and reassessment components.

R2 does **not** select final numerical weights.

The final method must satisfy:

- interpretability;
- reproducibility;
- sufficient evidence requirements;
- ability to trace each estimate back to item-level evidence;
- robustness to missing evidence;
- suitability for the available sample and classroom setting.

## Evidence sufficiency gate

The decision sequence must first ask:

```text
Is there enough valid objective-aligned evidence?
```

If no:

```text
Decision = insufficient_evidence
```

No remediation/continue/enrichment claim should be produced as though mastery were known.

The final sufficiency criterion is to be established after instrument design and expert/pilot review.

## Adaptive state model

### 1. `insufficient_evidence`

Meaning:

The system lacks adequate valid evidence to classify the student’s objective-level mastery state.

Possible action:

- collect diagnostic/reassessment evidence;
- continue ordinary instruction until evidence is available.

### 2. `remediation_candidate`

Meaning:

Validated evidence indicates that the objective requires additional support according to the frozen research policy.

Possible action:

- assign objective-linked remediation material/activity;
- later collect reassessment evidence.

### 3. `continue_candidate`

Meaning:

Validated evidence satisfies the policy for normal continuation to the next planned learning step.

Possible action:

- proceed with normal sequence.

### 4. `enrichment_candidate`

Meaning:

Validated evidence satisfies the policy for advanced/extended activity.

Possible action:

- enrichment/challenge linked to the current or next objective.

## Threshold policy

R2 deliberately does not define values such as:

```text
mastery < 40 = remediation
mastery >= 70 = continue
```

unless later supported by the research process.

The final thresholds/decision boundaries must come from a documented combination of:

1. literature/theoretical rationale;
2. expert judgement;
3. assessment design characteristics;
4. pilot evidence;
5. sensitivity analysis where feasible.

## Parameter architecture requirement for R3

When implemented, research parameters must be centralized and versioned.

Conceptually:

```text
AdaptivePolicyVersion
- version
- evidence_sufficiency_rule
- mastery_method
- mastery_parameters
- remediation_boundary
- continuation_boundary
- enrichment_boundary
- effective_from
- frozen_for_experiment
```

This is a conceptual requirement only. R2 does not create the table/model.

## Recommendation generation

A future recommendation must include:

- target student;
- target objective;
- adaptive state;
- recommended action/resource;
- mastery snapshot;
- evidence sufficiency;
- evidence summary;
- policy version;
- human-readable rationale;
- generation timestamp.

## Recommendation exposure

Generation and exposure are different events.

The research system must distinguish:

```text
recommendation_generated
recommendation_exposed
recommendation_accepted/followed
intervention_started
intervention_completed
reassessment_completed
```

Without this separation, a study could incorrectly assume treatment was received merely because a database recommendation existed.

## Teacher oversight model

The instructor may later be allowed to:

- accept;
- modify;
- reject.

Teacher decisions should be logged rather than silently replacing the system output.

The research can later analyze:

- agreement rate between system and teacher;
- common reasons for modification/rejection;
- whether recommendations are operationally usable.

## Remediation flow

```text
Low/insufficiently demonstrated objective
        ↓
Remediation recommendation
        ↓
Objective-linked remediation material/activity
        ↓
Intervention completion evidence
        ↓
Objective-aligned reassessment
        ↓
New mastery evidence
        ↓
Updated state
```

## Continue flow

```text
Objective evidence satisfies continuation policy
        ↓
Continue recommendation
        ↓
Next planned learning activity
```

The system should not require unnecessary remediation when the continuation policy is satisfied.

## Enrichment flow

```text
Objective evidence satisfies enrichment policy
        ↓
Enrichment recommendation
        ↓
Challenge/advanced resource
```

Enrichment is an instructional response, not a separate proof of mastery unless followed by valid scored evidence.

## Failure and edge cases

The R3 implementation must handle:

- no aligned questions;
- questions mapped to inactive/invalid objectives;
- unanswered items;
- incomplete attempts;
- duplicated events;
- deleted/archived materials;
- recommendation target unavailable;
- policy version mismatch;
- student switched/incorrectly assigned to treatment condition;
- reassessment with no preceding intervention;
- teacher override without rationale when rationale is required by protocol.

## Validation plan

### Expert validation

Experts should review at minimum:

- objective mapping appropriateness;
- item-objective alignment;
- mastery evidence logic;
- decision-policy reasonableness;
- remediation/enrichment relevance;
- clarity of recommendation rationale.

### Pilot validation

Pilot should examine:

- whether evidence is collected as designed;
- frequency of insufficient evidence;
- distribution of mastery estimates;
- stability/sensitivity of candidate boundaries;
- agreement with teacher judgement;
- cases of obviously inappropriate recommendations;
- intervention completion and reassessment linkage.

## R3 implementation gate

R3 may implement the mechanism only after the research team/pembimbing accepts:

- the objective granularity;
- assessment/evidence design;
- candidate mastery method;
- validation procedure;
- parameter versioning approach;
- experiment-condition definition.
