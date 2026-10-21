# R1 — Research Data Gaps and Conceptual Future Domain Model

Synchronized to canonical AI-LCMS baseline `39f34f411ccd68cdb54f47a1e8644808f7aedb4d`.

## Topic C requirement

The thesis requires a defensible chain from learning objective to evidence, mastery, adaptive decision, intervention and outcome.

The current product does not yet persist that chain.

## Research data gap matrix

| Research question the system must answer | Current support | Gap status | Future concept |
| --- | --- | --- | --- |
| Which learning objective does a material teach? | Material has section/content metadata only | Missing | Material–Learning Objective Mapping |
| Which learning objective does a question assess? | Question has topic/difficulty/source | Missing formal mapping | Question–Learning Objective Mapping |
| What evidence exists for one student-objective pair? | Quiz answers/attempts exist but not objective-linked | Partial | Mastery Evidence Trace |
| What is the student's mastery value for an objective? | No persistent mastery construct | Missing | StudentMastery |
| Which rule/model produced the mastery estimate? | No research mastery model | Missing | Mastery Estimation version/trace |
| What adaptive decision was generated? | Product follow-up heuristics exist | Missing validated adaptive decision | AdaptiveRecommendation |
| Why was that decision generated? | Product messages exist but no research-grade rationale record | Missing | Recommendation rationale/evidence snapshot |
| Was the recommendation shown? | No persistent exposure record | Missing | RecommendationExposure/LearningEvent |
| Did the student follow it? | No linked compliance/action record | Missing | RecommendationAction |
| Was remediation/enrichment completed? | Generic material/quiz completion exists but not intervention-linked | Missing linkage | Intervention linkage + event log |
| What changed after intervention? | Attempts/progress exist but no before/after intervention state | Missing | Reassessment + mastery history |
| Did teacher accept/modify/reject recommendation? | No persistent teacher decision | Missing | TeacherDecision / RecommendationAction |
| Was the learner in adaptive or non-adaptive condition? | No research treatment assignment | Missing | ExperimentAssignment |
| What happened over time during learning? | Limited completion/attempt timestamps | Partial | LearningEvent |

## Why current analytics do not close the gap

`CourseAnalyticsService` and `LearningAnalyticsInsightService` provide useful descriptive and heuristic signals. They can identify low progress, low scores, item difficulty patterns and limited evidence.

However, they do not provide formal objective alignment, persistent mastery, a validated adaptive policy, treatment assignment, intervention exposure or outcome traceability. Therefore they remain evidence/supporting layers, not the Topic C intervention itself.

## Conceptual future domain model

The following is design-only. No migration/model implementation is authorized in R1.2.

### 1. LearningObjective

Purpose: represents the competency or objective used by the research design.

Candidate fields:

- id
- course_id or curriculum scope
- code
- title
- description
- order
- active/version metadata

Research relevance: unit of mastery and adaptation.

### 2. LearningObjectiveMaterial

Purpose: maps a learning material to one or more objectives.

Candidate fields:

- learning_objective_id
- learning_material_id
- weight/primary flag if scientifically justified later
- mapping version / reviewer metadata if needed

Research relevance: identifies content used to teach an objective.

### 3. LearningObjectiveQuestion

Purpose: maps an assessment item to one or more objectives.

Candidate fields:

- learning_objective_id
- question_id
- optional weight if justified
- mapping version / validation metadata

Research relevance: makes answer evidence interpretable by objective.

### 4. LearningEvent

Purpose: research-grade event history beyond coarse completion.

Candidate fields:

- anonymized/student linkage
- course/material/quiz/question/objective linkage where relevant
- event type
- timestamp
- attempt/recommendation/intervention reference
- minimal metadata required by the research protocol

Possible event families:

- material_opened
- material_completed
- quiz_started
- question_answered
- quiz_submitted
- recommendation_generated
- recommendation_exposed
- recommendation_actioned
- remediation_started/completed
- enrichment_started/completed
- reassessment_completed

Research relevance: reconstructs exposure and process.

### 5. StudentMastery

Purpose: persistent current or historical estimate for a student-objective pair.

Candidate fields:

- student_id
- learning_objective_id
- mastery_value/state
- model/version
- evidence_count
- calculated_at

Research relevance: learner-state representation.

### 6. MasteryEvidence / mastery trace

Purpose: records which evidence produced a mastery estimate.

Candidate fields:

- student_mastery reference
- quiz_attempt/answer/event reference
- evidence type
- contribution/weight only if methodologically justified
- model version

Research relevance: auditability and reproducibility.

### 7. AdaptiveRecommendation

Purpose: persistent research intervention decision.

Candidate fields:

- student_id
- learning_objective_id
- recommendation type: remedial / continue / enrichment
- rationale
- evidence snapshot / model version
- created_at
- intervention target
- status

Research relevance: core adaptive-learning output.

### 8. RecommendationAction

Purpose: records exposure and response by learner/teacher.

Candidate fields:

- recommendation_id
- actor/role
- action type
- timestamp
- optional reason/modified target

Research relevance: distinguishes generated recommendation from actual intervention.

### 9. ExperimentAssignment

Purpose: freezes treatment condition.

Candidate fields:

- student/class/course research unit
- condition: adaptive / non-adaptive
- assignment method
- assigned_at
- research cohort/version

Research relevance: protects experimental integrity.

## Important R2 design decisions still open

R1 deliberately does not decide:

- the mathematical or rule-based mastery model;
- whether mastery is numeric, categorical or hybrid;
- evidence weighting;
- mastery thresholds;
- remediation/enrichment thresholds;
- minimum evidence required;
- how expert validation is conducted;
- whether adaptation is student-level or cluster/class-level;
- exact experiment unit and sample allocation;
- final ISO-based quality-evaluation characteristics and measures.

These decisions belong to R2 because they require methodological justification rather than code convenience.

## Scope guard

Do not infer that existing product thresholds (`50`, `60`, `70`, completion `100`, minimum responses `3`) should become research thresholds. Their current role is product guidance and must be separated from future validated research rules.
