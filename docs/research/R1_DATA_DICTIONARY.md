# R1 — Current Research-Relevant Data Dictionary

Synchronized to canonical AI-LCMS baseline `39f34f411ccd68cdb54f47a1e8644808f7aedb4d`.

This dictionary records data that already exists in the product and may later contribute evidence to Topic C. Availability does not mean the variable is automatically valid as a research construct.

## 1. Identity and role

| Entity | Field/concept | Current meaning | Research use |
| --- | --- | --- | --- |
| User | user identifier | application identity | participant linkage; should be anonymized for research export |
| User | role | Admin/Instructor/Student | separates research workflows |
| User | active status | account availability | operational eligibility |

## 2. Course and enrollment

| Entity | Field/concept | Current meaning | Research use |
| --- | --- | --- | --- |
| Course | course id/title/slug | learning container | research context |
| Course | publication/status | course lifecycle | ensures equivalent delivery state |
| CourseEnrollment | student_id | enrolled student | participant-course linkage |
| CourseEnrollment | course_id | enrolled course | treatment context |
| CourseEnrollment | status | enrollment state | operational status |
| CourseEnrollment | progress_percentage | aggregate course progress | process evidence candidate; not mastery |
| CourseEnrollment | timestamps | enrollment record timing where available | administrative chronology |

## 3. Learning material

| Entity | Field/concept | Current meaning | Research use |
| --- | --- | --- | --- |
| LearningMaterial | section_id | location in course | content sequence context |
| LearningMaterial | title/slug/description | material metadata | research stimulus identification |
| LearningMaterial | content | learning content | intervention content |
| LearningMaterial | duration_minutes | intended duration metadata | descriptive context |
| LearningMaterial | order | learning sequence | sequence evidence |
| LearningMaterial | status | draft/processing/review/published/archived | controls exposure |
| LearningMaterial | published_at | publication timestamp | exposure readiness |
| MaterialDocument | uploaded document relation | source material | source provenance |
| DocumentExtraction | extracted content | machine-readable source | AI/content provenance |

### Current gap

Learning materials do not currently carry a verified relationship to a formal `LearningObjective`/competency entity.

## 4. Material progress

Current `MaterialProgress` records:

| Field | Meaning | Research limitation |
| --- | --- | --- |
| student_id | student | useful identity linkage |
| learning_material_id | material | useful content linkage |
| status | completion state | outcome is coarse and not an event history |
| completed_at | completion time | only completion point, not full exposure/interaction history |

This table is useful but insufficient to answer when material was opened, revisited, abandoned, recommended or completed as part of a specific adaptive intervention.

## 5. Slidebook and AI content

Existing product data include, where applicable:

- Slidebook material linkage;
- version/status lifecycle;
- slides and ordering;
- design/layout metadata;
- review/teacher checks;
- source references/provenance;
- AI processing information.

Research role: stable delivery and provenance of learning content.

These fields do not themselves define learner mastery.

## 6. Question metadata

Current `Question` provides:

| Field | Meaning | Potential research use |
| --- | --- | --- |
| question_bank_id | source bank | assessment organization |
| question_text | item stem | item identity |
| type | multiple choice / true-false | item format |
| topic | topic label | descriptive metadata only; not formal objective mapping |
| source_slide_number | source location | content provenance |
| source_excerpt | source text | content provenance |
| difficulty | easy/medium/hard | descriptive assessment metadata |
| explanation | teacher/student explanation | feedback content |
| points | item weight | scoring evidence |
| order | item sequence | display/assessment context |
| needs_review | review requirement | quality control |
| answer_source | explicit/inferred/manual | AI/answer provenance |
| status | draft/review/approved | assessment quality lifecycle |

### Current gap

A question cannot yet be persistently and formally mapped to one or more research learning objectives/competencies.

## 7. Quiz attempt

Current `QuizAttempt` provides strong existing assessment evidence:

| Field | Meaning | Research use candidate |
| --- | --- | --- |
| quiz_id | assessment identity | assessment context |
| student_id | learner identity | learner linkage |
| started_at | attempt start | process timing |
| expires_at | attempt deadline | assessment context |
| submitted_at | submission time | outcome timing |
| status | attempt lifecycle | filtering valid evidence |
| score | raw score | assessment evidence |
| percentage | normalized score | assessment evidence |
| correct_count | total correct | assessment evidence |
| wrong_count | total wrong | assessment evidence |
| duration_seconds | elapsed duration | process evidence |

`QuizAttempt` should be reused as an evidence source rather than replaced.

## 8. Quiz answer and attempt question

Existing answer-level data can provide:

- question linkage;
- selected response;
- correctness;
- attempt linkage;
- ordering/context from attempt questions where applicable.

Research value: evidence can later be aggregated by formal learning objective after objective-question mapping exists.

## 9. Course analytics

`CourseAnalyticsService` currently derives:

- total students;
- completed students;
- average progress;
- at-risk count according to product rules;
- quiz count;
- submitted attempts;
- quiz participation;
- average quiz score;
- pass rate;
- quiz-level performance;
- per-student attempts/progress/pass information;
- product intervention labels/messages;
- priority student ordering.

Research warning: the product intervention labels are not validated mastery or adaptive-learning decisions.

## 10. Learning analytics insights

`LearningAnalyticsInsightService` currently derives:

- latest submitted attempt per student/quiz;
- question response counts;
- correct/incorrect counts;
- correct rate;
- sample-sufficiency signal;
- review/monitor/healthy item signal;
- participant coverage;
- representative attempt count;
- latest-attempt pass rate;
- teacher-facing learning signals;
- priority questions.

Research warning: these are descriptive/heuristic evidence signals, not validated mastery estimates.

## 11. Usability/product quality data

Existing `UsabilityReportService` can process task-level data:

- participant_id;
- role;
- task_id;
- completion;
- time_seconds;
- errors;
- confusion_score.

It also supports SUS response processing.

Research position for the thesis:

- task metrics may be reused as supporting quality-in-use evidence when aligned with the final methodology;
- SUS remains legacy/product infrastructure and is not designated as the sole or primary thesis product-quality evaluation.

## 12. AI processing and provenance

Existing AI processing logs/results and provider configuration can support reproducibility by recording or documenting:

- provider/model configuration where available;
- processing lifecycle;
- generated content provenance;
- source evidence;
- errors/recovery information.

For formal thesis reproducibility, the eventual experiment package must freeze provider/model/instrument versions used during data collection.

## 13. Data not currently represented as required research constructs

The synchronized baseline does not yet provide persistent research data for:

- formal learning objective definitions;
- material-objective mapping;
- question-objective mapping;
- student-objective mastery state;
- evidence-to-mastery trace;
- validated adaptive-decision rationale;
- recommendation exposure;
- recommendation acceptance/compliance;
- remediation/enrichment linkage;
- pre/post intervention learner state;
- teacher accept/modify/reject action;
- adaptive/non-adaptive experiment assignment;
- complete learning-event history.

Those remain future research-specific data requirements and must be designed in R2 before database implementation.
