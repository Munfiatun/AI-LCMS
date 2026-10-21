# Research Phase R1.2 — Baseline Sync & Revalidation

## Status

`RESEARCH PHASE R1.2 COMPLETE` for remote baseline synchronization and documentation revalidation.

R2 has **not** started. No adaptive-learning runtime feature was implemented.

## 1. Old local baseline

Legacy audit baseline:

`f469135848d78e4ebb1ff1bcd41aacaaa312ae1e`

The user's local checkout was reported at this commit and still used the legacy local remote URL before synchronization guidance.

## 2. New canonical baseline

Canonical repository:

`Munfiatun/AI-LCMS`

Canonical baseline used for R1.2:

`39f34f411ccd68cdb54f47a1e8644808f7aedb4d`

This baseline is 83 commits ahead of the original audit baseline.

## 3. Synchronization method

Because the assistant cannot directly modify the user's `/Users/...` local checkout, synchronization was performed safely on GitHub:

1. verified canonical repository identity;
2. verified the remote baseline commit;
3. created branch `research/r1-2-baseline-sync` from canonical `main`;
4. did not force-update or merge `main`;
5. rebuilt synchronized research documentation on the current baseline;
6. corrected stale repository identity examples in README only;
7. did not modify runtime PHP/JS/database behavior.

The user's local R1/R1.1 files should be committed/stashed before pulling/cherry-picking this branch to avoid local-file conflicts.

## 4. Research documentation set

The synchronized remote research documentation consists of:

- `docs/research/R1_BASELINE_GAP_AUDIT.md`
- `docs/research/R1_REUSE_MODIFY_DEVELOP.md`
- `docs/research/R1_DATA_DICTIONARY.md`
- `docs/research/R1_RESEARCH_DATA_GAPS.md`
- `docs/research/R1_TRACEABILITY_MATRIX.md`
- `docs/research/R1_2_BASELINE_SYNC_REVALIDATION.md`

The first synchronized five-document set was complete through commit:

`341c011421ee6163c6d46b56e6b8d78064f1af34`

## 5. Relevant product changes discovered in the newer baseline

The delta from the original audit includes important product work, including:

- `LearningAnalyticsInsightService`;
- instructor analytics updates;
- `CourseWorkflowReadinessService`;
- `SlidebookQualityService`;
- `UsabilityReportService` and usability report command;
- accessibility support;
- performance/stress testing;
- demo/academic readiness infrastructure;
- AI reliability/recovery improvements;
- additional regression tests and product documentation.

These improve the engineering baseline but do not erase the Topic C research gap.

## 6. Changes to REUSE classification

The following newer modules are now explicitly reusable/supporting infrastructure:

- `LearningAnalyticsInsightService` as analytics/evidence infrastructure;
- `CourseWorkflowReadinessService` as instructor workflow support;
- existing task-based usability metrics as supporting quality-in-use evidence;
- accessibility and performance/stress infrastructure;
- demo readiness and academic-readiness checks;
- Slidebook quality tooling as content-authoring quality support.

They are not classified as new thesis novelty.

## 7. Changes to MODIFY classification

The synchronized baseline strengthens the need to later modify/extend:

- material-objective alignment;
- question-objective alignment;
- learning-process instrumentation;
- analytics context and evidence traceability;
- instructor/student presentation of future mastery/recommendation evidence;
- research-specific automated tests.

Existing analytics should be preserved but scientifically separated from future mastery/adaptive rules.

## 8. Changes to DEVELOP classification

The core DEVELOP list remains materially unchanged:

- learning objectives;
- material/question objective mappings;
- learning events;
- mastery state and evidence trace;
- mastery estimation;
- adaptive decision/recommendation;
- remediation/enrichment/continue flow;
- reassessment linkage;
- recommendation rationale/exposure/action logs;
- teacher accept/modify/reject decision;
- adaptive/non-adaptive experiment assignment;
- privacy-aware research export.

## 9. Analytics heuristics revalidated

### CourseAnalyticsService

Current examples include:

- completion/stable at 100% progress or completed enrollment;
- progress below 50% → high-priority product intervention;
- average quiz score below 70% with attempts → high-priority product intervention;
- incomplete materials/quizzes → medium-priority product intervention;
- severity ordering for priority display.

### LearningAnalyticsInsightService

Current examples include:

- minimum 3 item responses;
- correct rate below 50% → review;
- correct rate below 70% → monitor;
- participant coverage/evidence labels;
- coverage below 50% participation signal;
- latest pass rate below 60% with at least 3 representative attempts → high signal;
- average progress below 50% progress signal.

All remain classified:

**Existing Product Heuristic — Requires Research Validation**

They are not approved mastery thresholds or adaptive policies.

## 10. Research data gaps revalidated

The synchronized baseline still cannot persistently answer, in a research-grade way:

- the learning objective taught by each material;
- the learning objective assessed by each question;
- objective-level mastery for each student;
- the evidence trace behind mastery;
- adaptive recommendation and validated rationale;
- recommendation exposure and compliance;
- intervention-linked remediation/enrichment outcome;
- mastery before/after intervention;
- teacher recommendation decision;
- adaptive/non-adaptive treatment assignment;
- complete event history across the learning process.

Therefore Topic C remains viable and non-trivial.

## 11. SUS/usability legacy classification

The synchronized product contains useful task-based usability infrastructure:

- task completion;
- time on task;
- errors;
- observed confusion;
- per-role and per-task summaries.

It also contains SUS scoring and SUS documentation.

For the thesis:

- existing SUS support is retained as legacy/formative product infrastructure;
- it is not the sole or primary thesis product-quality evaluation;
- the final ISO-based evaluation and operational measures must be defined in the research methodology before implementation/evaluation.

## 12. Test result

Not executed from this GitHub connector session because the connector provides repository operations, not an execution environment for `composer run release:check`.

The branch is documentation/README-only relative to the canonical runtime baseline. A Pull Request can be used to trigger the repository's configured GitHub Actions quality gate if desired.

## 13. Build result

Not executed locally for the same reason above. No frontend/runtime file was modified by R1.2.

## 14. Runtime code changes made by this phase

None.

Changed scope is restricted to:

- README repository identity examples;
- research Markdown documentation.

No PHP, JavaScript, CSS, migration, model, service, controller, route or test behavior was changed.

## 15. Remaining synchronization issue

The remote research branch is synchronized to canonical baseline, but the user's Mac checkout still needs a normal Git synchronization step. Because the local checkout contains local R1/R1.1 documentation, those local changes should be committed or stashed before switching/pulling the research branch.

## Gate to R2

R2 may start only after this branch/documentation is accepted as the research baseline. R2 must define constructs and operational definitions before adaptive feature implementation, particularly:

`Learning Objective → Evidence → Mastery → Decision → Intervention → Outcome`.

RESEARCH PHASE R1.2 COMPLETE
