#!/usr/bin/env python3
"""Phase 4 aggregation of actual engine outputs, not a simulation of engine decisions."""
import argparse
import csv
import hashlib
import json
from collections import Counter, defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
PLAN = ROOT / "research/ai_computational/config/phase4_variants.json"
DATA = ROOT / "research/ai_computational/datasets"
ARCHIVE = ROOT / "research/ai_computational/results/phase3/run-45928359249/phase2_replay"

def read(p):
    with p.open(newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f))

def sha(p):
    return hashlib.sha256(p.read_bytes()).hexdigest()

def write(p, rows):
    with p.open("w", newline="", encoding="utf-8") as f:
        w=csv.DictWriter(f, fieldnames=list(rows[0]), lineterminator="\n")
        w.writeheader();w.writerows(rows)

def key(r):
    return r["case_id"],r["variant_id"]

def stat(rows):
    c=Counter(r["status"] for r in rows)
    ok=[r for r in rows if r["status"]=="success"]
    n=len(ok)
    ac=sum(int(r["action_changed"]) for r in ok)
    tc=sum(int(r["target_changed"]) for r in ok)
    both=sum(int(r["both_unchanged"]) for r in ok)
    return dict(planned=len(rows),actual_calls=sum(int(r["engine_called"]) for r in rows),
        successful=n,failed=c["failed"],not_applicable=c["not_applicable"],
        clipped_no_change=c["clipped_no_change"],denominator=n,action_changed=ac,target_changed=tc,
        both_unchanged=both,parameter_sensitivity_rate=100*ac/n if n else None,
        decision_stability_rate=100*(n-ac)/n if n else None,
        target_stability_rate=100*(n-tc)/n if n else None,
        joint_stability_rate=100*both/n if n else None,
        fuzzy_zero_rule_count=sum(r["base_algorithm"]=="fuzzy-v2" and not r["fired_rule_ids"] for r in ok))

def main():
    p=argparse.ArgumentParser()
    p.add_argument("--input",type=Path,required=True)
    p.add_argument("--output",type=Path,required=True)
    p.add_argument("--commit",required=True)
    args=p.parse_args()
    plan=json.loads(PLAN.read_text())
    cases={r["case_id"]:r for f in ("grid","boundary","scenario","random") for r in read(DATA/f"dataset_{f}.csv")}
    assert len(cases)==1480
    archived={(r["case_id"],r["algorithm_code"]):r for r in read(ARCHIVE/"comparative_results.csv")}
    args.output.mkdir(parents=True,exist_ok=True)
    sens=[];rob=[];base_checks=[];shards={}
    for shard in plan["shards"]:
        directory=args.input/f"phase4-{shard}"
        raw=read(directory/"raw.csv")
        baseline=read(directory/"baseline_recheck.csv")
        alg="rule-based-v1" if shard.startswith("rb-") else "fuzzy-v2"
        robust=shard.endswith("robustness")
        assert len(baseline)==1480 and {r["case_id"] for r in baseline}==set(cases)
        assert all(r["matches_archive"]=="1" and r["algorithm"]==alg for r in baseline)
        for r in baseline:
            old=archived[(r["case_id"],alg)]
            assert (r["final_action"],r["target"],r["centroid"],r["selected"],r["rules"],r["reason"]) == (
                old["final_action"],old["target_concept_code"],old["centroid"],old["selected_fuzzy_output"],old["fired_rule_ids"],old["reason_code"])
            base_checks.append({"shard":shard,**r})
        variants={v["id"]:v for v in plan["variants"] if v["valid"] and v["shard"]==shard}
        if robust:
            variants={f"noise-{k}-{'plus' if d>0 else 'minus'}{abs(d)}":(k,d)
                for k,ds in plan["perturbations"].items() for d in ds}
        expected={(cid,v) for cid in cases for v in variants}
        assert len(raw)==len(expected) and {key(r) for r in raw}==expected
        for r in raw:
            c=cases[r["case_id"]];v=variants[r["variant_id"]];old=archived[(r["case_id"],alg)]
            assert r["base_algorithm"]==alg and r["dataset"]==c["dataset"] and r["seed"]==c["seed"]
            assert r["baseline_action"]==old["final_action"] and r["baseline_target"]==old["target_concept_code"] and r["baseline_centroid"]==old["centroid"]
            if robust:
                field,delta=v
                assert r["algorithm_code"]==alg and r["config_hash"]==old["config_hash"] and r["parameter"]==field and int(r["delta"])==delta
                val=c[field]
                assert r["old_value"]==val
                if not val:
                    assert r["status"]=="not_applicable" and not r["new_value"] and r["engine_called"]=="0"
                else:
                    new=min(100,max(0,float(val)+delta)) if "mastery" in field else max(0,int(val)+delta)
                    assert abs(float(r["new_value"])-new)<1e-9
                    if abs(new-float(val))<1e-9:
                        assert r["status"]=="clipped_no_change" and r["engine_called"]=="0"
                    else:
                        assert r["status"] in ("success","failed")
            else:
                assert r["algorithm_code"]==v["id"] and r["parameter"]==v["path"]
                assert float(r["old_value"])==v["baseline"] and float(r["new_value"])==v["value"]
                assert r["status"] in ("success","failed")
            if r["status"]=="success":
                assert r["engine_called"]=="1"
                assert int(r["action_changed"])==int(r["final_action"]!=r["baseline_action"])
                assert int(r["target_changed"])==int(r["target_concept_code"]!=r["baseline_target"])
                assert int(r["both_unchanged"])==int(r["action_changed"]=="0" and r["target_changed"]=="0")
        shards[shard]=stat(raw)
        (rob if robust else sens).extend(raw)
    write(args.output/"sensitivity_results.csv",sens)
    write(args.output/"robustness_results.csv",rob)
    write(args.output/"baseline_recheck.csv",base_checks)
    summaries=[]
    for mode,rows in [("sensitivity",sens),("robustness",rob)]:
        groups=defaultdict(list)
        for r in rows:
            for dataset in (r["dataset"],"ALL"):
                groups[(r["base_algorithm"],r["variant_id"],r["parameter"],r["delta"],dataset)].append(r)
        for (alg,vid,field,delta,ds),group in sorted(groups.items()):
            s=stat(group)
            if mode=="robustness":s["parameter_sensitivity_rate"]=None
            summaries.append(dict(mode=mode,algorithm=alg,variant_id=vid,parameter=field,delta=delta,dataset=ds,**s))
    write(args.output/"phase4_metrics.csv",summaries)
    by_algorithm={}
    for mode,rows in [("sensitivity",sens),("robustness",rob)]:
        by_algorithm[mode]={}
        for alg in ("rule-based-v1","fuzzy-v2"):
            subset=[r for r in rows if r["base_algorithm"]==alg]
            by_algorithm[mode][alg]=stat(subset)
            if mode=="robustness": by_algorithm[mode][alg]["parameter_sensitivity_rate"]=None
    # Report matched input perturbations separately, so unequal parameter counts cannot bias comparisons.
    perturbation_summary=[]
    for alg in ("rule-based-v1","fuzzy-v2"):
        groups=defaultdict(list)
        for r in rob:
            if r["base_algorithm"]==alg:
                groups[(r["parameter"],abs(int(r["delta"])))].append(r)
        for (field,mag),rows in sorted(groups.items()):
            s=stat(rows);s["parameter_sensitivity_rate"]=None
            perturbation_summary.append(dict(algorithm=alg,parameter=field,magnitude=mag,**s))
    write(args.output/"robustness_by_magnitude.csv",perturbation_summary)
    summary=dict(schema_version=1,experiment_commit=args.commit,source_phase3_run=45928359249,
        profiles=1480,variant_candidates=len(plan["variants"]),valid_variants=sum(v["valid"] for v in plan["variants"]),
        rejected_variants=sum(not v["valid"] for v in plan["variants"]),baseline_rechecks=len(base_checks),
        baseline_matches=sum(int(r["matches_archive"]) for r in base_checks),
        planned_sensitivity_rows=len(sens),planned_robustness_rows=len(rob),
        by_algorithm=by_algorithm,shards=shards,robustness_by_magnitude=perturbation_summary,
        plan_sha256=sha(PLAN),source_baseline_csv_sha256=sha(ARCHIVE/"comparative_results.csv"),
        sha256={f:sha(args.output/f) for f in ("sensitivity_results.csv","robustness_results.csv","baseline_recheck.csv","phase4_metrics.csv","robustness_by_magnitude.csv")},
        gate_pass=all(s["failed"]==0 for s in shards.values()),
        claim_limit="Stability and sensitivity on synthetic profiles; not accuracy or learning efficacy. Parameter variants are not calibrated or optimized.")
    (args.output/"phase4_manifest.json").write_text(json.dumps(summary,indent=2)+"\n")
    print("PHASE4_MANIFEST "+json.dumps({k:v for k,v in summary.items() if k!="robustness_by_magnitude"},separators=(",",":")),flush=True)
    return 0 if summary["gate_pass"] else 1

if __name__=="__main__":
    raise SystemExit(main())
