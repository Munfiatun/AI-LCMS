# R2 — Data, Variables & Instrument Plan

## Purpose

This document separates research variables, operational system data and evaluation instruments so the future implementation does not collect data without a clear research purpose.

## 1. Variable structure

### Independent variable

**Adaptive-learning condition in AI-LCMS**

Operational levels:

- Adaptive AI-LCMS;
- Non-Adaptive AI-LCMS.

The core platform remains the same; the individualized adaptive decision/recommendation mechanism is the primary treatment contrast.

### Primary dependent variable

**Learning achievement / competency mastery measured by an external research posttest aligned to the targeted programming objectives.**

### Baseline covariate

**Pretest score** on the same target-objective domain.

### Secondary/process variables

Potential variables:

- objective-level internal mastery estimate;
- objective-level assessment score;
- number of valid evidence observations;
- evidence sufficiency;
- recommendation type;
- recommendation exposure;
- remediation/enrichment completion;
- reassessment score;
- number of attempts;
- material completion;
- teacher recommendation decision;
- recommendation-system/teacher agreement;
- time/duration measures where technically reliable.

These are secondary/process variables and must not be substituted for the primary external outcome without justification.

### Product-quality variables

Evaluated separately from pedagogical effectiveness:

- selected ISO/IEC 25010:2023 product-quality characteristics;
- ISO 9241-11:2018 usability concepts operationalized in context of use;
- selected ISO/IEC 25022:2016 quality-in-use measures.

## 2. Data-source matrix

| Research need | Source | Existing / Future | Notes |
| --- | --- | --- | --- |
| Participant assignment | Experiment assignment | Future | Must record condition and assignment version |
| Pretest | Research assessment | Future/research instrument | External to internal mastery state |
| Posttest | Research assessment | Future/research instrument | Primary outcome |
| Quiz attempt | `QuizAttempt` | Existing | Reuse |
| Item response | `QuizAnswer` / attempt question data | Existing | Reuse after objective alignment |
| Material completion | `MaterialProgress` | Existing | Contextual evidence |
| Objective alignment | Material/question mappings | Future | Core gap |
| Internal mastery | Student-objective mastery state | Future | Must include evidence trace/version |
| Recommendation | Adaptive recommendation | Future | Must include rationale/version |
| Exposure | Recommendation event/log | Future | Distinguish generated vs shown |
| Intervention completion | Research event/action log | Future | Remediation/enrichment fidelity |
| Reassessment | Linked assessment event | Future | Link to intervention/objective |
| Teacher decision | Recommendation action | Future | Accept/modify/reject |
| Task usability metrics | Existing usability infrastructure | Existing/supporting | Can be adapted to research tasks |
| Technical quality | Tests/stress/security checks | Existing + future | Requirement-specific |

## 3. Research event taxonomy

The future event model should use a controlled taxonomy rather than arbitrary free-text event names.

Candidate groups:

### Learning-content events

- `material_opened`
- `material_completed`
- `slidebook_opened`

### Assessment events

- `assessment_started`
- `item_answered`
- `assessment_submitted`
- `reassessment_started`
- `reassessment_submitted`

### Adaptive events

- `mastery_recalculated`
- `recommendation_generated`
- `recommendation_exposed`
- `recommendation_accepted`
- `recommendation_rejected`
- `recommendation_modified`

### Intervention events

- `remediation_started`
- `remediation_completed`
- `enrichment_started`
- `enrichment_completed`
- `continue_action_followed`

The final taxonomy should be minimized to events that answer real research questions.

## 4. Minimum research event fields

Conceptually each research-relevant event should support:

- event ID;
- de-identified participant/research ID;
- authenticated student ID internally where required by the application;
- course;
- class/cluster where applicable;
- objective ID where applicable;
- event type;
- source object type/ID;
- timestamp;
- treatment condition;
- recommendation/intervention reference where applicable;
- adaptive policy version where applicable;
- metadata/value payload limited to necessary research data.

Research export should not expose unnecessary direct identifiers.

## 5. Pretest/posttest instrument requirements

The research learning-outcome instrument should:

1. be derived from the defined learning objectives;
2. have a test blueprint/table of specifications;
3. include an appropriate spread of cognitive demand for the target material;
4. undergo expert review/content validation;
5. be piloted where feasible;
6. record item-level responses for item analysis;
7. have documented scoring rules;
8. have documented reliability evidence appropriate to the instrument;
9. avoid leaking answers between study conditions;
10. maintain equivalent administration conditions.

Potential item-quality analysis may include, where appropriate:

- item difficulty;
- discrimination/corrected item-total relationship;
- distractor functioning for multiple-choice items;
- reliability coefficient appropriate to the final item format.

## 6. Learning-objective validation instrument

Experts should evaluate:

- clarity of objective wording;
- relevance to target curriculum/material;
- measurability;
- scope/granularity;
- sequence/prerequisite relationship where relevant.

The chosen content-validity index/statistic, if any, must match the number of experts, rating scale and methodological justification.

## 7. Material/objective alignment instrument

Experts/instructors should evaluate whether each material:

- directly teaches/supports the mapped objective;
- has the correct instructional role;
- is appropriate to remediation/enrichment where assigned;
- does not introduce an unsupported objective silently.

## 8. Question/objective alignment instrument

For each scored item, reviewers should evaluate:

- objective alignment;
- cognitive demand;
- technical correctness;
- clarity;
- answer-key correctness;
- distractor quality where applicable;
- suitability as mastery evidence.

Question provenance/source evidence from the existing AI-LCMS remains supporting evidence but does not replace expert validation of research alignment.

## 9. Adaptive recommendation validation instrument

Experts should be presented with structured cases containing enough information to judge the recommendation without revealing unnecessary identifiers.

Potential rating domains:

- consistency with stated evidence;
- pedagogical appropriateness;
- relevance of remediation/enrichment;
- clarity of rationale;
- risk of over/under-intervention;
- whether the same decision would be made by the reviewer.

Record categorical agreement (e.g. accept/modify/reject) as well as structured rating where useful.

## 10. Quality-in-use task instrument

Existing task-based infrastructure can be adapted for the final research features.

Recommended fields:

- participant research ID;
- role;
- task ID;
- completion status;
- time seconds;
- errors;
- assistance required;
- critical failure flag;
- observation notes;
- optional post-task ease/satisfaction rating if justified.

The instructor and student task sets must be reported separately.

## 11. Data dictionary requirements

Before R3 database implementation, every new research field should document:

- variable name;
- conceptual meaning;
- data type;
- unit/range;
- nullable conditions;
- source;
- event/time semantics;
- whether it is personally identifying;
- research role (primary outcome / covariate / process / fidelity / technical);
- export transformation/de-identification rule.

## 12. Research ID and privacy strategy

Preferred architecture:

- operational database may retain authenticated user IDs needed by AI-LCMS;
- research export maps users to study-specific participant IDs;
- direct identifiers are excluded from analytic datasets unless explicitly necessary and approved;
- the mapping key, if required, is stored separately with restricted access;
- logs do not store unnecessary free-text personal information.

## 13. Algorithm/configuration provenance

Every research-generated mastery/recommendation record should be traceable to:

- code/research baseline version;
- adaptive-policy version;
- objective-map version;
- assessment version where applicable.

This is necessary because a score generated by Policy v1 is not analytically identical to one generated by a later modified policy.

## 14. Analysis-ready export concept

The eventual export should support at least four logical datasets/tables:

### Participants

- research participant ID;
- condition;
- class/cluster;
- baseline/pretest;
- posttest;
- completion/missingness status.

### Objective evidence

- participant;
- objective;
- item/source;
- time;
- scored outcome;
- attempt/reassessment indicator.

### Adaptive interventions

- participant;
- objective;
- mastery-before;
- evidence sufficiency;
- decision;
- recommendation;
- exposed/not exposed;
- action/compliance;
- mastery-after or subsequent evidence reference;
- policy version.

### Teacher decisions

- recommendation;
- teacher decision;
- modification/reason category;
- timestamp.

## 15. Data-quality checks before analysis

Future research export must support checks for:

- duplicate event IDs;
- impossible timestamps/order;
- missing objective mappings;
- recommendation without mastery/evidence snapshot;
- intervention completion before exposure;
- reassessment preceding intervention;
- treatment mismatch;
- student/course mismatch;
- out-of-range scores;
- conflicting policy versions during the frozen experiment period.

## 16. Dummy data policy

Synthetic/demo data may be used for software testing only.

It must never be reported as respondent/student research evidence.

The final thesis dataset must distinguish:

- development/demo data;
- pilot data;
- main-study data.
