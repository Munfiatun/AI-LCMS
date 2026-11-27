<?php

declare(strict_types=1);

namespace Tests\Research\AIComputational;

use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\Learning\Adaptive\FuzzyAdaptiveEngine;
use App\Services\Learning\Adaptive\RuleBasedAdaptiveEngine;
use Illuminate\Support\Facades\DB;
use Tests\Research\AIComputational\Support\ExperimentFixture;
use Tests\Research\AIComputational\Support\ExperimentRunner;
use Tests\TestCase;
use RuntimeException;
use Throwable;

final class MetamorphicPropertyExperimentTest extends TestCase
{
    private const ALGS = ['rule-based-v1'=>[RuleBasedAdaptiveEngine::class,'1.0.0'],
        'fuzzy-v2'=>[FuzzyAdaptiveEngine::class,'2.0.0-experimental']];
    private const ACTIONS = ['additional_diagnostic','prerequisite_remediation','remediation','reinforcement','next_concept','enrichment'];
    private $raw;
    private $checks;
    private int $calls=0;
    private int $errors=0;
    private array $counts=[];

    private function encodeObservation(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function write($handle,array $row): void
    {
        if (fputcsv($handle,$row,',','"','',"\n")===false) throw new RuntimeException('CSV write failed');
    }

    private function check(string $suite,string $id,string $alg,string $property,?bool $pass,array $actual,array $expected=[],string $scope='baseline'): void
    {
        $status=$pass===null?'not_applicable':($pass?'pass':'fail');
        $this->write($this->checks,[$scope,$suite,$id,$alg,$property,$status,$this->encodeObservation($expected),$this->encodeObservation($actual)]);
        $this->counts[$status]=($this->counts[$status]??0)+1;
    }

    private function error(string $suite,string $id,string $alg,string $property,string $scope): void
    {
        $this->write($this->checks,[$scope,$suite,$id,$alg,$property,'error','{}','{"reason":"engine_or_fixture_failed"}']);
        $this->counts['error']=($this->counts['error']??0)+1;
    }

    /** A new disposable fixture per call; no persisted recommendations. */
    private function observe(array $p,User $teacher,User $student,string $alg,string $stage,bool $missing=false,?array $variant=null): ?array
    {
        $level=DB::transactionLevel(); DB::beginTransaction();
        $original=config('adaptive.algorithms');
        $selector=$alg==='fuzzy-v2'?'adaptive.comparison_algorithm':'adaptive.baseline_algorithm';
        [$class,$version]=self::ALGS[$alg];$code=$alg;
        $configHash=hash('sha256',$this->encodeObservation($original[$alg]['configuration']));
        $called=0;$snapshot=null;$error='';
        try {
            if ($variant!==null) {
                $def=$original[$alg];
                if (data_get($def['configuration'],$variant['path'])!=$variant['baseline']) throw new RuntimeException('Variant baseline mismatch');
                data_set($def['configuration'],$variant['path'],$variant['value']);
                $code=$variant['id'];$version='phase4-1.0.0-'.$code;
                $def['name']='Research '.$code;$def['version']=$version;$def['status']='experimental';$def['is_baseline']=false;
                $configHash=hash('sha256',$this->encodeObservation($def['configuration']));
                config(['adaptive.algorithms'=>$original+[$code=>$def],$selector=>$code]);
            }
            $fixture=ExperimentFixture::build($p,$teacher,$student);
            if ($missing) {
                LearnerMastery::query()->where('student_id',$student->id)->where('concept_id',$fixture['concept']->id)->delete();
            }
            $engine=app($class);$called=1;$this->calls++;
            $d=$engine->decide($student,$fixture['concept']);
            $target=$d->targetConceptId===null?null:LearningConcept::query()->find($d->targetConceptId);
            $ready=false;
            if ($target!==null) {
                $ready=$target->course_id===$fixture['course']->id && $target->is_active &&
                    ($target->order>$fixture['concept']->order ||
                    ($target->order===$fixture['concept']->order && $target->id>$fixture['concept']->id));
                foreach ($target->prerequisites()->get() as $pre) {
                    $m=LearnerMastery::query()->where('student_id',$student->id)->where('concept_id',$pre->id)->first();
                    $ready=$ready && $m!==null && $m->evidence_count>=2 && $m->mastery_score>=70;
                }
            }
            $snapshot=[
                'algorithm_code'=>$d->algorithmCode,'algorithm_version'=>$d->algorithmVersion,
                'action'=>$d->action,'target'=>$target?->code,'reason'=>$d->reasonCode,
                'same_course'=>$target!==null && $target->course_id===$fixture['course']->id,
                'next_ready'=>$ready,
                'selected'=>$d->metadata['selected_output']??null,
                'centroid'=>$d->metadata['crisp_output']??null,
                'memberships'=>$d->metadata['memberships']??null,
                'activation'=>$d->metadata['output_activation']??null,
                'rules'=>$d->metadata['fired_rules']??[],
            ];
            if ($d->algorithmCode!==$code || $d->algorithmVersion!==$version) throw new RuntimeException('Unexpected algorithm identity');
        } catch (Throwable $e) {
            $this->errors++;$error=get_class($e);$snapshot=null;
        } finally {
            config(['adaptive.algorithms'=>$original,$selector=>$alg]);
            DB::rollBack($level);
        }
        $this->write($this->raw,[$stage,$p['case_id'],$p['dataset'],$alg,$code,$version,
            $variant['id']??'baseline',hash('sha256',$this->encodeObservation([$p,$missing])),$configHash,$called,
            $snapshot===null?'error':'success',$error,$this->encodeObservation($p),(int)$missing,$this->encodeObservation($snapshot)]);
        return $snapshot;
    }

    private function compareArchive(?array $s,array $old): bool
    {
        return $s!==null && $s['action']===$old['final_action'] && $s['target']===$old['target_concept_code']
            && $s['reason']===$old['reason_code'] && ($s['selected']??'')===$old['selected_fuzzy_output']
            && ($s['centroid']===null?'':number_format((float)$s['centroid'],4,'.',''))===$old['centroid']
            && implode('|',array_column($s['rules'],'rule'))===$old['fired_rule_ids'];
    }

    private function properties(string $id,string $alg,?array $s,array $p,bool $missing=false,string $suite='base'): void
    {
        $items=['P01_action_domain','P02_same_course','P03_centroid_range','P04_low_evidence_diagnosis',
            'P05_next_unavailable_fallback','P06_next_readiness','P07_nonzero_rule_coverage'];
        if ($s===null) {
            foreach ($items as $prop) $this->error($suite,$id,$alg,$prop,'baseline');
            return;
        }
        $fz=$alg==='fuzzy-v2';
        $low=!$fz && ($missing || (int)$p['evidence_count']<2);
        $fallback=$fz && $s['selected']==='next_concept' && in_array($p['next_context'],['none','blocked','dual_blocked'],true);
        $this->check($suite,$id,$alg,$items[0],in_array($s['action'],self::ACTIONS,true),['action'=>$s['action']],['allowed'=>self::ACTIONS]);
        $this->check($suite,$id,$alg,$items[1],$s['same_course'],['target'=>$s['target'],'same_course'=>$s['same_course']],['same_course'=>true]);
        $cent=$s['centroid'];
        $this->check($suite,$id,$alg,$items[2],$fz?(is_numeric($cent)&&is_finite((float)$cent)&&$cent>=0&&$cent<=100):null,['centroid'=>$cent],['range'=>[0,100]]);
        $this->check($suite,$id,$alg,$items[3],$low?($s['action']==='additional_diagnostic'&&$s['target']==='C'):null,
            ['action'=>$s['action'],'target'=>$s['target']],['action'=>'additional_diagnostic','target'=>'C']);
        $this->check($suite,$id,$alg,$items[4],$fallback?($s['action']==='enrichment'&&$s['target']==='C'):null,
            ['selected'=>$s['selected'],'action'=>$s['action'],'target'=>$s['target'],'next_context'=>$p['next_context']],['action'=>'enrichment','target'=>'C']);
        $this->check($suite,$id,$alg,$items[5],$s['action']==='next_concept'?$s['next_ready']:null,['target'=>$s['target'],'ready'=>$s['next_ready']],['ready'=>true]);
        $this->check($suite,$id,$alg,$items[6],$fz?count($s['rules'])>0:null,['count'=>count($s['rules'])],['minimum'=>1]);
    }

    private function archive(string $path): array
    {
        $f=fopen($path,'rb');$h=fgetcsv($f,0,',','"','');$rows=[];
        while (($v=fgetcsv($f,0,',','"',''))!==false) {
            $r=array_combine($h,$v);
            $key=$r['case_id'].'/'.$r['algorithm_code'];
            $this->assertArrayNotHasKey($key,$rows);$rows[$key]=$r;
        }
        fclose($f);return $rows;
    }

    public function test_phase5_actual_engines(): void
    {
        ExperimentFixture::assertIsolated();
        $out=getenv('AI_PHASE5_OUTPUT');$this->assertIsString($out);$this->assertDirectoryExists($out);
        $this->assertFileDoesNotExist($out.'/engine_observations.csv');
        $archive=base_path('research/ai_computational/results/phase3/run-45928359249/phase2_replay');
        $p4=base_path('research/ai_computational/results/phase4/run-45933537326');
        $m2=json_decode(file_get_contents($archive.'/run_manifest.json'),true,512,JSON_THROW_ON_ERROR);
        $m4=json_decode(file_get_contents($p4.'/phase4_manifest.json'),true,512,JSON_THROW_ON_ERROR);
        $this->assertTrue($m4['gate_pass']);
        $this->assertSame($m2['results_sha256'],hash_file('sha256',$archive.'/comparative_results.csv'));
        $this->assertSame($m4['sha256']['sensitivity_results.csv'],hash_file('sha256',$p4.'/sensitivity_results.csv'));
        $this->assertSame($m2['adaptive_file_sha256'],hash_file('sha256',config_path('adaptive.php')));
        foreach ($m2['input_sha256'] as $file=>$hash) $this->assertSame($hash,hash_file('sha256',base_path('research/ai_computational/datasets/'.$file)));
        $old=$this->archive($archive.'/comparative_results.csv');$this->assertCount(2960,$old);
        $profiles=ExperimentRunner::load(base_path('research/ai_computational/datasets'));
        $this->assertCount(1480,$profiles);
        $index=array_column($profiles,null,'case_id');
        $original=config('adaptive.algorithms');
        $this->artisan('migrate',['--force'=>true])->assertSuccessful();
        app(AdaptiveAlgorithmRegistry::class)->syncConfiguredVersions();
        $teacher=User::factory()->create(['name'=>'Synthetic teacher','email'=>'phase5-teacher@example.invalid']);
        $student=User::factory()->create(['name'=>'Synthetic student','email'=>'phase5-student@example.invalid']);
        $this->raw=fopen($out.'/engine_observations.csv','xb');
        $this->checks=fopen($out.'/metamorphic_results.csv','xb');
        $this->assertNotFalse($this->raw);$this->assertNotFalse($this->checks);
        $this->write($this->raw,['stage','case_id','dataset','base_algorithm','algorithm_code','algorithm_version','variant_id',
            'input_hash','config_hash','engine_called','status','error_class','profile_json','missing_current_mastery','observation_json']);
        $this->write($this->checks,['scope','suite','case_id','algorithm','check_id','status','expected_json','observed_json']);
        try {
            foreach ($profiles as $p) {
                $id=$p['case_id'];
                foreach (self::ALGS as $alg=>$_) {
                    $a=$this->observe($p,$teacher,$student,$alg,'base');
                    $b=$this->observe($p,$teacher,$student,$alg,'repeat');
                    if ($a===null || $b===null) {
                        $this->error('base',$id,$alg,'MR01_repeat_determinism','baseline');
                    } else {
                        $this->check('base',$id,$alg,'MR01_repeat_determinism',$a===$b,$b,$a);
                    }
                    if ($a===null) $this->error('base',$id,$alg,'MR03_archive_replay','baseline');
                    else $this->check('base',$id,$alg,'MR03_archive_replay',$this->compareArchive($a,$old[$id.'/'.$alg]),$a,$old[$id.'/'.$alg]);
                    $this->properties($id,$alg,$a,$p);
                    if ($p['dataset']==='D3') {
                        $missing=$this->observe($p,$teacher,$student,$alg,'missing_current',true);
                        $this->properties($id,$alg,$missing,$p,true,'missing_current');
                    }
                }
                $q=$p;$q['evidence_count']='10';
                $a=$this->observe($q,$teacher,$student,'fuzzy-v2','saturation_10');
                foreach ([11,20] as $value) {
                    $q['evidence_count']=(string)$value;
                    $b=$this->observe($q,$teacher,$student,'fuzzy-v2','saturation_'.$value);
                    $prop='MR02_evidence_10_vs_'.$value;
                    if ($a===null || $b===null) $this->error('saturation',$id,'fuzzy-v2',$prop,'baseline');
                    else $this->check('saturation',$id,'fuzzy-v2',$prop,$a===$b,$b,$a);
                }
            }
            // Targeted nonvacuous fallback relation: change only readiness/availability of next concept.
            $p=$profiles[0];
            $p=array_replace($p,['case_id'=>'FALLBACK-READY','dataset'=>'P5','mastery_score'=>'90.00','evidence_count'=>'3',
                'prereq1_state'=>'absent','prereq1_mastery'=>'','prereq1_evidence'=>'','prereq2_state'=>'absent',
                'prereq2_mastery'=>'','prereq2_evidence'=>'','next_context'=>'ready']);
            $a=$this->observe($p,$teacher,$student,'fuzzy-v2','fallback_ready');
            foreach (['none','blocked','dual_blocked'] as $context) {
                $q=$p;$q['case_id']='FALLBACK-'.strtoupper($context);$q['next_context']=$context;
                $b=$this->observe($q,$teacher,$student,'fuzzy-v2','fallback_'.$context);
                if ($a===null || $b===null) $this->error('fallback',$q['case_id'],'fuzzy-v2','MR04_target_fallback','baseline');
                else {
                    $same=true;
                    foreach (['selected','centroid','memberships','activation','rules'] as $k) $same=$same&&$a[$k]===$b[$k];
                    $this->check('fallback',$q['case_id'],'fuzzy-v2','MR04_target_fallback',
                        $same&&$a['selected']==='next_concept'&&$a['action']==='next_concept'&&$a['target']==='N'
                        &&$b['action']==='enrichment'&&$b['target']==='C',$b,$a);
                }
                $this->properties($q['case_id'],'fuzzy-v2',$b,$q,false,'fallback');
            }
            // Outcome-selected confirmation set from Phase 4; do not estimate incidence from this set.
            $plan=json_decode(file_get_contents(base_path('research/ai_computational/config/phase4_variants.json')),true,512,JSON_THROW_ON_ERROR);
            $variants=array_column($plan['variants'],null,'id');
            $f=fopen($p4.'/sensitivity_results.csv','rb');$h=fgetcsv($f,0,',','"','');$n=0;
            while (($v=fgetcsv($f,0,',','"',''))!==false) {
                $r=array_combine($h,$v);
                if ($r['base_algorithm']!=='fuzzy-v2'||$r['status']!=='success'||$r['fired_rule_ids']!=='') continue;
                $n++;$variant=$variants[$r['variant_id']];$this->assertTrue($variant['valid']);
                $p=$index[$r['case_id']];$id=$r['case_id'].'/'.$r['variant_id'];
                $s=$this->observe($p,$teacher,$student,'fuzzy-v2','stress_replay',false,$variant);
                if ($s===null) {
                    foreach (['P07_nonzero_rule_coverage','P08_zero_rule_safe_fallback','MR05_phase4_negative_replay'] as $prop)
                        $this->error('phase4_selected_negative',$id,'fuzzy-v2',$prop,'stress');
                    continue;
                }
                $this->check('phase4_selected_negative',$id,'fuzzy-v2','P07_nonzero_rule_coverage',count($s['rules'])>0,['count'=>count($s['rules'])],['minimum'=>1],'stress');
                $this->check('phase4_selected_negative',$id,'fuzzy-v2','P08_zero_rule_safe_fallback',
                    count($s['rules'])===0?($s['action']==='additional_diagnostic'&&$s['target']==='C'):null,$s,['action'=>'additional_diagnostic','target'=>'C'],'stress');
                $this->check('phase4_selected_negative',$id,'fuzzy-v2','MR05_phase4_negative_replay',$this->compareArchive($s,$r),$s,$r,'stress');
            }
            fclose($f);$this->assertSame(306,$n);
        } finally {
            fclose($this->raw);fclose($this->checks);
        }
        $this->assertSame($original,config('adaptive.algorithms'));
        $this->assertSame('rule-based-v1',config('adaptive.baseline_algorithm'));
        $this->assertSame('fuzzy-v2',config('adaptive.comparison_algorithm'));
        $this->assertSame(10702,$this->calls);
        fwrite(STDOUT,'PHASE5_EXECUTION '.$this->encodeObservation(['engine_calls'=>$this->calls,'execution_errors'=>$this->errors,'check_counts'=>$this->counts]).PHP_EOL);
        // Do not assert that all scientific properties pass: retain negative observations.
        $this->assertSame(0,$this->errors,'Engine/instrument errors; inspect raw outputs.');
    }
}
