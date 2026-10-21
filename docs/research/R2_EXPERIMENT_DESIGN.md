# R2 — Experiment Design

## Purpose

This document defines the pedagogical evaluation plan for Topic C without running the experiment or creating treatment code.

## Recommended design

A **quasi-experimental pretest–posttest comparison using intact classes** is the default design when random assignment of individual students is not feasible in the school setting.

Preferred conditions:

- **Experimental:** AI-LCMS with the frozen adaptive mechanism enabled.
- **Comparison/control:** the same AI-LCMS with the adaptive mechanism disabled.

The two conditions should use the same teacher where feasible, the same learning objectives, equivalent content, equivalent assessment coverage, similar duration and the same core platform features.

## Why the same platform is used

Using the same AI-LCMS in both conditions reduces confounding from differences in interface, content-authoring tools, quiz engine or general LMS experience. The intended treatment contrast is the adaptive mechanism itself.

## Participants and sampling

The final sample depends on school access and class structure.

R2 requirements:

1. identify the target population and inclusion criteria;
2. document how intact classes are selected/assigned;
3. perform an a priori sample-size/power justification where feasible;
4. record class, teacher and schedule information that may create cluster effects;
5. avoid claiming individual randomization when assignment occurs at class level.

## Important cluster warning

If only one class receives adaptive treatment and one class receives non-adaptive treatment, treatment is completely confounded with class. Statistical significance alone cannot remove that limitation.

Therefore, if resources permit, multiple classes/clusters per condition are preferable. If not possible, the limitation must be explicitly reported and conclusions kept appropriately bounded.

## Pretest

The pretest should:

- measure the targeted learning objectives before treatment;
- be equivalent across groups;
- avoid using personalized adaptive feedback;
- be scored independently of the internal mastery state;
- provide a baseline covariate for primary analysis.

## Treatment period

During the intervention period:

### Adaptive condition

Students may receive objective-specific:

- remediation;
- continuation guidance;
- enrichment;
- reassessment;

according to the frozen adaptive policy.

### Non-adaptive condition

Students follow the predefined ordinary course sequence without individualized adaptive decisions from the research mechanism.

Both groups still use the same core platform features required for learning and assessment.

## Treatment fidelity

The system must log enough information to determine whether treatment was actually delivered.

Minimum adaptive fidelity evidence:

- treatment assignment;
- policy/configuration version;
- recommendation generated;
- recommendation exposed;
- intervention started/completed where relevant;
- reassessment completed where relevant.

A student assigned to the adaptive group but never exposed to a recommendation should not be silently treated as identical to a student who actually received several adaptive interventions.

## Contamination control

Potential contamination must be documented, including:

- students sharing personalized recommendations across classes;
- teacher manually providing adaptive support to the comparison class;
- students accessing content intended only for another treatment condition;
- schedule/teacher differences;
- different assessment difficulty across groups.

The application should later enforce treatment boundaries where technically appropriate.

## Posttest

The posttest should:

- cover the same target objectives as the pretest;
- be administered under equivalent conditions;
- serve as the principal external learning outcome;
- remain analytically distinct from the internal adaptive mastery estimate.

Where identical pre/post items could create recall effects, equivalent forms may be used if their alignment and difficulty are appropriately validated.

## Primary outcome

Primary outcome:

**Post-intervention achievement/mastery on the targeted programming objectives measured by the external research assessment.**

## Secondary outcomes

Possible secondary/process outcomes:

- objective-specific scores;
- remediation frequency;
- reassessment improvement;
- recommendation compliance;
- completion/progress;
- number of attempts;
- teacher-system recommendation agreement;
- engagement/process events where validity is adequate.

Secondary outcomes must be identified as secondary to reduce selective interpretation.

## Primary statistical analysis

The default analysis should account for baseline performance rather than comparing raw posttest scores alone.

### When class structure is simple and assumptions are defensible

A candidate model is ANCOVA / regression:

```text
Posttest = Group + Pretest + relevant prespecified covariates
```

Report:

- adjusted group difference;
- confidence interval;
- effect size;
- model assumptions/diagnostics.

### When multiple classes/clusters are available

Prefer a multilevel/mixed-effects model that accounts for class clustering, for example a random class intercept where the design supports it.

The exact final model must match the realized number of clusters and sample structure.

## Why N-gain is not the sole inferential result

Normalized gain may be presented descriptively if useful, but it should not replace a model that directly accounts for baseline performance, class structure and uncertainty.

## Assumption checks and robust alternatives

Before final inferential analysis, inspect:

- distribution/residual assumptions;
- linearity of baseline–posttest relation where applicable;
- homogeneity of regression slopes for ANCOVA;
- influential observations;
- clustering;
- missingness;
- ceiling/floor effects.

If assumptions are seriously violated, document and use an appropriate robust/non-parametric or generalized alternative rather than forcing the planned model.

## Missing data

R2 policy:

- never replace missing research outcomes with fabricated values;
- distinguish technical missingness from participant absence/non-completion;
- report missingness by treatment group;
- define the primary analysis population before main analysis;
- document any imputation method if later used.

## Analysis populations

Recommended distinction:

### Intention-to-treat-like / assigned-group analysis

Where feasible, analyze students according to assigned treatment condition to preserve the comparison created by the research design.

### Per-protocol/process analysis

A secondary analysis may examine students with adequate intervention exposure/compliance.

This secondary analysis must not replace the primary group comparison without justification because exposure itself may be related to student characteristics.

## Effect reporting

Do not report only p-values.

Report as appropriate:

- means/medians and dispersion;
- adjusted mean difference or model coefficient;
- 95% confidence interval;
- effect size;
- sample size per group;
- number of classes/clusters;
- missing data;
- treatment-exposure summary.

## Recommendation-validation study before main experiment

Before the main field experiment, conduct an expert/pilot evaluation of the adaptive recommendation mechanism.

Possible outputs:

- expert agreement on objective mapping;
- agreement on recommended action;
- frequency of inappropriate recommendations;
- reasons for teacher modification/rejection;
- parameter sensitivity results;
- evidence-sufficiency frequency.

This stage is separate from proof of student learning effectiveness.

## Main threats to validity

### Selection bias

Mitigation:

- pretest baseline;
- document class selection;
- comparable course conditions;
- adjusted analysis.

### Class/teacher effect

Mitigation:

- same teacher where possible;
- multiple classes per condition where possible;
- model cluster structure when supported.

### Contamination

Mitigation:

- clear treatment permissions;
- treatment logging;
- instructor protocol.

### Instrumentation

Mitigation:

- same or validated equivalent measurement design across groups;
- frozen scoring rules;
- item-objective mapping.

### Intervention inconsistency

Mitigation:

- versioned adaptive policy;
- logged exposure;
- standardized remediation/enrichment resources.

### Attrition/missingness

Mitigation:

- report participation flow and reasons when known;
- retain assignment/exposure records;
- sensitivity analysis where warranted.

## Ethical/data governance requirements

Before real-user study:

- obtain institutional/school permission as required;
- obtain participant/guardian consent/assent where required;
- use de-identified research IDs in export;
- minimize collected personal data;
- define access and retention rules;
- do not expose research-group assignment unnecessarily in the student UI;
- preserve teacher ability to intervene when educationally necessary.

## Experiment freeze gate

Before the main experiment starts, freeze and record:

- source-code commit;
- adaptive-policy version;
- objective map version;
- material version;
- assessment/pretest/posttest version;
- treatment assignment procedure;
- analysis plan;
- data dictionary;
- research export schema.

Changes after treatment begins must be logged as protocol deviations.
