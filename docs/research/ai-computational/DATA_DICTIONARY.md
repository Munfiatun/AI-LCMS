# Data dictionary and fixture contract v1
All CSV data are synthetic input profiles, not real learners or experiment results. CSV ASCII, comma delimiter, LF, fixed two decimals for scores. IDs stable and unique across datasets. Blank is not zero.
| Field | Meaning |
|---|---|
| case_id | D1/D2 sequence, S01–S16 or D4 seed/index |
| dataset | D1 grid, D2 boundary, D3 scenario, D4 random |
| seed | D4 RNG seed; blank deterministic sets |
| mastery_score | Current learner mastery 0–100, persisted as observed or insufficient_evidence as appropriate |
| evidence_count | Count of positive-weight evidence; integer >=0; fuzzy fuzzification clamps at 10 |
| prereq1_state, prereq2_state | absent = no graph edge; missing = active edge but no mastery row; observed = active edge and mastery row (even 0) |
| prereq1_mastery, prereq2_mastery | Score; blank unless observed |
| prereq1_evidence, prereq2_evidence | Count; blank unless observed |
| next_context | none, ready, blocked, dual_ready, dual_blocked |
| scenario | Design stratum or case description |

## Phase 2 fixture mapping
Create one isolated course, current active concept C (order 30), P1/P2 (orders 10/20) when present, current mastery row for every D1–D4 case, prerequisite edges C->P1 then P2 with stable order. When next_context=none no subsequent active concept exists. Otherwise create N (order 40) and external prerequisite Q1 (order 1) with observed mastery 90/evidence 3 for ready/dual_ready/dual_blocked, 60/evidence 3 for blocked. For dual contexts Q2 (order 2) score 90 for dual_ready or 60 for dual_blocked, evidence 3. Attach N->Q1 (and Q2 for dual). No other later candidate; target readiness independent of C input by design. Stable IDs/keys P1,P2,C,Q1,Q2,N; create in deterministic order and compare target code. Do not attach missing prerequisites as absent. S14 intentionally uses blocked dual next; additional ready dual cases D4. D1/D2 fixed next ready; D3 otherwise ready unless S13/S14. Cases may overlap across groups and context distributions are not population frequencies. D4 pattern counts balanced per seed, scores/evidence exploratory and may not be realizable by actual assessment combinations.
D5 has separate columns reference_centroid and reference_selected_output copied from frozen oracle (not newly measured); first seven use no prerequisite graph, eighth observed P1 score 30/evidence 3. For centroid validation compare selected output, not resolved final action. Set next context ready for fixture or explicitly record alternate; centroid independent of next context.

## Required result record (Phase 2+)
case_id, dataset, seed, algorithm_code, algorithm_version, config_hash, fixture_hash, variant_id, replicate, success, error_class, error_message_sanitized, final_action, target_concept_code, reason_code, selected_fuzzy_output, centroid, fired_rule_ids, execution_ns. Nonapplicable fuzzy fields blank on Rule-Based. Keep failures; no human gold label. 
