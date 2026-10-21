# R1 — REUSE / MODIFY / DEVELOP Matrix

This matrix is synchronized to canonical baseline `39f34f411ccd68cdb54f47a1e8644808f7aedb4d` and the Topic C thesis direction.

## Classification rules

- **REUSE**: existing capability suitable as research infrastructure without becoming thesis novelty.
- **MODIFY**: existing capability that later needs research-specific extension.
- **DEVELOP**: research-specific capability not currently available in the validated form required by Topic C.
- **SUPPORTING INFRASTRUCTURE**: useful product capability outside the main thesis novelty.

## REUSE

| Component | Current role | Research role |
| --- | --- | --- |
| Authentication | login/session infrastructure | identity and access |
| Role management | Admin/Instructor/Student | research workflow separation |
| Authorization/policies | protects instructor/student resources | research data protection baseline |
| Course | learning container | treatment/course context |
| Course Section | content organization | learning sequence container |
| Enrollment | student-course relationship and progress | participant-course linkage |
| Learning Material lifecycle | draft/review/published content | delivery infrastructure |
| AI Slidebook | AI-assisted content authoring and review | learning-content delivery |
| Slidebook lifecycle/versioning | human review and publication | stable learning content |
| Question Bank | assessment item organization | assessment infrastructure |
| AI Question/Quiz generation | teacher-reviewed AI-assisted assessment | source of assessment items |
| Quiz lifecycle | draft/review/publish/use | research assessment delivery |
| Quiz Attempt | time, score, percentage, duration and status | assessment evidence |
| Quiz Answer | item-level response evidence | mastery evidence candidate |
| AI Provider infrastructure | mock/OpenAI/Groq/Gemini | supporting AI infrastructure |
| Existing automated tests | regression protection | engineering quality baseline |
| CourseWorkflowReadinessService | instructor workflow guidance | product readiness support |
| DemoReadinessService | deterministic demo checks | product/demo support |
| Accessibility support | product interaction support | product quality evidence |
| Performance/stress tests | product performance checks | product quality evidence |

## MODIFY LATER

| Component | Current limitation | Required research extension |
| --- | --- | --- |
| LearningMaterial | no objective/competency alignment | map material to learning objectives |
| Question | topic/difficulty/source metadata but no objective alignment | map item to learning objective/competency |
| MaterialProgress | completion-oriented only | complement with event/process evidence |
| Assessment provenance | rich source provenance but not objective evidence | connect evidence to objective/mastery model |
| CourseAnalyticsService | descriptive + heuristic intervention labels | expose research context without treating rules as validated adaptive policy |
| LearningAnalyticsInsightService | class/item signals based on fixed product thresholds | use as evidence layer; separate from validated mastery/adaptive model |
| Instructor analytics UI | presents product analytics | later expose mastery/recommendation evidence transparently |
| Student dashboard | progress/feedback oriented | later present assigned adaptive intervention where applicable |
| Recommendation presentation | generic teacher follow-up messages | later distinguish validated adaptive recommendation from product hints |
| Research-specific tests | current tests protect product behavior | later add tests for objective mapping, mastery, intervention and treatment isolation |

## DEVELOP LATER — THESIS-SPECIFIC

1. Learning Objective Management
2. Material–Learning Objective Mapping
3. Question–Learning Objective Mapping
4. Research Learning Event Log
5. Student–Objective Mastery State
6. Mastery Evidence Trace
7. Mastery Estimation Service
8. Adaptive Decision Policy
9. Adaptive Recommendation entity/service
10. Remediation flow
11. Enrichment flow
12. Continue/advance decision flow
13. Reassessment linkage
14. Recommendation rationale persistence
15. Recommendation exposure log
16. Recommendation action/compliance log
17. Teacher accept/modify/reject decision
18. Adaptive vs Non-Adaptive experiment assignment
19. Intervention-period consistency controls
20. Research data export with privacy safeguards

## Supporting Infrastructure — not thesis novelty

The following may be academically useful but must not be presented as the primary Topic C contribution:

- Slidebook themes/layouts;
- UI design system;
- generic CRUD;
- file upload/document extraction;
- AI provider switching;
- SlidebookQualityService;
- CourseWorkflowReadinessService;
- DemoReadinessService;
- accessibility utilities;
- performance/stress testing;
- legacy/formative usability tooling including SUS scoring;
- generic course analytics dashboards.

## Existing heuristics register

| Rule | Component | Current purpose | Research status |
| --- | --- | --- | --- |
| progress `>=100%` or enrollment completed | CourseAnalyticsService | stable/completed display | Existing Product Heuristic — Requires Research Validation |
| progress `<50%` | CourseAnalyticsService | high-priority follow-up | Existing Product Heuristic — Requires Research Validation |
| average quiz score `<70%` with attempts | CourseAnalyticsService | high-priority follow-up | Existing Product Heuristic — Requires Research Validation |
| incomplete materials/quizzes | CourseAnalyticsService | medium-priority follow-up | Existing Product Heuristic — Requires Research Validation |
| severity order high→medium→low→stable | CourseAnalyticsService | priority sorting | Product display logic |
| minimum item responses = 3 | LearningAnalyticsInsightService | evidence sufficiency | Existing Product Heuristic — Requires Research Validation |
| correct rate `<50%` | LearningAnalyticsInsightService | item review signal | Existing Product Heuristic — Requires Research Validation |
| correct rate `<70%` | LearningAnalyticsInsightService | item monitor signal | Existing Product Heuristic — Requires Research Validation |
| participant coverage `<70%` | LearningAnalyticsInsightService | developing-evidence label | Existing Product Heuristic — Requires Research Validation |
| participant coverage `<50%` | LearningAnalyticsInsightService | participation signal | Existing Product Heuristic — Requires Research Validation |
| latest pass rate `<60%` with ≥3 representative attempts | LearningAnalyticsInsightService | high-priority class signal | Existing Product Heuristic — Requires Research Validation |
| average course progress `<50%` | LearningAnalyticsInsightService | progress signal | Existing Product Heuristic — Requires Research Validation |
| Slidebook warning/critical thresholds | SlidebookQualityService | authoring quality control | Supporting product heuristic; not mastery/adaptive policy |

## Decision

The synchronized repository remains appropriate for Topic C. The research novelty begins where the current product stops: validated objective alignment, mastery estimation, adaptive decision/intervention, and outcome traceability.
