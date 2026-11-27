#!/usr/bin/env python3
"""Phase 3: frozen specification checks; no pedagogical labels or fabricated observations."""
import argparse
import csv
import hashlib
import json
import sys
from collections import Counter, defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
DATA = ROOT / "research/ai_computational/datasets"
PRIOR = ROOT / "research/ai_computational/manifests/phase2_verified_manifest.json"
sys.path.insert(0, str(ROOT / "scripts/research"))
from fuzzy_reference_oracle import evaluate

FIELDS = ["case_id","dataset","seed","mastery_score","evidence_count",
          "prereq1_state","prereq1_mastery","prereq1_evidence",
          "prereq2_state","prereq2_mastery","prereq2_evidence","next_context","scenario"]
ALGS = ("rule-based-v1","fuzzy-v2")
TOL = 0.00015

def read(path):
    with path.open(newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f))

def write(path, fields, rows):
    with path.open("w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=fields, lineterminator="\n")
        w.writeheader()
        w.writerows(rows)

def sha(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()

def profile(cid, group, m, e, p, pe=3, note=""):
    return dict(zip(FIELDS, [cid,group,"",f"{m:.2f}",str(e),
        "absent" if p is None else "observed","" if p is None else f"{p:.2f}",
        "" if p is None else str(pe),"absent","","","ready",note]))

def generate(out):
    rows = []
    for r in read(DATA / "dataset_reference.csv"):
        p = None if r["case_id"] != "N08" else float(r["prerequisite_mastery"])
        rows.append(profile(r["case_id"],"D5",float(r["mastery_score"]),int(r["evidence_count"]),p,
                            note="fixed_centroid_reference"))
    def add(m,e,p,pe,note):
        rows.append(profile(f"B3-{len(rows)-7:03d}","B3",m,e,p,pe,note))
    # Isolated one-factor boundaries; other prerequisites are satisfied or absent.
    for m in (39.98,39.99,40,40.01,79.98,79.99,80,80.01):
        add(m,3,None,3,"rule_mastery_threshold")
    for knot in (30,50,60,80):
        for delta in (-.01,0,.01):
            add(knot+delta,3,None,3,"fuzzy_mastery_knot")
    for p in (69.98,69.99,70,70.01):
        add(90,3,p,3,"rule_prerequisite_threshold")
    for knot in (40,60,65,70,80,85):
        for delta in (-.01,0,.01):
            add(90,3,knot+delta,3,"fuzzy_prerequisite_knot")
    for e in (0,1,2,3,4,5,9,10,11):
        add(90,e,100,3,"current_evidence_boundary")
    for pe in (1,2,3):
        add(90,3,100,pe,"prerequisite_evidence_boundary")
    assert len(rows)==62 and len({r["case_id"] for r in rows})==62
    out.mkdir(parents=True,exist_ok=True)
    write(out/"additional_inputs.csv",FIELDS,rows)

def prereqs(r):
    return [(f"P{i}", r[f"prereq{i}_state"],
             float(r[f"prereq{i}_mastery"] or 0),int(r[f"prereq{i}_evidence"] or 0))
             for i in (1,2) if r[f"prereq{i}_state"]!="absent"]

def ready(r):
    return r["next_context"] in ("ready","dual_ready")

def rule_expected(r):
    if int(r["evidence_count"])<2:
        return "additional_diagnostic","C","current_evidence_insufficient"
    for target,state,m,e in prereqs(r):
        if state=="missing" or e<2:
            return "additional_diagnostic",target,"prerequisite_evidence_insufficient"
        if m<70:
            return "prerequisite_remediation",target,"prerequisite_below_threshold"
    m=float(r["mastery_score"])
    if m<=39.99:
        return "remediation","C","mastery_low"
    if m<=79.99:
        return "reinforcement","C","mastery_medium"
    if ready(r):
        return "next_concept","N","mastery_high_next_ready"
    return "enrichment","C","mastery_high_no_next_ready"

def fuzzy_expected(r):
    ps=prereqs(r)
    weakest=min(ps,key=lambda p:(p[2],p[0])) if ps else None
    crisp,selected,fired=evaluate(float(r["mastery_score"]),int(r["evidence_count"]),weakest[2] if weakest else 100)
    action,target=selected,"C"
    if selected=="prerequisite_remediation" and weakest:
        target=weakest[0]
    if selected=="next_concept":
        action,target=("next_concept","N") if ready(r) else ("enrichment","C")
    return crisp,selected,fired,action,target

def verify(out, phase2, commit):
    prior=json.loads(PRIOR.read_text())["manifest"]
    replay=json.loads((phase2/"run_manifest.json").read_text())
    keys=("baseline_commit","profiles","recorded_rows","actual_engine_calls","success","failures",
          "complete_pairs","same_action","same_target","same_both","by_dataset",
          "input_sha256","algorithm_config_sha256","adaptive_file_sha256","engine_sha256")
    replay_match=all(prior[k]==replay[k] for k in keys)
    replay_hash_ok=sha(phase2/"comparative_results.csv")==replay["results_sha256"]
    profiles=[r for n in ("grid","boundary","scenario","random") for r in read(DATA/f"dataset_{n}.csv")]
    profiles+=read(out/"additional_inputs.csv")
    observed=read(phase2/"comparative_results.csv")+read(out/"additional_actual.csv")
    actual={}
    for r in observed:
        key=(r["case_id"],r["algorithm_code"])
        if key in actual:
            raise ValueError(f"Duplicate actual key: {key}")
        actual[key]=r
    expected_keys={(r["case_id"],a) for r in profiles for a in ALGS}
    if set(actual)!=expected_keys:
        raise ValueError("Missing or unexpected engine observation keys")
    reference={r["case_id"]:r for r in read(DATA/"dataset_reference.csv")}
    numerical=[]; boundary=[]; counts=defaultdict(Counter); errors=[]; active=set()
    for r in profiles:
        cid,group=r["case_id"],r["dataset"]
        stats=counts[group]
        stats["profiles"]+=1
        rb=actual[cid,ALGS[0]]; fz=actual[cid,ALGS[1]]
        ra,rt,rr=rule_expected(r)
        rp=rb["success"]=="1" and (rb["final_action"],rb["target_concept_code"],rb["reason_code"])==(ra,rt,rr)
        crisp,selected,fired,fa,ft=fuzzy_expected(r)
        if cid in reference:
            fixed=reference[cid]
            if abs(crisp-float(fixed["reference_centroid"]))>TOL or selected!=fixed["reference_selected_output"]:
                raise ValueError("Frozen oracle disagrees with tabulated reference: "+cid)
        ids=fz["fired_rule_ids"].split("|") if fz["fired_rule_ids"] else []
        active.update(ids)
        error=abs(float(fz["centroid"])-crisp) if fz["centroid"] else None
        numeric_ok=fz["success"]=="1" and error is not None and error<=TOL+1e-12
        selected_ok=fz["success"]=="1" and fz["selected_fuzzy_output"]==selected
        fired_ok=fz["success"]=="1" and len(ids)==fired and len(set(ids))==len(ids) and all(x.isdigit() and 1<=int(x)<=15 for x in ids)
        coverage=fz["success"]=="1" and bool(ids)
        final_ok=fz["success"]=="1" and (fz["final_action"],fz["target_concept_code"])==(fa,ft)
        stats["rule_spec_pass"]+=int(rp)
        stats["centroid_pass"]+=int(numeric_ok)
        stats["selected_pass"]+=int(selected_ok)
        stats["fired_count_pass"]+=int(fired_ok)
        stats["coverage_nonzero"]+=int(coverage)
        stats["fuzzy_target_action_pass"]+=int(final_ok)
        stats["engine_calls_success"]+=int(rb["success"]=="1")+int(fz["success"]=="1")
        if error is not None:
            errors.append(error)
        numerical.append(dict(case_id=cid,dataset=group,reference_centroid=f"{crisp:.4f}",
            actual_centroid=fz["centroid"],absolute_error="" if error is None else f"{error:.8f}",
            reference_selected=selected,actual_selected=fz["selected_fuzzy_output"],
            reference_fired_count=fired,actual_fired_count=len(ids),actual_fired_rule_ids=fz["fired_rule_ids"],
            centroid_pass=int(numeric_ok),selected_pass=int(selected_ok),fired_count_pass=int(fired_ok),
            coverage_nonzero=int(coverage),engine_success=fz["success"]))
        boundary.append(dict(case_id=cid,dataset=group,scenario=r["scenario"],
            mastery_score=r["mastery_score"],evidence_count=r["evidence_count"],
            prereq1_state=r["prereq1_state"],prereq1_mastery=r["prereq1_mastery"],
            prereq1_evidence=r["prereq1_evidence"],next_context=r["next_context"],
            rule_expected_action=ra,rule_actual_action=rb["final_action"],
            rule_expected_target=rt,rule_actual_target=rb["target_concept_code"],
            rule_expected_reason=rr,rule_actual_reason=rb["reason_code"],rule_spec_pass=int(rp),
            fuzzy_expected_action=fa,fuzzy_actual_action=fz["final_action"],
            fuzzy_expected_target=ft,fuzzy_actual_target=fz["target_concept_code"],
            fuzzy_target_action_pass=int(final_ok)))
    write(out/"numerical_results.csv",list(numerical[0]),numerical)
    write(out/"boundary_results.csv",list(boundary[0]),boundary)
    totals=Counter()
    for c in counts.values():
        totals.update(c)
    n=len(profiles)
    gate=(replay_match and replay_hash_ok and totals["engine_calls_success"]==2*n
          and all(totals[k]==n for k in ("rule_spec_pass","centroid_pass","selected_pass",
          "fired_count_pass","coverage_nonzero","fuzzy_target_action_pass")))
    summary=dict(schema_version=1,experiment_commit=commit,
        prior_phase2_run=45927771715,prior_phase2_artifact=15167511175,
        prior_phase2_aggregate_reproduced=replay_match,replay_csv_hash_valid=replay_hash_ok,
        profiles=n,phase2_replayed_profiles=1480,additional_profiles=62,reference_cases=8,
        isolated_boundary_cases=54,centroid_tolerance=TOL,mean_absolute_centroid_error=sum(errors)/len(errors) if errors else None,
        max_absolute_centroid_error=max(errors) if errors else None,centroid_error_n=len(errors),
        distinct_fired_rule_ids=sorted(active,key=int),totals=dict(totals),by_dataset={k:dict(v) for k,v in counts.items()},
        gate_pass=gate,human_participants=0,claim_limit="Specification and numerical consistency, not pedagogical validity or accuracy.",
        sha256={name:sha(out/name) for name in ("additional_inputs.csv","additional_actual.csv","numerical_results.csv","boundary_results.csv")})
    (out/"phase3_manifest.json").write_text(json.dumps(summary,indent=2)+"\n")
    print("PHASE3_MANIFEST "+json.dumps(summary,separators=(",",":")),flush=True)
    return 0 if gate else 1

def main():
    parser=argparse.ArgumentParser()
    parser.add_argument("mode",choices=("generate","verify"))
    parser.add_argument("--output",type=Path,required=True)
    parser.add_argument("--phase2",type=Path)
    parser.add_argument("--commit")
    args=parser.parse_args()
    if args.mode=="generate":
        generate(args.output)
        return 0
    if not args.phase2 or not args.commit:
        parser.error("verify requires --phase2 and --commit")
    return verify(args.output,args.phase2,args.commit)

if __name__=="__main__":
    sys.exit(main())
