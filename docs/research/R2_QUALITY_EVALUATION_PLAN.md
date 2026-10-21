# R2 — Quality Evaluation Plan

## Purpose

This document replaces the idea of using one general SUS score as the primary product-quality evidence for the thesis.

The evaluation is structured using relevant parts of:

- ISO/IEC 25010:2023 — product quality model;
- ISO 9241-11:2018 — usability definitions and concepts in a specified context of use;
- ISO/IEC 25022:2016 — quality-in-use measurement guidance.

The study does **not** claim full certification or formal conformity to all requirements of these standards.

## Principle

ISO/IEC 25010:2023 is a quality model, not a ready-made questionnaire. Therefore each selected characteristic must be linked to:

1. an AI-LCMS requirement;
2. an operational measure;
3. a test/evaluation procedure;
4. an acceptance criterion defined for the project context.

## Product-quality scope

### 1. Functional Suitability — REQUIRED

Research focus:

- functional completeness of the adaptive workflow;
- functional correctness of calculations/mappings/decisions;
- functional appropriateness for the intended teacher/student tasks.

Candidate evidence:

- automated feature/unit tests;
- scenario-based black-box tests;
- known-input/known-output mastery calculation tests;
- objective mapping integrity tests;
- recommendation traceability tests;
- adaptive/non-adaptive isolation tests.

Acceptance criteria must be requirement-specific rather than a generic Likert percentage.

### 2. Performance Efficiency — REQUIRED

Research focus:

- response time of important learning and recommendation flows;
- capacity under realistic school-class usage;
- resource/load behavior for relevant endpoints.

Candidate evidence:

- existing stress/performance infrastructure;
- response-time distributions under predefined scenarios;
- failure/error rate during load scenarios.

The final load profile must be tied to expected classroom use rather than arbitrary very-large traffic.

### 3. Compatibility — SELECTED WHERE RELEVANT

Research focus:

- compatibility with supported browsers/devices;
- coexistence with the existing AI-LCMS stack;
- interoperability only where external integration is actually part of the tested requirement.

Candidate evidence:

- browser/device matrix;
- responsive functional checks;
- API/provider integration tests where relevant.

### 4. Interaction Capability — REQUIRED FOR USER INTERACTION

Research focus:

- whether instructors and students can understand and operate the adaptive workflow;
- protection from user error;
- clarity of recommendation/rationale;
- learnability/operability of relevant research features.

Candidate evidence:

- representative task scenarios;
- task completion;
- observed errors;
- user assistance required;
- qualitative issue observations;
- accessibility/inclusivity checks relevant to the interface.

This product-quality view is complemented by quality-in-use measures below.

### 5. Reliability — REQUIRED

Research focus:

- correct behavior across repeated adaptive calculations;
- recoverability/fault tolerance where AI/provider or network operations fail;
- prevention of duplicated recommendations/events;
- preservation of treatment/logging integrity.

Candidate evidence:

- automated failure/retry tests;
- transaction/idempotency tests where applicable;
- known failure scenarios;
- database integrity checks.

### 6. Security — REQUIRED

Research focus:

- authorization boundaries;
- confidentiality of student/research data;
- integrity of grades/mastery/treatment data;
- prevention of student access to instructor/research controls.

Candidate evidence:

- policy/authorization tests;
- treatment-condition access tests;
- research export de-identification checks;
- attempt/mastery integrity tests.

### 7. Maintainability — SUPPORTING ENGINEERING EVIDENCE

This may be documented using existing modular services, automated tests, separation of policy/configuration and change impact.

It is useful engineering evidence but is not automatically a primary thesis variable.

### 8. Flexibility — SUPPORTING / CONTEXT-DEPENDENT

Relevant only for justified aspects such as supported deployment/browser environments or scalability. Do not force every subcharacteristic into the thesis.

### 9. Safety — NOT PRIMARY FOR THIS THESIS

AI-LCMS is not treated as a safety-critical system. Educational/data risks should still be handled through governance, authorization and teacher oversight, but the thesis does not claim comprehensive evaluation of the ISO safety characteristic unless later required.

## Quality-in-use / usability framework

ISO 9241-11:2018 frames usability in terms of specified users achieving specified goals with effectiveness, efficiency and satisfaction in a specified context of use.

For AI-LCMS, contexts should be defined separately for:

- Instructor/Guru;
- Student/Siswa.

## Instructor context-of-use tasks

Research-feature tasks may include:

1. map a material/question to an objective;
2. inspect student mastery evidence;
3. interpret a recommendation rationale;
4. accept/modify/reject a recommendation where enabled;
5. review intervention history;
6. identify students/objectives requiring attention.

## Student context-of-use tasks

Research-feature tasks may include:

1. view recommended next action;
2. understand why an activity is recommended at an appropriate level of explanation;
3. open and complete remediation/enrichment;
4. complete reassessment;
5. continue normal learning when no adaptive action is required.

## Operational usability measures

### Effectiveness

Possible measures:

- task completion rate;
- critical task failure rate;
- correct interpretation of recommendation/action;
- proportion of tasks completed without facilitator intervention.

### Efficiency

Possible measures:

- time on task;
- number of unnecessary steps/errors;
- assistance count.

### Satisfaction

Use a research instrument or structured rating whose items and interpretation are explicitly documented.

Existing SUS code may remain available for historical/formative product comparison, but **SUS is not the primary thesis framework** and no claim of ISO compliance is created by replacing SUS labels with ISO labels.

### Qualitative evidence

Capture:

- recurring confusion;
- unexpected interpretation;
- recommendation explanation problems;
- teacher trust/oversight concerns;
- missing information required to act.

## ISO/IEC 25022 relationship

ISO/IEC 25022:2016 provides quality-in-use measures and explains measurement, but it does not provide one universal pass/fail percentage suitable for every product.

Therefore R2 requires AI-LCMS to define contextual acceptance criteria before evaluation.

## Proposed acceptance-criteria strategy

Do not use:

```text
ISO score >= 80% = feasible
```

unless an independently justified instrument explicitly supports that interpretation.

Instead define criterion-specific acceptance rules, for example conceptually:

| Requirement | Measure | Acceptance basis |
| --- | --- | --- |
| Core adaptive function | Expected output for verified scenarios | All critical scenarios correct |
| Authorization | Unauthorized research/admin access | Zero critical authorization leakage |
| Recommendation traceability | Recommendation with evidence/rationale/version | 100% of generated research recommendations traceable |
| User core task | Task completion | Target set before study and reported with raw counts/CI where appropriate |
| Performance | Response-time/load scenario | Threshold justified by classroom context/pilot |
| Reliability | Duplicate/lost critical research event | No known unresolved critical integrity defect before field experiment |

Final numeric acceptance criteria must be frozen before formal product evaluation.

## Evaluation sequence

### Stage 1 — Developer/technical verification

- automated tests;
- calculation correctness;
- authorization;
- integrity;
- performance/load;
- failure recovery.

### Stage 2 — Expert validation

- objective/item mapping;
- recommendation logic;
- pedagogical appropriateness;
- rationale clarity.

### Stage 3 — Representative-user quality-in-use evaluation

- instructor tasks;
- student tasks;
- effectiveness;
- efficiency;
- satisfaction;
- qualitative findings.

### Stage 4 — Pilot refinement

Fix material system issues, document changes and retest affected requirements before the main experiment.

## Reporting rules

The thesis should state:

- which ISO characteristics/measures were selected;
- why each was relevant;
- how each was operationalized;
- who/what was measured;
- the acceptance criterion;
- actual results;
- limitations.

Avoid statements such as “the system is ISO 25010 compliant” unless a defensible formal conformity process has actually been completed.

## Source references

- ISO/IEC 25010:2023: https://www.iso.org/standard/78176.html
- ISO 9241-11:2018: https://www.iso.org/standard/63500.html
- ISO/IEC 25022:2016: https://www.iso.org/standard/35746.html
