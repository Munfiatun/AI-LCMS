#!/usr/bin/env python3
"""Sequential, alternating-order actual PHP benchmark; no fabricated decisions."""
import argparse
import csv
import hashlib
import json
import math
import os
import platform
import statistics as st
import subprocess
import sys
import time
from collections import Counter
from pathlib import Path

ROOT=Path(__file__).resolve().parents[3]
ALGS=("rule-based-v1","fuzzy-v2")
TEST="tests/Research/AIComputational/PerformanceBenchmarkTest.php"
BASE="8d99545be7039e97cc3778b356276efe4f5f1281"
PRIOR="4b7f964e194ac5939e8ab9a6e531ffec186539ac"
ARCH=ROOT/"research/ai_computational/results/phase3/run-45928359249/phase2_replay"

def sha(p): return hashlib.sha256(p.read_bytes()).hexdigest()
def git(*args): return subprocess.check_output(["git",*args],cwd=ROOT,text=True).strip()
def read(p):
    with p.open(newline="",encoding="utf-8") as f: return list(csv.DictReader(f))
def save(p,obj): p.write_text(json.dumps(obj,indent=2)+"\n",encoding="utf-8")
def quantile(xs,p):
    a=sorted(xs);j=(len(a)-1)*p;i=math.floor(j)
    return a[i]+(j-i)*(a[min(i+1,len(a)-1)]-a[i])
def metrics(xs):
    return dict(mean_ms=st.mean(xs)/1e6,median_ms=st.median(xs)/1e6,
        sample_sd_ms=st.stdev(xs)/1e6 if len(xs)>1 else None,
        q1_ms=quantile(xs,.25)/1e6,q3_ms=quantile(xs,.75)/1e6,
        iqr_ms=(quantile(xs,.75)-quantile(xs,.25))/1e6,p95_ms=quantile(xs,.95)/1e6,
        min_ms=min(xs)/1e6,max_ms=max(xs)/1e6,successful_calls_per_engine_second=len(xs)*1e9/sum(xs))
def write(p,rows):
    with p.open("w",newline="",encoding="utf-8") as f:
        w=csv.DictWriter(f,fieldnames=list(rows[0]),lineterminator="\n");w.writeheader();w.writerows(rows)
def cpu():
    try:
        if platform.system()=="Darwin":
            return subprocess.check_output(["sysctl","-n","machdep.cpu.brand_string"],text=True).strip()
        for line in Path("/proc/cpuinfo").read_text().splitlines():
            if line.startswith("model name"): return line.split(":",1)[1].strip()
    except (OSError,subprocess.CalledProcessError): pass
    return platform.processor() or "unknown"
def main():
    p=argparse.ArgumentParser()
    p.add_argument("--output",type=Path,required=True)
    p.add_argument("--environment",choices=["shared-ci","controlled-local"],required=True)
    a=p.parse_args()
    out=a.output.resolve()
    if out==ROOT or ROOT in out.parents: raise SystemExit("Output must be outside repository.")
    if out.exists() and any(out.iterdir()): raise SystemExit("Fresh output required")
    out.mkdir(parents=True,exist_ok=True)
    if git("status","--porcelain"): raise SystemExit("Require clean checkout for reproducibility")
    commit=git("rev-parse","HEAD")
    for base,paths in [(BASE,["app","config","database","routes","resources","bootstrap","composer.json","composer.lock","phpunit.xml","tests/Feature","tests/Unit","tests/TestCase.php"]),
                       (PRIOR,["research/ai_computational/datasets","research/ai_computational/results","tests/Research/AIComputational/Support"])]:
        subprocess.run(["git","diff","--exit-code",base,"HEAD","--",*paths],cwd=ROOT,check=True)
    if os.environ.get("GITHUB_ACTIONS")=="true" and a.environment!="shared-ci":
        raise SystemExit("Shared runner cannot be labeled controlled-local.")
    source=json.loads((ARCH/"run_manifest.json").read_text())
    assert sha(ARCH/"comparative_results.csv")==source["results_sha256"]
    profiles=read(ROOT/"research/ai_computational/datasets/dataset_random.csv")
    ids=[r["case_id"] for r in profiles];assert len(ids)==len(set(ids))==1000
    old={(r["case_id"],r["algorithm_code"]):r for r in read(ARCH/"comparative_results.csv") if r["dataset"]=="D4"}
    env=os.environ.copy()
    env.update(APP_ENV="testing",DB_CONNECTION="sqlite",DB_DATABASE=":memory:",DB_URL="",AI_PROVIDER="mock")
    env.pop("APP_CONFIG_CACHE",None)
    meta=dict(schema_version=1,commit=commit,baseline_commit=BASE,source_phase3_run=45928359249,
        source_phase5_run=45935485184,environment=a.environment,os=platform.platform(),architecture=platform.machine(),
        cpu_model=cpu(),logical_cpus=os.cpu_count(),python_version=platform.python_version(),github_run_id=os.environ.get("GITHUB_RUN_ID"),
        started_utc=time.strftime("%Y-%m-%dT%H:%M:%SZ",time.gmtime()),dataset_sha256=source["input_sha256"]["dataset_random.csv"],
        source_archive_sha256=source["results_sha256"],process_policy="one fresh PHPUnit process per algorithm/batch; sequential; alternating first algorithm continuously",
        timing_scope="decide() only including registry and DB queries; excludes fixture, rollback, app resolution and CSV",warmup_batches_per_algorithm=3,
        measured_batches_per_algorithm=10,prewarm_cases_per_process=20,profiles_per_batch=1000,
        timing_claim_limit="Shared CI is preliminary only; local controlled run requires documented stable hardware/load. PHP allocator peak is not RSS.")
    save(out/"environment.json",meta)
    raw=[];batch_meta=[];failures=[]
    for i in range(1,14):
        stage="warmup" if i<=3 else "measured"
        batch=i if i<=3 else i-3
        order=ALGS if i%2 else ALGS[::-1]
        for pos,alg in enumerate(order,1):
            d=out/f"{stage}-{batch:02d}-{alg}";d.mkdir()
            ee=env.copy();ee.update(AI_PHASE6_BATCH_OUTPUT=str(d),AI_PHASE6_ALGORITHM=alg,AI_PHASE6_STAGE=stage,
                AI_PHASE6_BATCH=str(batch),AI_PHASE6_POSITION=str(pos))
            with (d/"phpunit.log").open("w",encoding="utf-8") as log:
                result=subprocess.run(["php","vendor/bin/phpunit","-c","phpunit.ai-computational.xml",TEST],
                    cwd=ROOT,env=ee,stdout=log,stderr=subprocess.STDOUT)
            if result.returncode!=0: failures.append(dict(stage=stage,batch=batch,algorithm=alg,exit_code=result.returncode))
            if not (d/"batch_manifest.json").exists() or not (d/"observations.csv").exists():
                failures.append(dict(stage=stage,batch=batch,algorithm=alg,error="missing batch evidence"))
                continue
            bm=json.loads((d/"batch_manifest.json").read_text());rows=read(d/"observations.csv")
            assert bm["csv_sha256"]==sha(d/"observations.csv")
            assert (bm["stage"],bm["batch"],bm["position"],bm["algorithm"])==(stage,batch,pos,alg)
            assert len(rows)==1020 and [r["case_id"] for r in rows]==ids[:20]+ids
            assert [r["call_kind"] for r in rows]==["prewarm"]*20+["batch"]*1000
            for r in rows:
                assert (r["stage"],int(r["batch"]),int(r["position"]),r["algorithm_code"])==(stage,batch,pos,alg)
                if r["status"]=="success":
                    z=old[(r["case_id"],alg)]
                    assert r["engine_called"]=="1" and int(r["execution_ns"])>0
                    match=all(r[k]==z[k] for k in ("final_action","target_concept_code","reason_code","selected_fuzzy_output","centroid","fired_rule_ids"))
                    assert int(r["archive_match"])==int(match)
                    assert r["config_hash"]==z["config_hash"] and r["fixture_hash"]==z["fixture_hash"]
            raw.extend(rows);batch_meta.append(bm)
            print("PHASE6_PROGRESS",stage,batch,alg,bm["counts"],flush=True)
    if not raw: raise SystemExit("No raw results; inspect preserved batch logs")
    write(out/"benchmark_results.csv",raw)
    save(out/"batch_manifests.json",batch_meta)
    stats=[];summary={}
    for bm in batch_meta:
        rr=[r for r in raw if r["stage"]==bm["stage"] and int(r["batch"])==bm["batch"] and r["algorithm_code"]==bm["algorithm"] and r["call_kind"]=="batch"]
        xs=[int(r["execution_ns"]) for r in rr if r["status"]=="success"]
        stats.append(dict(stage=bm["stage"],batch=bm["batch"],position=bm["position"],algorithm=bm["algorithm"],
            planned=1000,success=len(xs),errors=len(rr)-len(xs),archive_mismatches=sum(r["archive_match"]!="1" for r in rr if r["status"]=="success"),
            **(metrics(xs) if xs else {k:"" for k in metrics([1,2])}),
            peak_php_allocated_bytes=bm["peak_php_allocated_bytes"],peak_php_used_bytes=bm["peak_php_used_bytes"],loop_wall_ns=bm["loop_wall_ns"]))
    write(out/"benchmark_batch_metrics.csv",stats)
    for alg in ALGS:
        rows=[r for r in raw if r["stage"]=="measured" and r["call_kind"]=="batch" and r["algorithm_code"]==alg]
        xs=[int(r["execution_ns"]) for r in rows if r["status"]=="success"]
        bs=[b for b in stats if b["algorithm"]==alg and b["stage"]=="measured" and b["success"]>0]
        summary[alg]=dict(planned=10000,recorded=len(rows),success=len(xs),errors=len(rows)-len(xs),
            archive_mismatches=sum(r["archive_match"]!="1" for r in rows if r["status"]=="success"),
            **(metrics(xs) if xs else {}),measured_batches=len(bs),
            median_of_batch_medians_ms=st.median(b["median_ms"] for b in bs) if bs else None,
            max_peak_php_allocated_bytes=max((b["peak_php_allocated_bytes"] for b in bs),default=None),
            max_peak_php_used_bytes=max((b["peak_php_used_bytes"] for b in bs),default=None))
    gate=len(raw)==26520 and len(batch_meta)==26 and not failures and all(r["status"]=="success" and r["archive_match"]=="1" for r in raw)
    final=dict(**meta,raw_rows=len(raw),engine_calls=sum(int(r["engine_called"]) for r in raw),
        errors=sum(r["status"]!="success" for r in raw),archive_mismatches=sum(r["status"]=="success" and r["archive_match"]!="1" for r in raw),
        measured_rows=sum(r["stage"]=="measured" and r["call_kind"]=="batch" for r in raw),
        warmup_rows=sum(r["stage"]=="warmup" and r["call_kind"]=="batch" for r in raw),
        prewarm_rows=sum(r["call_kind"]=="prewarm" for r in raw),by_algorithm=summary,process_failures=failures,
        sha256={f:sha(out/f) for f in ("benchmark_results.csv","benchmark_batch_metrics.csv","batch_manifests.json","environment.json")},
        execution_gate_pass=gate)
    save(out/"phase6_manifest.json",final)
    print("PHASE6_MANIFEST "+json.dumps(final,separators=(",",":")),flush=True)
    return 0 if gate else 1
if __name__=="__main__": raise SystemExit(main())
