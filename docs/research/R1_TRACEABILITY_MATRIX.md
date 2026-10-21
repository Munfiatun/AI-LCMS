# R1 — Thesis Traceability Matrix

Synchronized to canonical AI-LCMS baseline `39f34f411ccd68cdb54f47a1e8644808f7aedb4d`.

The purpose of this matrix is to distinguish the existing engineering platform from the future Topic C research contribution.

| Thesis requirement | Existing component/evidence | Current gap | Future research module |
| --- | --- | --- | --- |
| Deliver structured learning content | Course, Section, LearningMaterial, Slidebook | no formal objective alignment | LearningObjective + material mapping |
| Generate/review assessment | QuestionBank, Question, AI Quiz, teacher review | no formal objective mapping | question-objective mapping |
| Collect assessment evidence | QuizAttempt, QuizAnswer, attempt questions | evidence not organized by objective | mastery evidence trace |
| Collect learning-process evidence | MaterialProgress, attempt timestamps/duration | event history is incomplete | LearningEvent |
| Estimate objective-level mastery | product scores/progress/analytics | no persistent validated mastery model | StudentMastery + MasteryEstimationService |
| Explain learner state | analytics signals/messages | signals are heuristic, not mastery evidence trace | mastery rationale/evidence trace |
| Produce adaptive decision | generic product intervention messages | no validated adaptive policy | AdaptiveDecision / AdaptiveRecommendation |
| Provide remediation | existing materials/quizzes can deliver content | not linked to adaptive decision | remediation intervention flow |
| Provide enrichment | existing content can deliver extension material | not linked to adaptive decision | enrichment intervention flow |
| Continue/advance learner | course sequence exists | no validated mastery-based advance rule | continue/advance policy |
| Reassess after intervention | quiz attempts/retries exist | no explicit intervention→reassessment linkage | reassessment linkage |
| Record recommendation rationale | generic messages exist | no persistent research rationale/model version | recommendation evidence snapshot |
| Record recommendation exposure | no reliable persistent exposure state | missing | recommendation exposure event |
| Record learner response | generic activity exists | recommendation compliance cannot be identified | RecommendationAction |
| Record teacher oversight | teacher reviews AI content | no teacher decision on adaptive recommendation | teacher accept/modify/reject action |
| Separate adaptive vs non-adaptive treatment | same LMS infrastructure can support both | no treatment assignment | ExperimentAssignment |
| Evaluate product functionality | automated tests/quality gates | research feature tests not yet present | research-specific automated tests |
| Evaluate performance/accessibility | existing stress/accessibility infrastructure | final thesis measures not yet selected | reuse supporting quality evidence |
| Evaluate quality in use | task-based usability service and protocol | existing protocol includes legacy SUS; ISO operationalization not yet designed | R2/R4 evaluation design |
| Support reproducibility | Git history, provider config, provenance | research baseline/model/instrument versions must be frozen | research package/version registry |
| Export research dataset | application DB contains operational data | no privacy-aware research export | ResearchExport |

## Topic C contribution boundary

### Existing supporting platform

AI-LCMS already supports:

- learning content management;
- AI-assisted Slidebook and assessment authoring;
- teacher review and publication;
- student learning and quizzes;
- progress and assessment recording;
- descriptive/heuristic learning analytics;
- product usability, accessibility, quality and readiness tooling.

### Research contribution to be designed after R1

The Topic C contribution begins with a validated mechanism that can connect:

```text
Objective alignment
        ↓
Evidence
        ↓
Mastery estimation
        ↓
Adaptive decision
        ↓
Intervention
        ↓
Reassessment/outcome
```

plus traceability of recommendation exposure, action, teacher oversight and experimental treatment.

## Existing analytics classification

`CourseAnalyticsService` and `LearningAnalyticsInsightService` are **existing evidence/analytics infrastructure**.

Their current thresholds are explicitly classified as:

**Existing Product Heuristic — Requires Research Validation**

They are not the scientific adaptive-learning model to be claimed by the thesis.

## Existing usability classification

The current task-based usability infrastructure can be retained as supporting product evidence. Existing SUS scoring is legacy/formative infrastructure. The thesis evaluation plan must be defined separately and should operationalize the ISO-based approach requested by the supervisor rather than simply renaming SUS as ISO.

## R1 conclusion

The synchronized baseline supports the thesis technically without erasing the research gap. R2 must define constructs and operational rules before any adaptive migration/service is implemented.
