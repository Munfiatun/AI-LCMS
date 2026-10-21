# R2 — Decisions, Deferred Parameters & R3 Gate

## R2 decisions locked

The following design decisions are considered locked unless the supervisor/research protocol explicitly changes them.

### 1. Thesis focus

Topic C remains the selected thesis direction:

**Adaptive learning based on learning analytics in AI-LCMS.**

### 2. Unit of mastery

Mastery is estimated at:

**Student × Learning Objective**

not merely at course-level progress or overall quiz average.

### 3. Objective alignment is mandatory

Research materials and scored mastery evidence must be aligned to explicit learning objectives.

Existing `topic`, `difficulty`, source excerpts and course progress do not replace learning-objective mapping.

### 4. Primary mastery evidence

Primary mastery evidence comes from **valid scored evidence explicitly aligned to the objective**, especially item-level assessment/reassessment responses.

### 5. Contextual evidence is separate

Material completion, time, attempts, navigation and recommendation exposure are contextual/process evidence.

They do not automatically increase mastery.

### 6. Evidence sufficiency precedes adaptive decisions

The system must support an `insufficient_evidence` state.

It must not force every student-objective pair into remediation/continue/enrichment when evidence is inadequate.

### 7. Adaptive states

Future policy states:

- `insufficient_evidence`;
- `remediation_candidate`;
- `continue_candidate`;
- `enrichment_candidate`.

### 8. Adaptive decisions must be explainable

Every research recommendation must preserve:

- mastery/evidence snapshot;
- evidence sufficiency;
- policy version;
- recommended action;
- human-readable rationale.

### 9. Recommendation generation is not treatment exposure

The system must distinguish:

- generated;
- exposed;
- followed/accepted;
- intervention started;
- intervention completed;
- reassessment completed.

### 10. Teacher oversight remains available

The research architecture supports accept/modify/reject decisions where the final workflow requires teacher review.

### 11. Existing analytics heuristics remain product heuristics

Current values in `CourseAnalyticsService` and `LearningAnalyticsInsightService` are not automatically reused as research mastery/adaptive thresholds.

### 12. Main treatment comparison

Preferred comparison:

- Adaptive AI-LCMS;
- Non-Adaptive AI-LCMS;

with the remaining platform/content environment held as similar as feasible.

### 13. Primary learning outcome is external to the adaptive score

Primary pedagogical outcome uses a research pretest/posttest instrument aligned to the target objectives.

Internal mastery is a treatment mechanism/process variable, not the sole external proof of effectiveness.

### 14. Product quality is evaluated separately

Evaluation planning uses selected relevant measures based on:

- ISO/IEC 25010:2023;
- ISO 9241-11:2018;
- ISO/IEC 25022:2016.

ISO/IEC 25010 is not treated as a questionnaire and the thesis does not claim automatic ISO certification/compliance.

### 15. SUS is not the primary thesis framework

Existing SUS infrastructure may remain for legacy/formative use, but it is not the primary product-quality framework for this thesis.

### 16. Reproducibility is mandatory

The final field experiment must freeze/version:

- source commit;
- policy version;
- objective map;
- content/material version;
- assessment version;
- data dictionary;
- treatment assignment procedure;
- analysis plan.

## Parameters deliberately deferred

The following are **not** arbitrary R2 decisions and must be finalized through literature, expert validation, instrument design and/or pilot evidence:

### Mastery calculation details

- final aggregation formula;
- item weights;
- use/non-use of recency weighting;
- diagnostic vs reassessment weighting.

### Evidence sufficiency threshold

- minimum number/coverage of objective-aligned observations.

### Adaptive boundaries

- numeric boundary for remediation;
- continuation boundary;
- enrichment boundary.

### Exact sample size

Depends on school/class access and a priori justification.

### Final inferential model

ANCOVA/regression or multilevel/mixed-effects model depends on the realized number of classes/clusters and data structure.

### Final satisfaction instrument

The final user-satisfaction measure must be selected and justified in the methods protocol. It must not be called an “ISO questionnaire” unless it actually derives from a documented validated instrument.

### Exact ISO acceptance thresholds

Performance, task-success and other acceptance criteria must be defined against actual AI-LCMS requirements/context before formal evaluation.

## R3 implementation order

R3 should not begin as one large adaptive-engine implementation.

Recommended staged development:

### R3.1 — Learning Objective & Alignment Foundation

Implement:

- learning objectives;
- material-objective mapping;
- question-objective mapping;
- authorization and validation;
- alignment-oriented tests.

No adaptive decisions yet.

### R3.2 — Research Event & Experiment Instrumentation

Implement:

- controlled research event taxonomy;
- treatment/experiment assignment foundation;
- event integrity and privacy-aware logging;
- version/provenance fields.

No mastery recommendation yet.

### R3.3 — Mastery Evidence & Estimation

Implement:

- objective-level evidence aggregation;
- mastery state;
- evidence sufficiency;
- evidence trace;
- versioned calculation policy.

Only after the candidate calculation method is accepted.

### R3.4 — Adaptive Decision & Recommendation

Implement:

- decision states;
- recommendation rationale;
- remediation/continue/enrichment selection;
- policy configuration/versioning.

Only after decision parameters have been validated enough for pilot use.

### R3.5 — Intervention, Reassessment & Teacher Oversight

Implement:

- exposure/action logs;
- remediation/enrichment flow;
- reassessment linkage;
- teacher accept/modify/reject workflow.

### R3.6 — Research Export & Experiment Guardrails

Implement:

- de-identified export;
- integrity checks;
- treatment isolation;
- configuration freeze support;
- analysis-ready datasets.

## What may start immediately after R2 approval

R3.1 and the non-algorithmic parts of R3.2 may begin because they do not require arbitrary mastery thresholds.

R3.3/R3.4 must wait for the candidate mastery/decision parameters to be accepted through the research validation process.

## R2 completion criteria

R2 is complete when the repository contains and the researcher understands:

- `R2_RESEARCH_DESIGN.md`;
- `R2_OPERATIONAL_DEFINITIONS.md`;
- `R2_ADAPTIVE_MECHANISM_SPEC.md`;
- `R2_EXPERIMENT_DESIGN.md`;
- `R2_QUALITY_EVALUATION_PLAN.md`;
- `R2_DATA_INSTRUMENT_PLAN.md`;
- this decision/gate document.

No adaptive runtime implementation is part of R2.

## R2 conclusion

The research now has an explicit separation between product analytics, mastery evidence, adaptive policy, product quality and learning effectiveness.

The next technically safe development step is **R3.1 — Learning Objective & Alignment Foundation**, not a full recommendation engine.

RESEARCH PHASE R2 COMPLETE
