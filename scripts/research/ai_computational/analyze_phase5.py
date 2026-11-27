#!/usr/bin/env python3
"""Aggregate actual Phase 5 observations and keep failed properties visible."""
import argparse
import csv
import hashlib
import json
from collections import Counter, defaultdict
from pathlib import Path

def sha(p):
    return hashlib.sha256(p.read_bytes()).hexdigest()

def read(p):
    with p.open(newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f))

def main():
    ap=argparse.ArgumentParser()
    ap.add_argument("--output",type=Path,required=True)
    ap.add_argument("--commit",required=True)
    args=ap.parse_args()
    out=args.output
    raw=read(out/"engine_observations.csv")
    checks=read(out/"metamorphic_results.csv")
    assert len(raw)==10702
    assert len({(r["stage"],r["case_id"],r["base_algorithm"],r["variant_id"]) for r in raw})==len(raw)
    assert len({(r["scope"],r["suite"],r["case_id"],r["algorithm"],r["check_id"]) for r in checks})==len(checks)
    assert Counter(r["stage"] for r in raw)=={
        "base":2960,"repeat":2960,"missing_current":32,"saturation_10":1480,
        "saturation_11":1480,"saturation_20":1480,"fallback_ready":1,"fallback_none":1,
        "fallback_blocked":1,"fallback_dual_blocked":1,"stress_replay":306}
    expected_checks=2960*9+32*7+1480*2+3*8+306*3
    assert len(checks)==expected_checks,(len(checks),expected_checks)
    for r in raw:
        assert r["status"] in ("success","error")
        if r["status"]=="success":
            assert r["engine_called"]=="1"
            s=json.loads(r["observation_json"])
            assert s["algorithm_code"]==r["algorithm_code"] and s["algorithm_version"]==r["algorithm_version"]
    # Audit relational equality and core property statuses against independent CSV snapshots.
    index={(r["stage"],r["case_id"],r["base_algorithm"],r["variant_id"]):json.loads(r["observation_json"]) for r in raw}
    for r in checks:
        assert r["status"] in ("pass","fail","not_applicable","error")
        a=r["algorithm"];cid=r["case_id"];prop=r["check_id"]
        if r["status"]=="error": continue
        if prop=="MR01_repeat_determinism":
            x=index["base",cid,a,"baseline"];y=index["repeat",cid,a,"baseline"]
            assert r["status"]==("pass" if x==y else "fail")
        elif prop.startswith("MR02_evidence_10_vs_"):
            x=index["saturation_10",cid,a,"baseline"]
            y=index["saturation_"+prop.rsplit("_",1)[-1],cid,a,"baseline"]
            assert r["status"]==("pass" if x==y else "fail")
        elif prop=="P07_nonzero_rule_coverage" and a=="fuzzy-v2":
            if r["scope"]=="stress":
                c,v=cid.split("/")
                s=index["stress_replay",c,a,v]
            else:
                stage=r["suite"] if r["suite"]!="fallback" else "fallback_"+cid.removeprefix("FALLBACK-").lower()
                s=index[stage,cid,a,"baseline"]
            assert r["status"]==("pass" if s["rules"] else "fail")
    groups=defaultdict(Counter)
    for r in checks:
        kind="metamorphic" if r["check_id"].startswith("MR") else "property"
        groups[(r["scope"],r["suite"],r["algorithm"],kind,r["check_id"])][r["status"]]+=1
    metrics=[]
    for (scope,suite,alg,kind,check),c in sorted(groups.items()):
        n=c["pass"]+c["fail"]
        metrics.append(dict(scope=scope,suite=suite,algorithm=alg,kind=kind,check_id=check,
            planned=sum(c.values()),passed=c["pass"],failed=c["fail"],not_applicable=c["not_applicable"],
            errors=c["error"],denominator=n,satisfaction_rate=100*c["pass"]/n if n else ""))
    with (out/"phase5_metrics.csv").open("w",newline="",encoding="utf-8") as f:
        w=csv.DictWriter(f,fieldnames=list(metrics[0]),lineterminator="\n");w.writeheader();w.writerows(metrics)
    with (out/"negative_results.csv").open("w",newline="",encoding="utf-8") as f:
        w=csv.DictWriter(f,fieldnames=list(checks[0]),lineterminator="\n");w.writeheader()
        w.writerows(r for r in checks if r["status"] in ("fail","error"))
    scopes={}
    for scope in ("baseline","stress"):
        scopes[scope]={}
        for kind in ("metamorphic","property"):
            c=Counter(r["status"] for r in checks if r["scope"]==scope and
                      ("metamorphic" if r["check_id"].startswith("MR") else "property")==kind)
            n=c["pass"]+c["fail"]
            scopes[scope][kind]=dict(passed=c["pass"],failed=c["fail"],not_applicable=c["not_applicable"],
                                     errors=c["error"],denominator=n,satisfaction_rate=100*c["pass"]/n if n else None)
    summary=dict(schema_version=1,experiment_commit=args.commit,source_phase3_run=45928359249,source_phase4_run=45933537326,
        profiles=1480,engine_observation_rows=len(raw),engine_calls=sum(int(r["engine_called"]) for r in raw),
        engine_errors=sum(r["status"]=="error" for r in raw),check_rows=len(checks),scopes=scopes,
        by_check=metrics,negative_rows=sum(r["status"] in ("fail","error") for r in checks),
        generator_reproducibility_verified=(out/"generator_check.txt").exists(),
        sha256={n:sha(out/n) for n in ("engine_observations.csv","metamorphic_results.csv","negative_results.csv","phase5_metrics.csv","generator_check.txt")},
        execution_gate_pass=all(r["status"]=="success" for r in raw) and all(r["status"]!="error" for r in checks),
        claim_limit="Property conformance and metamorphic consistency; no pedagogical ground truth. Stress cases selected for prior zero-rule observation.")
    (out/"phase5_manifest.json").write_text(json.dumps(summary,indent=2)+"\n")
    print("PHASE5_MANIFEST "+json.dumps({k:v for k,v in summary.items() if k!="by_check"},separators=(",",":")),flush=True)
    for row in metrics:
        if row["failed"] or row["errors"] or row["kind"]=="metamorphic":
            print("PHASE5_CHECK "+json.dumps(row,separators=(",",":")))
    return 0 if summary["execution_gate_pass"] else 1

if __name__=="__main__":
    raise SystemExit(main())
