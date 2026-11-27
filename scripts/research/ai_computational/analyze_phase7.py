#!/usr/bin/env python3
"""Phase 7: descriptive analysis of frozen actual experiment archives."""
import argparse
import csv
import hashlib
import json
import math
import platform
import statistics as st
import subprocess
from collections import Counter, defaultdict
from pathlib import Path

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import numpy as np

ROOT=Path(__file__).resolve().parents[3]
BASE="c1aafa0de5fb445224b1074d9906a87c4ecfd53c"
R=ROOT/"research/ai_computational/results"
P2=R/"phase3/run-45928359249/phase2_replay"
P3=R/"phase3/run-45928359249/phase3"
P4=R/"phase4/run-45933537326"
P5=R/"phase5/run-45935485184"
P6=R/"phase6/run-45955417194"
A=("rule-based-v1","fuzzy-v2")
LABEL=("Rule-Based","Fuzzy Mamdani")
ACT=("additional_diagnostic","prerequisite_remediation","remediation","reinforcement","next_concept","enrichment")
SHORT=("Diagnostic","Prerequisite\nremediation","Remediation","Reinforcement","Next concept","Enrichment")
COLORS=("#4C6178","#16857B")
plt.rcParams.update({"font.family":"DejaVu Sans","font.size":9,"axes.spines.top":False,"axes.spines.right":False,
    "figure.dpi":120,"savefig.dpi":300,"svg.fonttype":"none","axes.labelsize":9,"axes.titlesize":10,"legend.fontsize":8})

def sha(p): return hashlib.sha256(p.read_bytes()).hexdigest()
def read(p):
    with p.open(newline="",encoding="utf-8") as f:return list(csv.DictReader(f))
def load(p):return json.loads(p.read_text())
def save(p,x):p.write_text(json.dumps(x,indent=2,allow_nan=False)+"\n",encoding="utf-8")
def write(p,rows):
    assert rows
    with p.open("w",newline="",encoding="utf-8") as f:
        w=csv.DictWriter(f,fieldnames=list(rows[0]),lineterminator="\n");w.writeheader();w.writerows(rows)
def check(x,y):
    if isinstance(y,(float,int)) and not isinstance(y,bool):
        assert math.isclose(float(x),float(y),rel_tol=1e-10,abs_tol=1e-9),(x,y)
    else:assert x==y,(x,y)
def pct(n,d):return 100*n/d if d else None
def figsave(fig,out,name):
    fig.savefig(out/(name+".png"),bbox_inches="tight",dpi=300,facecolor="white")
    fig.savefig(out/(name+".svg"),bbox_inches="tight",facecolor="white")
    plt.close(fig)

def main():
    ap=argparse.ArgumentParser();ap.add_argument("--output",type=Path,required=True);args=ap.parse_args()
    out=args.output.resolve();assert ROOT not in out.parents and out!=ROOT
    out.mkdir(parents=True,exist_ok=True);assert not any(out.iterdir())
    figures=out/"figures";figures.mkdir()
    commit=subprocess.check_output(["git","rev-parse","HEAD"],cwd=ROOT,text=True).strip()
    subprocess.run(["git","diff","--exit-code",BASE,"HEAD","--","app","config","database","routes","resources","tests/Feature","tests/Unit","research/ai_computational/results","research/ai_computational/datasets","research/ai_computational/config"],cwd=ROOT,check=True)
    sources={}
    def verified(directory,name):
        p=directory/name;m=load(p);sources[str(p.relative_to(ROOT))]=sha(p)
        for file,digest in m.get("sha256",{}).items():
            path=directory/file;assert sha(path)==digest,str(path);sources[str(path.relative_to(ROOT))]=digest
        return m
    m2=verified(P2,"run_manifest.json");m3=verified(P3,"phase3_manifest.json")
    m4=verified(P4,"phase4_manifest.json");m5=verified(P5,"phase5_manifest.json");m6=verified(P6,"phase6_manifest.json")
    p=P2/"comparative_results.csv";assert sha(p)==m2["results_sha256"];sources[str(p.relative_to(ROOT))]=sha(p)
    assert m3["gate_pass"] and m4["gate_pass"] and m5["execution_gate_pass"] and m6["execution_gate_pass"]
    # Paired actions and targets; no reference labels.
    raw=read(p);assert len(raw)==2960
    pairs=defaultdict(dict)
    for r in raw:
        assert r["algorithm_code"] in A and r["algorithm_code"] not in pairs[r["case_id"]]
        pairs[r["case_id"]][r["algorithm_code"]]=r
    assert len(pairs)==1480
    obs=read(P5/"engine_observations.csv");assert len(obs)==10702
    profiles={}
    for r in obs:
        if r["stage"]=="base" and r["base_algorithm"]==A[0]:
            profiles[r["case_id"]]=json.loads(r["profile_json"])
    assert set(profiles)==set(pairs)
    def agreement(group,ids):
        complete=[pairs[i] for i in ids if set(pairs[i])==set(A) and all(r["success"]=="1" for r in pairs[i].values())]
        n=len(complete);c=Counter((x[A[0]]["final_action"],x[A[1]]["final_action"]) for x in complete)
        assert all(x in ACT and y in ACT for x,y in c)
        same=sum(c[x,x] for x in ACT);target=sum(x[A[0]]["target_concept_code"]==x[A[1]]["target_concept_code"] for x in complete)
        both=sum(x[A[0]]["final_action"]==x[A[1]]["final_action"] and x[A[0]]["target_concept_code"]==x[A[1]]["target_concept_code"] for x in complete)
        expected=sum(sum(c[x,y] for y in ACT)*sum(c[y,x] for y in ACT) for x in ACT)/n**2 if n else None
        kappa=(same/n-expected)/(1-expected) if n and expected!=1 else None
        return dict(group=group,planned=len(ids),complete_pairs=n,failed_pairs=len(ids)-n,same_action=same,different_action=n-same,
                    same_target=target,same_both=both,agreement_pct=pct(same,n),divergence_pct=pct(n-same,n),
                    target_agreement_pct=pct(target,n),joint_agreement_pct=pct(both,n),expected_agreement=expected,cohens_kappa=kappa),c
    groups={"ALL":list(pairs)}
    for ds in ("D1","D2","D3","D4"):
        groups[ds]=[i for i in pairs if pairs[i][A[0]]["dataset"]==ds]
    agreement_rows=[];matrix=[];distribution=[]
    for g,ids in groups.items():
        a,c=agreement(g,ids);agreement_rows.append(a)
        source=m2 if g=="ALL" else m2["by_dataset"][g]
        for k in ("complete_pairs","same_action","same_target","same_both"):check(a[k],source[k])
        for x in ACT:
            for y in ACT:matrix.append(dict(dataset=g,rule_based_action=x,fuzzy_action=y,count=c[x,y]))
            for j,alg in enumerate(A):
                count=sum(c[x,y] if j==0 else c[y,x] for y in ACT)
                distribution.append(dict(dataset=g,algorithm=alg,action=x,count=count,percent=pct(count,a["complete_pairs"])))
    write(out/"agreement.csv",agreement_rows);write(out/"decision_matrix_6x6.csv",matrix);write(out/"recommendation_distribution.csv",distribution)
    contexts=defaultdict(list)
    for i in groups["D4"]:
        r=profiles[i]
        contexts[("seed",str(r["seed"]))].append(i)
        contexts[("prerequisite_pattern",r["prereq1_state"]+"+"+r["prereq2_state"])].append(i)
        contexts[("next_context",r["next_context"])].append(i)
    write(out/"d4_context_agreement.csv",[dict(dimension=d,**agreement(g,ids)[0]) for (d,g),ids in sorted(contexts.items())])
    # Numerical verification based on actual and reference CSV values.
    num=read(P3/"numerical_results.csv");bound=read(P3/"boundary_results.csv")
    assert len(num)==len(bound)==1542
    assert len({r["case_id"] for r in num})==1542
    assert {r["case_id"] for r in num}=={r["case_id"] for r in bound}
    bmap={r["case_id"]:r for r in bound};numeric=[];rule_ids=Counter()
    for g in ("ALL","D1","D2","D3","D4","D5","B3"):
        rr=[r for r in num if g=="ALL" or r["dataset"]==g];err=[]
        counts=Counter()
        for r in rr:
            e=abs(float(r["actual_centroid"])-float(r["reference_centroid"]));err.append(e)
            check(e,float(r["absolute_error"]))
            tests={"centroid_pass":e<=m3["centroid_tolerance"],"selected_pass":r["reference_selected"]==r["actual_selected"],
                "fired_count_pass":int(r["reference_fired_count"])==int(r["actual_fired_count"]),
                "coverage_nonzero":int(r["actual_fired_count"])>0}
            for k,v in tests.items():assert int(r[k])==int(v);counts[k]+=int(v)
            b=bmap[r["case_id"]]
            rb=all(b["rule_expected_"+k]==b["rule_actual_"+k] for k in ("action","target","reason"))
            fz=all(b["fuzzy_expected_"+k]==b["fuzzy_actual_"+k] for k in ("action","target"))
            assert int(b["rule_spec_pass"])==rb and int(b["fuzzy_target_action_pass"])==fz
            counts["rule_spec_pass"]+=rb;counts["fuzzy_target_action_pass"]+=fz
            if g=="ALL":rule_ids.update(r["actual_fired_rule_ids"].split("|") if r["actual_fired_rule_ids"] else [])
        source=m3["totals"] if g=="ALL" else m3["by_dataset"][g]
        for k,v in counts.items():check(v,source[k])
        numeric.append(dict(dataset=g,profiles=len(rr),mean_absolute_error=st.mean(err),max_absolute_error=max(err),**dict(counts)))
    write(out/"numerical_verification.csv",numeric)
    write(out/"reference_cases.csv",[r for r in num if r["dataset"]=="D5"])
    write(out/"fuzzy_rule_coverage.csv",[dict(rule_id=i,activations=rule_ids[str(i)]) for i in range(1,16)])
    check(numeric[0]["mean_absolute_error"],m3["mean_absolute_centroid_error"]);check(numeric[0]["max_absolute_error"],m3["max_absolute_centroid_error"])
    # Phase 4, calculate rates directly from decisions, retain inapplicable/failure slots.
    def stat(rows,mode):
        cc=Counter(r["status"] for r in rows);ok=[r for r in rows if r["status"]=="success"]
        ac=tc=both=zero=0
        for r in ok:
            a=r["final_action"]!=r["baseline_action"];t=r["target_concept_code"]!=r["baseline_target"]
            assert int(r["action_changed"])==a and int(r["target_changed"])==t and int(r["both_unchanged"])==(not a and not t)
            ac+=a;tc+=t;both+=not a and not t;zero+=r["base_algorithm"]==A[1] and not r["fired_rule_ids"]
        n=len(ok)
        return dict(planned=len(rows),actual_calls=sum(int(r["engine_called"]) for r in rows),successful=n,failed=cc["failed"],
            not_applicable=cc["not_applicable"],clipped_no_change=cc["clipped_no_change"],denominator=n,action_changed=ac,target_changed=tc,
            both_unchanged=both,parameter_sensitivity_rate=pct(ac,n) if mode=="sensitivity" else None,
            decision_stability_rate=pct(n-ac,n),target_stability_rate=pct(n-tc,n),joint_stability_rate=pct(both,n),fuzzy_zero_rule_count=zero)
    sens=read(P4/"sensitivity_results.csv");rob=read(P4/"robustness_results.csv")
    assert len(sens)==124320 and len(rob)==71040
    overall=[];detail=[];mag=[]
    for mode,rows in (("sensitivity",sens),("robustness",rob)):
        assert len({(r["case_id"],r["base_algorithm"],r["variant_id"]) for r in rows})==len(rows)
        gg=defaultdict(list)
        for r in rows:
            for ds in (r["dataset"],"ALL"):gg[(r["base_algorithm"],r["variant_id"],r["parameter"],r["delta"],ds)].append(r)
        for (alg,v,p,d,ds),rr in sorted(gg.items()):detail.append(dict(mode=mode,algorithm=alg,variant_id=v,parameter=p,delta=d,dataset=ds,**stat(rr,mode)))
        for alg in A:
            rr=[r for r in rows if r["base_algorithm"]==alg];s=stat(rr,mode)
            for k,v in s.items():check(v,m4["by_algorithm"][mode][alg][k])
            overall.append(dict(mode=mode,algorithm=alg,**s))
            if mode=="robustness":
                mm=defaultdict(list)
                for r in rr:mm[(r["parameter"],abs(int(r["delta"])))].append(r)
                for (p,d),rrr in sorted(mm.items()):mag.append(dict(algorithm=alg,parameter=p,magnitude=d,**stat(rrr,mode)))
    # Compare every metric row against prior result CSV.
    def verify_table(derived,archived,keys):
        ix={tuple(str(r[k]) for k in keys):r for r in archived};assert len(ix)==len(archived)==len(derived)
        for r in derived:
            old=ix[tuple(str(r[k]) for k in keys)]
            for k,v in r.items():
                if k not in keys:check(v,None if old[k]=="" else float(old[k]) if isinstance(v,(int,float)) else old[k])
    verify_table(detail,read(P4/"phase4_metrics.csv"),("mode","algorithm","variant_id","parameter","delta","dataset"))
    verify_table(mag,read(P4/"robustness_by_magnitude.csv"),("algorithm","parameter","magnitude"))
    write(out/"sensitivity_robustness_summary.csv",overall);write(out/"parameter_and_perturbation_metrics.csv",detail);write(out/"robustness_by_magnitude.csv",mag)
    # Match robustness by identical perturbation keys, not pooled independent observations.
    matched=defaultdict(dict)
    for r in rob:matched[(r["case_id"],r["variant_id"])][r["base_algorithm"]]=r
    paired=Counter()
    for k,v in matched.items():
        assert set(v)==set(A)
        a,b=(v[x] for x in A);assert a["status"]==b["status"]
        if a["status"]=="success":paired[(int(a["action_changed"]),int(b["action_changed"]))]+=1
    paired_rows=[dict(rule_based_changed=a,fuzzy_changed=b,count=paired[a,b]) for a in (0,1) for b in (0,1)]
    write(out/"matched_robustness.csv",paired_rows)
    zero=[r for r in sens if r["base_algorithm"]==A[1] and r["status"]=="success" and not r["fired_rule_ids"]]
    assert len(zero)==306
    write(out/"phase4_zero_rule_cases.csv",zero)
    # Phase 5: recompute status counts, verify exact negative subset and prior variant identities.
    checks=read(P5/"metamorphic_results.csv");assert len(checks)==30766
    negative=[r for r in checks if r["status"] in ("fail","error")]
    assert negative==read(P5/"negative_results.csv")
    assert len(negative)==306
    write(out/"phase5_negative_results.csv",negative)
    gg=defaultdict(Counter)
    for r in checks:gg[(r["scope"],r["suite"],r["algorithm"],"metamorphic" if r["check_id"].startswith("MR") else "property",r["check_id"])][r["status"]]+=1
    p5=[]
    for (scope,suite,alg,kind,prop),c in sorted(gg.items()):
        n=c["pass"]+c["fail"]
        p5.append(dict(scope=scope,suite=suite,algorithm=alg,kind=kind,check_id=prop,planned=sum(c.values()),passed=c["pass"],failed=c["fail"],
            not_applicable=c["not_applicable"],errors=c["error"],denominator=n,satisfaction_rate=pct(c["pass"],n)))
    verify_table(p5,read(P5/"phase5_metrics.csv"),("scope","suite","algorithm","kind","check_id"))
    write(out/"metamorphic_property_metrics.csv",p5)
    zz={(r["case_id"],r["variant_id"]):r for r in zero}
    replay=[r for r in obs if r["stage"]=="stress_replay"]
    assert len(replay)==306 and {(r["case_id"],r["variant_id"]) for r in replay}==set(zz)
    zcount=Counter();fallback=Counter()
    for r in replay:
        old=zz[(r["case_id"],r["variant_id"])];s=json.loads(r["observation_json"])
        assert r["config_hash"]==old["config_hash"] and r["status"]=="success" and not s["rules"]
        zcount[r["variant_id"]]+=1;fallback[r["variant_id"]]+=s["action"]=="additional_diagnostic" and s["target"]=="C"
    neg_summary=[dict(variant_id=v,phase4_zero_rule_cases=n,phase5_reproduced=zcount[v],diagnostic_fallback=fallback[v]) for v,n in sorted(Counter(r["variant_id"] for r in zero).items())]
    write(out/"zero_rule_reproduction.csv",neg_summary)
    # Phase 6: descriptive preliminary timing only; warmup excluded.
    bench=read(P6/"benchmark_results.csv");assert len(bench)==26520 and all(r["status"]=="success" and r["archive_match"]=="1" for r in bench)
    def timing(rr):
        x=np.array([int(r["execution_ns"]) for r in rr],dtype=np.float64);q=np.quantile(x,[.25,.5,.75,.95],method="linear")
        return dict(success=len(x),mean_ms=float(x.mean()/1e6),median_ms=float(q[1]/1e6),sample_sd_ms=float(x.std(ddof=1)/1e6),q1_ms=float(q[0]/1e6),
            q3_ms=float(q[2]/1e6),iqr_ms=float((q[2]-q[0])/1e6),p95_ms=float(q[3]/1e6),min_ms=float(x.min()/1e6),max_ms=float(x.max()/1e6),
            successful_calls_per_engine_second=float(len(x)*1e9/x.sum()))
    bsum=[];batches=[];lat=[]
    bm=load(P6/"batch_manifests.json");assert len(bm)==26
    for alg in A:
        rr=[r for r in bench if r["stage"]=="measured" and r["call_kind"]=="batch" and r["algorithm_code"]==alg];assert len(rr)==10000
        s=timing(rr)
        for k,v in s.items():check(v,m6["by_algorithm"][alg][k])
        lat.append([int(r["execution_ns"])/1e6 for r in rr])
        bsum.append(dict(environment="shared-ci preliminary",algorithm=alg,**s,
            peak_php_allocated_bytes=max(r["peak_php_allocated_bytes"] for r in bm if r["stage"]=="measured" and r["algorithm"]==alg),
            peak_php_used_bytes=max(r["peak_php_used_bytes"] for r in bm if r["stage"]=="measured" and r["algorithm"]==alg)))
        for i in range(1,11):
            rs=[r for r in rr if int(r["batch"])==i];assert len(rs)==1000
            batches.append(dict(environment="shared-ci preliminary",algorithm=alg,batch=i,**timing(rs)))
    for r in batches:
        old=next(x for x in read(P6/"benchmark_batch_metrics.csv") if x["stage"]=="measured" and x["algorithm"]==r["algorithm"] and int(x["batch"])==r["batch"])
        for k,v in r.items():
            if k not in ("environment","algorithm","batch"):check(v,float(old[k]))
    write(out/"benchmark_preliminary_summary.csv",bsum);write(out/"benchmark_preliminary_batches.csv",batches)
    # Publication figures in paired vector and high-resolution raster formats.
    c=np.array([[next(r["count"] for r in matrix if r["dataset"]=="ALL" and r["rule_based_action"]==x and r["fuzzy_action"]==y) for y in ACT] for x in ACT])
    fig,ax=plt.subplots(figsize=(7.2,5.7),layout="constrained");im=ax.imshow(c,cmap="Blues");ax.set_xticks(range(6),SHORT,rotation=35,ha="right");ax.set_yticks(range(6),SHORT)
    for i in range(6):
        for j in range(6):ax.text(j,i,str(c[i,j]),ha="center",va="center",color="white" if c[i,j]>c.max()/2 else "#182433")
    ax.set(xlabel="Fuzzy Mamdani final action",ylabel="Rule-Based final action",title="Paired decision agreement (n = 1,480)");fig.colorbar(im,ax=ax,label="Paired profiles");figsave(fig,figures,"fig01_decision_matrix")
    fig,ax=plt.subplots(figsize=(7.2,3.8),layout="constrained");x=np.arange(6)
    for j in range(2):ax.bar(x+(j-.5)*.36,[r["count"] for r in distribution if r["dataset"]=="ALL" and r["algorithm"]==A[j]],.36,label=LABEL[j],color=COLORS[j],hatch="" if j==0 else "//")
    ax.set_xticks(x,SHORT,rotation=25,ha="right");ax.set(ylabel="Profiles",title="Distribution of final recommendations");ax.legend(frameon=False);figsave(fig,figures,"fig02_action_distribution")
    fig,ax=plt.subplots(figsize=(6.6,3.5),layout="constrained");ax.bar([r["group"] for r in agreement_rows],[r["cohens_kappa"] for r in agreement_rows],color=COLORS[0]);ax.set(ylim=(0,1),ylabel="Cohen's kappa",title="Descriptive agreement by dataset")
    for i,r in enumerate(agreement_rows):ax.text(i,r["cohens_kappa"]+.025,f'{r["cohens_kappa"]:.3f}',ha="center")
    figsave(fig,figures,"fig03_kappa")
    fig,axes=plt.subplots(1,2,figsize=(10,4.1),layout="constrained")
    for j,ax in enumerate(axes):
        rr=sorted([r for r in detail if r["mode"]=="sensitivity" and r["algorithm"]==A[j] and r["dataset"]=="ALL"],key=lambda r:r["parameter_sensitivity_rate"],reverse=True)[:8][::-1]
        ax.barh([r["variant_id"]+" ("+r["delta"]+")" for r in rr],[r["parameter_sensitivity_rate"] for r in rr],color=COLORS[j]);ax.set(title=LABEL[j]+" — top 8 variants",xlabel="Action change (%)")
    fig.suptitle("Parameter sensitivity (different variant sets)");figsave(fig,figures,"fig04_parameter_sensitivity")
    fig,axes=plt.subplots(1,2,figsize=(8.2,3.5),layout="constrained")
    for j in range(2):
        rr=[r for r in mag if r["algorithm"]==A[j] and r["parameter"]=="mastery_score"]
        axes[0].plot([r["magnitude"] for r in rr],[r["decision_stability_rate"] for r in rr],marker="o" if j==0 else "s",linestyle="-" if j==0 else "--",color=COLORS[j],label=LABEL[j])
        r=next(r for r in overall if r["mode"]=="robustness" and r["algorithm"]==A[j])
        axes[1].bar(np.arange(3)+(j-.5)*.36,[r[k] for k in ("decision_stability_rate","target_stability_rate","joint_stability_rate")],.36,color=COLORS[j],hatch="" if j==0 else "//",label=LABEL[j])
    axes[0].set(xlabel="Absolute mastery perturbation",ylabel="Decision stability (%)",xticks=[1,3,5],ylim=(0,105),title="Mastery perturbation");axes[0].legend(frameon=False)
    axes[1].set_xticks(range(3),["Decision","Target","Joint"]);axes[1].set(ylim=(0,105),ylabel="Stability (%)",title="All valid perturbations");figsave(fig,figures,"fig05_robustness")
    fig,ax=plt.subplots(figsize=(6.5,3.5),layout="constrained");x=np.arange(len(neg_summary))
    ax.bar(x-.18,[r["phase4_zero_rule_cases"] for r in neg_summary],.36,label="Phase 4 observed",color=COLORS[0]);ax.bar(x+.18,[r["phase5_reproduced"] for r in neg_summary],.36,label="Phase 5 reproduced",color=COLORS[1],hatch="//")
    ax.set_xticks(x,[r["variant_id"].removeprefix("p4-fz-") for r in neg_summary],rotation=25,ha="right");ax.set(ylabel="Selected negative cases",title="Zero-rule activation: outcome-selected replay");ax.legend(frameon=False);figsave(fig,figures,"fig06_negative_reproduction")
    fig,axes=plt.subplots(1,2,figsize=(8.2,3.6),layout="constrained")
    bp=axes[0].boxplot(lat,labels=LABEL,patch_artist=True,showfliers=True,flierprops={"markersize":1,"alpha":.2})
    for j,b in enumerate(bp["boxes"]):b.set_facecolor(COLORS[j]);b.set_alpha(.65)
    axes[0].set(ylabel="Latency (ms)",title="10,000 measured calls / engine")
    for j,alg in enumerate(A):
        rr=[r for r in batches if r["algorithm"]==alg];axes[1].plot([r["batch"] for r in rr],[r["median_ms"] for r in rr],marker="o" if j==0 else "s",color=COLORS[j],linestyle="-" if j==0 else "--",label=LABEL[j])
    axes[1].set(xlabel="Measured batch",ylabel="Median latency (ms)",xticks=range(1,11),title="Batch medians");axes[1].legend(frameon=False);fig.suptitle("Preliminary benchmark — shared GitHub CI");figsave(fig,figures,"fig07_preliminary_latency")
    fig,ax=plt.subplots(figsize=(6,3.5),layout="constrained");ax.bar(LABEL,[r["successful_calls_per_engine_second"] for r in bsum],color=COLORS)
    ax.set(ylabel="Successful calls / engine-second",title="Preliminary engine throughput — shared CI");figsave(fig,figures,"fig08_preliminary_throughput")
    summary=dict(schema_version=1,analysis_commit=commit,source_archive_commit=BASE,source_sha256=sources,agreement=agreement_rows,numerical=numeric,
        phase4=overall,matched_robustness=paired_rows,phase5_scope_counts=m5["scopes"],zero_rule_reproduction=neg_summary,
        preliminary_shared_ci_benchmark=bsum,controlled_local_benchmark=None,controlled_local_status="not performed; required for final timing under protocol",
        python=platform.python_version(),matplotlib=matplotlib.__version__,numpy=np.__version__,
        limitations=["Descriptive synthetic profiles; no independent human ground truth or inferential p-values.",
            "Parameter sets differ in number and type; no direct superiority claim from pooled PSR.",
            "Negative replay selected on prior zero-rule outcomes; no population incidence estimate.",
            "Latency is preliminary shared CI; repeated cases not independent and PHP allocator peak is not algorithm-only RAM.",
            "Phase 5 recounts recorded check statuses; no fresh engine execution."])
    summary["output_sha256"]={str(p.relative_to(out)):sha(p) for p in sorted(out.rglob("*")) if p.is_file()}
    save(out/"summary_metrics.json",summary)
    print("PHASE7_SUMMARY "+json.dumps({k:summary[k] for k in ("agreement","numerical","matched_robustness","zero_rule_reproduction","preliminary_shared_ci_benchmark","controlled_local_status")},separators=(",",":")),flush=True)
    print("PHASE7_OUTPUT_FILES",len(summary["output_sha256"])+1,flush=True)
if __name__=="__main__":main()
