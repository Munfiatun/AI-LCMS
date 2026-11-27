# Phase 8 — Scientific manuscript draft (review status)

27 November 2026. Draft target: Jurnal Teknik Informatika, UIN Syarif Hidayatullah Jakarta (ISSN 1979-9160 / 2549-7901; SINTA profile 3038). No submission, publication permission, author order, or acceptance is asserted. This is an independent computational evaluation of the frozen implementation, not lecturer R5/R6 expert or field validation.

## Proposed title
Computational Evaluation and Sensitivity Analysis of Rule-Based and Mamdani Fuzzy Inference for Adaptive Learning Recommendations

Indonesian: Evaluasi Komputasional dan Analisis Sensitivitas Algoritma Rule-Based dan Fuzzy Mamdani pada Sistem Rekomendasi Pembelajaran Adaptif.

## Draft abstract
Adaptive learning recommendations require computationally consistent decisions even when learner evidence is incomplete or perturbed. This study evaluates two implemented AI-LCMS recommendation policies, Rule-Based and Mamdani fuzzy inference, using paired synthetic profiles rather than human assessment labels. The design combines 1,480 paired profiles, numerical verification of 1,542 profiles, 84 valid parameter variants, matched input perturbations, metamorphic and property testing, and preliminary performance benchmarking. Action agreement was 62.64% (Cohen’s kappa = 0.4888), with the lowest agreement at decision boundaries (43.75%). All numerical reference checks matched at the recorded four-decimal precision. Across 21,259 valid perturbations per engine, decision stability was 94.89% for Rule-Based and 96.53% for fuzzy inference. Four fuzzy parameter variants produced 306 zero-rule cases; all were reproduced with diagnostic fallback. Baseline applicable properties and metamorphic relations were satisfied. On shared GitHub continuous integration infrastructure, mean engine latency was 1.697 and 2.064 ms, respectively; controlled-local timing remains outstanding. The findings characterize policy disagreement, robustness, and coverage vulnerability without establishing pedagogical accuracy or learning effectiveness. The versioned datasets, instruments and results provide a reproducible basis for subsequent validation.

Keywords: adaptive learning, Mamdani fuzzy inference, rule-based systems, sensitivity analysis, metamorphic testing, robustness.

## Research gap and contribution
Focused narrative search (not a systematic review) across publisher pages and scholarly records: adaptive learning learner model, modular rule-based recommendations, fuzzy adaptive learning paths, fuzzy rule coverage, metamorphic relations. Selected closely relevant studies, including two recent JTI papers, support a computational verification question rather than a global priority claim.

- Pelánek et al. (2024) propose a modular rule-based approach to recommending learning activities; Szczepański and Marciniak (2023) implement a fuzzy controller for content adaptation.
- Wang, Huang and Li (2025) study fuzzy competence-space learning-path construction; Wang et al. (2025) study mastery reinforcement recommendations. Zhang et al. (2024) use process mining and knowledge tracing.
- Primanita et al. (JTI 2025) evaluate adaptive hints and usability; Suendri et al. (JTI 2026) study fuzzy-neural attainment decisions with threshold and incomplete scenarios. Neither is the same evaluation object as the two AI-LCMS engines.
- Lin et al. (2024) establish the general sparse rule coverage problem; the 306 reproducible cases here are a specific empirical finding, not discovery of the generic phenomenon.
- Altamimi et al. (2023) and Ying et al. (2025) motivate relational testing without a complete oracle.

The proposed contribution is a versioned, paired engine-level evaluation integrating numerical reference verification, categorical agreement and target agreement, one-at-a-time parameter sensitivity, matched perturbations, metamorphic/property checks and reproducible negative findings. This is not a new Mamdani algorithm or a claim of universal superiority. Broader novelty and indexed-source status should be independently confirmed by authors.

## Method and evidence sources
Frozen engine baseline 8d99545be7039e97cc3778b356276efe4f5f1281. Rule-Based 1.0.0 vs Fuzzy 2.0.0-experimental. SQLite in-memory isolated fixture. Direct synthetic learner states, not response-generated samples. D1 336, D2 128, D3 16, D4 1000 (five seeds); D5 8 and B3 54 added for numerical verification only. Rule-Based evidence >=2, mastery <=39.99 and <=79.99, prerequisite mastery >=70. Fuzzy min/max Mamdani, 15 rules, discrete centroid 0–100, evidence saturates at 10. Missing prerequisite mastery maps to zero; no prerequisite maps to 100; weakest rather than first failed prerequisite. Selected label is not necessarily resolved action. Policies differ beyond fuzzification. Full specification is in config/adaptive.php, the engine classes and frozen protocol.
 
Source runs: 45927771715 (original Phase 2); 45928359249 (Phase 3 and Phase 2 replay); 45933537326 (Phase 4); 45935485184 (Phase 5); 45955417194 (preliminary Phase 6); 45961495618 (Phase 7). Phase 7 archived analysis commit 1e50f92747c731c23efd7ccb81554fac2771ef20. Analysis root research/ai_computational/results/phase7/run-45961495618/. Use archived Phase 2 replay rows rather than pool the original and replay. Source and output hashes are in summary_metrics.json. No independent learner labels.
 
## Results and discussion ledger
| Finding | Actual values | Evidence |
|---|---|---|
| Paired agreement | 927/1480 (62.6351%), divergence 553/1480, kappa 0.4888089, target 1085/1480, joint 778/1480 | agreement.csv |
| Dataset agreement | D1 76.7857%, D2 43.75%, D3 50%, D4 60.5% | agreement.csv |
| Largest off-diagonal cell | AD (Rule-Based) to PR (fuzzy), 260 | decision_matrix_6x6.csv |
| Marginal actions AD/PR/RM/RF/NC/EN | RB 491/585/147/164/67/26; fuzzy 216/698/285/179/26/76 | recommendation_distribution.csv |
| D4 single missing prerequisite | 23/200 action agreement; 200/200 target agreement | d4_context_agreement.csv |
| Numerical specification | 1542/1542, recorded absolute centroid error zero, all 15 rule IDs activated | numerical_verification.csv, reference_cases.csv, fuzzy_rule_coverage.csv |
| Parameter sensitivity | 15 RB vs 69 fuzzy valid variants; 1505/22200 and 1282/102120 changed actions (6.78%,1.26%); 15 invalid candidates excluded | sensitivity_robustness_summary.csv, parameter_and_perturbation_metrics.csv |
| Robustness | 21259 valid perturbations/engine; action changes 1087 vs 737; DSR 94.89% vs 96.53%; target stability 96.00% vs 98.16%; joint 94.60% vs 96.27% | sensitivity_robustness_summary.csv |
| Matched action change | Neither 19715; RB only 807; fuzzy only 457; both 280 | matched_robustness.csv |
| Mastery noise ±1, ±3, ±5 | RB 96.70,96.10,95.33%; fuzzy 99.40,98.52,94.63% | robustness_by_magnitude.csv |
| Negative outcomes | 120+89+61+36=306 zero-rule cases, all reproduced and diagnostic fallback at C | phase4_zero_rule_cases.csv, phase5_negative_results.csv, zero_rule_reproduction.csv |
| Metamorphic/properties | 10702 calls, 30766 checks: 18841 pass,306 fail,11619 N/A; baseline MR 8883/8883; baseline properties 9346/9346; stress MR306/306; stress coverage 0/306; stress fallback306/306 | metamorphic_property_metrics.csv |
| Preliminary latency | 10000 measured calls/engine; mean 1.697189 vs 2.064416 ms; median 1.725166 vs 2.064270; P95 1.972862 vs 2.365811 ms; throughput 589.2095 vs 484.3985 calls/engine-second | benchmark_preliminary_summary.csv, benchmark_preliminary_batches.csv |

The four fuzzy variants are p4-fz-evidence-082 (sufficient left 1→2,120), p4-fz-evidence-089 (sufficient right 5→4,89), p4-fz-prerequisite-060 (partial right 80→70,61), p4-fz-prerequisite-067 (mastered left 70→80,36). Preserve all negatives. Stress replay selected on outcome, not incidence. PSR differs in variant sets, so no superiority claim from pooled PSR. Matched robustness is descriptive, no independent-sample p-values. Higher stability is not inherently educationally better.
 
## Manuscript figures and structure
The two review DOCX drafts have bilingual abstracts, six keywords each, Introduction (focused literature and gap), Methods (dataset, fixture, policies, grouped 15 rules, numerical and experimental measures), Results and Discussion, Limitations, Conclusion, Declarations, 18 IEEE-style references. Ten editable tables and four original Phase 7 PNGs: fig01_decision_matrix, fig05_robustness, fig06_negative_reproduction, fig07_preliminary_latency. The remaining four publication plots including sensitivity and action distribution are already preserved in the Phase 7 supplement. Use figure IDs from Phase 7 for provenance (renumbered 1–4 in manuscript). No confusion matrix against ground truth. No generated illustration or artificial results. 

## Delivery record
DOCX drafts delivered through the chat, not uploaded as binary into repository. English primary draft (12 rendered pages), Indonesian companion (13 rendered pages; not for submission). Both use uploaded JTI template-derived A4 2.5-cm margins, Times New Roman 11 pt, title 16 pt, two-column body and full-width figures. English and Indonesian abstracts are 176 and 170 words. Official online author guide and supplied template differ in some layout details; supplied template controls title and layout, with both abstracts included as online guide requests.
- AI_LCMS_JTI_Draft_EN.docx SHA-256 28055057e1fb170bb0a231b57ccfbea142c87bdb642d3f2118f36cca67d1131a
- AI_LCMS_JTI_Draft_ID.docx SHA-256 9cf7fb3329b18dca0eb959a2b67efeb107e38f0b71da83aad103fa3dc35a707c

## Status: DRAFT COMPLETE, NOT SUBMISSION READY
Controlled-local benchmark remains absent; preliminary shared-CI timing cannot be presented as final controlled performance. Confirm funding, conflicts, ethics requirements, author list/order/CRediT roles, affiliation/correspondence, original/unpublished status, manuscript and data/code rights with supervisor and contributors. Confirm reference metadata/full text and indexing, use Word/Mendeley or EndNote reference fields as required by journal; final similarity check not performed. No peer review or SINTA acceptance guarantee. Generative AI use disclosed (OpenAI ChatGPT GPT-6.2: research coding/debugging, analysis support, literature search, writing, translation, formatting); human authors must verify all scientific claims, code, literature, and final manuscript. No human subjects or private learner records; ethics exemption status must be confirmed. Data availability subject to authorization. Do not merge or change main, R5, R6 or production.
