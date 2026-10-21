# R2 — Research Design & Operational Definition

## Status

This phase defines the research model for Topic C before any adaptive-learning runtime implementation.

**No mastery algorithm, adaptive threshold, recommendation engine, treatment assignment, or research migration is implemented in R2.**

## Working thesis title

**Pengembangan AI-LCMS dengan Mekanisme Pembelajaran Adaptif Berbasis Learning Analytics pada Pembelajaran Pemrograman di SMK**

The title intentionally emphasizes development of the adaptive mechanism. Claims about improved learning outcomes remain hypotheses to be tested rather than assumptions built into the product.

## Basis from supervisor evaluation

The selected direction is Topic C: adaptive learning based on learning analytics. The required new contribution is centered on:

- mapping learning objectives to materials and assessment items;
- estimating student mastery;
- defining a recommendation/remediation policy;
- recording recommendation exposure and treatment;
- supporting teacher acceptance/oversight;
- comparing adaptive and non-adaptive learning conditions;
- evaluating learning outcomes empirically.

Existing AI-LCMS capabilities remain the engineering baseline and supporting infrastructure.

## Research problem

AI-LCMS currently records course progress, material completion, quiz attempts, item responses and teacher-facing analytics. However, the existing analytics use product heuristics and do not form a validated persistent chain from objective-aligned evidence to competency mastery and adaptive intervention.

The research problem is therefore not “how to build an LMS with AI”, but:

> how to design, implement and evaluate an auditable adaptive-learning mechanism that converts objective-aligned learning evidence into mastery estimates and defensible learning recommendations, while retaining teacher oversight.

## Research questions

### RQ1 — Development

How can an AI-LCMS be developed with an adaptive-learning mechanism based on learning analytics for programming learning in vocational high school?

### RQ2 — Product quality

How is the quality of the developed AI-LCMS when evaluated using relevant software/product-quality and quality-in-use measures?

### RQ3 — Recommendation validity

How appropriate are the adaptive recommendations produced from objective-level mastery evidence according to predefined rules, expert judgement and pilot evidence?

### RQ4 — Learning effect

Is there a difference in competency mastery between students who use adaptive AI-LCMS and students who use the same AI-LCMS without adaptive recommendations?

## Research objectives

1. Develop objective–material–question alignment in AI-LCMS.
2. Define an auditable learning-evidence model.
3. Develop an objective-level mastery estimation mechanism.
4. Develop an interpretable adaptive-decision and recommendation mechanism.
5. Support remediation, continuation, enrichment and reassessment workflows.
6. Record recommendation rationale, exposure, action and outcome.
7. Preserve teacher oversight through accept/modify/reject decisions where the instructor workflow requires it.
8. Evaluate software quality and quality in use using explicit operational measures rather than a single questionnaire score.
9. Evaluate recommendation validity before the main field experiment.
10. Compare adaptive and non-adaptive learning conditions empirically.

## Research contribution boundary

### Supporting infrastructure — not thesis novelty

- authentication and roles;
- course, section and enrollment management;
- AI Slidebook authoring/review;
- AI Quiz and question-bank workflow;
- quiz attempts and answers;
- source evidence and AI-provider infrastructure;
- current course analytics and Learning Analytics Insights;
- task-based usability reporting;
- accessibility, performance/stress and demo-readiness infrastructure.

### Research contribution

- learning-objective representation;
- material/objective and question/objective mapping;
- research event model;
- objective-level mastery state plus evidence trace;
- validated adaptive-decision policy;
- adaptive recommendation plus rationale;
- remediation/enrichment/reassessment linkage;
- recommendation exposure/action log;
- teacher decision log;
- adaptive/non-adaptive treatment assignment;
- research-oriented export and analysis dataset.

## Core research pipeline

```text
Learning Objective
        ↓
Aligned Material + Aligned Questions
        ↓
Learning Evidence
        ↓
Mastery Estimation
        ↓
Evidence Sufficiency Check
        ↓
Adaptive Decision
        ↓
Recommendation
        ↓
Intervention / Continue / Enrichment
        ↓
Reassessment / Subsequent Evidence
        ↓
Outcome and Updated Mastery
```

The existing `CourseAnalyticsService` and `LearningAnalyticsInsightService` remain contextual/evidence-support services. Their current fixed cutoffs are not automatically promoted into this research pipeline.

## Research paradigm and design

The planned thesis combines:

1. **Design and Development Research** for specification, construction, expert review, technical validation and iterative refinement of the adaptive mechanism.
2. **Quasi-experimental / class-based comparative evaluation** for the pedagogical effect, subject to school access and class allocation constraints.

The main comparison should keep the surrounding platform as constant as possible:

- Experimental condition: **AI-LCMS + adaptive mechanism enabled**.
- Comparison condition: **AI-LCMS + adaptive mechanism disabled**.

This reduces confounding from comparing two completely different systems.

## Primary outcome

The primary pedagogical outcome is **competency mastery / learning achievement on the targeted programming objectives**, measured with a research assessment aligned to those objectives.

Pretest and posttest are kept conceptually separate from the internal adaptive mastery state:

- internal mastery state drives the adaptive mechanism;
- pretest/posttest provide an external research outcome for group comparison.

This separation reduces circularity where the same computed variable is both treatment mechanism and sole proof of treatment success.

## Secondary/process outcomes

Potential process measures include:

- objective-level assessment performance;
- number of attempts;
- remediation exposure and completion;
- reassessment result;
- recommendation acceptance/compliance;
- material completion;
- time/duration evidence where technically reliable;
- teacher accept/modify/reject decisions;
- frequency of insufficient-evidence states.

These are supporting outcomes and should not replace the primary learning outcome.

## Design principles for R3 implementation

The later runtime implementation must follow these R2 rules:

1. **Traceability:** every mastery state must be traceable to evidence.
2. **Interpretability:** every recommendation must expose a human-readable rationale.
3. **Objective alignment:** materials and questions must be explicitly tied to learning objectives.
4. **Evidence sufficiency:** no strong adaptive decision should be made when evidence is insufficient.
5. **Configurable research parameters:** any cutoffs/weights must be versioned and centrally configurable, not scattered as magic numbers.
6. **Teacher oversight:** the system is decision support, not an unquestionable final authority.
7. **Treatment integrity:** adaptive and non-adaptive conditions must be distinguishable and logged.
8. **Reproducibility:** algorithm/configuration version, baseline commit and data dictionary must be recorded.
9. **Privacy:** research exports must use de-identified participant identifiers.
10. **No hidden use of product heuristics:** existing analytics thresholds must remain separate unless deliberately validated and adopted through the research process.

## Decision sequence before R3

R2 must lock the following before code is introduced:

- definition and granularity of learning objectives;
- which observations count as primary mastery evidence;
- which observations are contextual only;
- candidate mastery-estimation method;
- evidence sufficiency rule;
- candidate adaptive states and actions;
- procedure for expert validation;
- pilot procedure for parameter refinement;
- experiment condition definition;
- software-quality and quality-in-use measures;
- research data retention/export rules.

## External standards used for evaluation planning

- **ISO/IEC 25010:2023** — product quality model. It is treated as a quality model/reference, not a ready-made questionnaire.
- **ISO 9241-11:2018** — usability concepts in a specified context of use, centered on effectiveness, efficiency and satisfaction.
- **ISO/IEC 25022:2016** — quality-in-use measurement guidance and measures; it does not prescribe universal pass/fail cutoffs for every product.

The final subset of characteristics and measures must be justified against AI-LCMS requirements rather than attempting to measure every ISO characteristic equally.

## Research references used in R2 planning

Official standards pages:

- https://www.iso.org/standard/78176.html
- https://www.iso.org/standard/63500.html
- https://www.iso.org/standard/35746.html

Recent supporting literature consulted during R2 planning:

- du Plooy, E., Casteleijn, D., & Franzsen, D. (2024). *Personalized adaptive learning in higher education: A scoping review of key characteristics and impact on academic performance and engagement*. Heliyon, e39630. https://doi.org/10.1016/j.heliyon.2024.e39630
- Paulsen, L., & Lindsay, E. (2024). *Learning analytics dashboards are increasingly becoming about learning and not just analytics — A systematic review*. Education and Information Technologies, 29, 14279–14308. https://doi.org/10.1007/s10639-023-12401-4

These external sources support design context only; the supervisor evaluation remains the project-specific basis for selecting Topic C.

## R2 gate

R2 is complete only when operational definitions, evidence/mastery policy, experiment design, quality evaluation plan and data/instrument plan are all documented without implementing the adaptive runtime.
