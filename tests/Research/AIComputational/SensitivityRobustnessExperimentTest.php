<?php

declare(strict_types=1);

namespace Tests\Research\AIComputational;

use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\Learning\Adaptive\RuleBasedAdaptiveEngine;
use App\Services\Learning\Adaptive\FuzzyAdaptiveEngine;
use Illuminate\Support\Facades\DB;
use Tests\Research\AIComputational\Support\ExperimentFixture;
use Tests\Research\AIComputational\Support\ExperimentRunner;
use Tests\TestCase;
use RuntimeException;
use Throwable;

final class SensitivityRobustnessExperimentTest extends TestCase
{
    private const ACTIONS = ['additional_diagnostic','prerequisite_remediation','remediation',
        'reinforcement','next_concept','enrichment'];
    private const HEADER = ['case_id','dataset','seed','base_algorithm','algorithm_code','algorithm_version',
        'variant_id','parameter','delta','old_value','new_value','input_hash','config_hash','status','engine_called',
        'error_class','baseline_action','baseline_target','baseline_centroid','final_action','target_concept_code',
        'reason_code','selected_fuzzy_output','centroid','fired_rule_ids','action_changed','target_changed','both_unchanged'];

    private function digest(array $v): string
    {
        return hash('sha256', json_encode($v, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function decide(array $fixture, string $class, string $code, string $version): array
    {
        $level = DB::transactionLevel();
        DB::beginTransaction();
        $record = ['status'=>'failed','engine_called'=>0,'error_class'=>'','final_action'=>'','target_concept_code'=>'',
            'reason_code'=>'','selected_fuzzy_output'=>'','centroid'=>'','fired_rule_ids'=>''];
        try {
            $engine = app($class);
            $record['engine_called'] = 1;
            $d = $engine->decide($fixture['student'], $fixture['concept']);
            $record['final_action'] = $d->action;
            $record['target_concept_code'] = $fixture['targets'][$d->targetConceptId] ?? '';
            $record['reason_code'] = $d->reasonCode;
            $record['selected_fuzzy_output'] = $d->metadata['selected_output'] ?? '';
            $record['centroid'] = isset($d->metadata['crisp_output']) ? number_format((float) $d->metadata['crisp_output'],4,'.','') : '';
            $record['fired_rule_ids'] = implode('|', array_column($d->metadata['fired_rules'] ?? [], 'rule'));
            if ($d->algorithmCode !== $code || $d->algorithmVersion !== $version
                || ! in_array($d->action,self::ACTIONS,true) || $record['target_concept_code']==='') {
                throw new RuntimeException('Unexpected identity/action/target.');
            }
            $record['status'] = 'success';
        } catch (Throwable $e) {
            $record['error_class'] = get_class($e);
        } finally {
            DB::rollBack($level);
        }
        return $record;
    }

    private function archived(string $alg): array
    {
        $p=base_path('research/ai_computational/results/phase3/run-45928359249/phase2_replay');
        $m=json_decode(file_get_contents($p.'/run_manifest.json'),true,512,JSON_THROW_ON_ERROR);
        $this->assertSame($m['results_sha256'],hash_file('sha256',$p.'/comparative_results.csv'));
        $this->assertSame(2960,$m['success']);
        foreach ($m['input_sha256'] as $file=>$hash) {
            $this->assertSame($hash,hash_file('sha256',base_path('research/ai_computational/datasets/'.$file)));
        }
        $this->assertSame($m['adaptive_file_sha256'],hash_file('sha256',config_path('adaptive.php')));
        $this->assertSame($m['algorithm_config_sha256'][$alg],$this->digest(config('adaptive.algorithms.'.$alg.'.configuration')));
        $f=fopen($p.'/comparative_results.csv','rb');
        $header=fgetcsv($f,0,',','"','');
        $out=[];
        while (($v=fgetcsv($f,0,',','"',''))!==false) {
            $r=array_combine($header,$v);
            if ($r['algorithm_code']===$alg) {
                $this->assertSame('1',$r['success']);
                $this->assertArrayNotHasKey($r['case_id'],$out);
                $out[$r['case_id']]=$r;
            }
        }
        fclose($f);
        $this->assertCount(1480,$out);
        return $out;
    }

    private function baselineMatches(array $actual,array $old): bool
    {
        if ($actual['status']!=='success') return false;
        foreach (['final_action','target_concept_code','reason_code','selected_fuzzy_output','centroid','fired_rule_ids'] as $k) {
            if ($actual[$k]!==$old[$k]) return false;
        }
        return true;
    }

    private function write($f,array $profile,array $base,array $observed,array $meta): void
    {
        $r=array_replace(array_fill_keys(self::HEADER,''),[
            'case_id'=>$profile['case_id'],'dataset'=>$profile['dataset'],'seed'=>$profile['seed'],
            'baseline_action'=>$base['final_action'],'baseline_target'=>$base['target_concept_code'],
            'baseline_centroid'=>$base['centroid'],
        ],$observed,$meta);
        if ($r['status']==='success') {
            $r['action_changed']=(int)($r['final_action']!==$base['final_action']);
            $r['target_changed']=(int)($r['target_concept_code']!==$base['target_concept_code']);
            $r['both_unchanged']=(int)(!$r['action_changed']&&!$r['target_changed']);
        }
        if (fputcsv($f,array_values($r),',','"','',"\n")===false) throw new RuntimeException('CSV write failed');
    }

    public function test_phase4_shard(): void
    {
        ExperimentFixture::assertIsolated();
        $shard=getenv('AI_PHASE4_SHARD');
        $out=getenv('AI_PHASE4_OUTPUT');
        $plan=json_decode(file_get_contents(base_path('research/ai_computational/config/phase4_variants.json')),true,512,JSON_THROW_ON_ERROR);
        $this->assertContains($shard,$plan['shards']);
        $this->assertIsString($out);
        $this->assertDirectoryExists($out);
        $isRb=str_starts_with($shard,'rb-');
        $robust=str_ends_with($shard,'robustness');
        $alg=$isRb?'rule-based-v1':'fuzzy-v2';
        $class=$isRb?RuleBasedAdaptiveEngine::class:FuzzyAdaptiveEngine::class;
        $selector=$isRb?'adaptive.baseline_algorithm':'adaptive.comparison_algorithm';
        $original=config('adaptive.algorithms');
        $baseDef=$original[$alg];
        $baseVersion=$baseDef['version'];
        $baseHash=$this->digest($baseDef['configuration']);
        $this->assertSame($alg,config($selector));
        $archived=$this->archived($alg);
        $profiles=ExperimentRunner::load(base_path('research/ai_computational/datasets'));
        $this->assertCount(1480,$profiles);
        $this->artisan('migrate',['--force'=>true])->assertSuccessful();
        app(AdaptiveAlgorithmRegistry::class)->syncConfiguredVersions();
        $instructor=User::factory()->create(['name'=>'Synthetic instructor','email'=>'phase4-instructor@example.invalid']);
        $student=User::factory()->create(['name'=>'Synthetic learner','email'=>'phase4-student@example.invalid']);
        $variants=[];
        foreach ($plan['variants'] as $v) {
            if ($v['shard']!==$shard || !$v['valid']) continue;
            $def=$baseDef;
            $this->assertEquals($v['baseline'],data_get($def['configuration'],$v['path']));
            data_set($def['configuration'],$v['path'],$v['value']);
            $def['name']='Research '.$v['id'];
            $def['version']='phase4-1.0.0-'.$v['id'];
            $def['status']='experimental';
            $def['is_baseline']=false;
            $variants[]=[$v,$def,$this->digest($def['configuration'])];
        }
        if (!$robust) $this->assertNotEmpty($variants);
        $f=fopen($out.'/raw.csv','xb');
        $b=fopen($out.'/baseline_recheck.csv','xb');
        $this->assertNotFalse($f);$this->assertNotFalse($b);
        fputcsv($f,self::HEADER,',','"','',"\n");
        fputcsv($b,['case_id','algorithm','matches_archive','status','final_action','target','centroid','selected','rules','reason'],',','"','',"\n");
        $mismatch=0;$attempts=0;
        try {
            foreach ($profiles as $p) {
                $old=$archived[$p['case_id']];
                $level=DB::transactionLevel();
                DB::beginTransaction();
                try {
                    $fixture=ExperimentFixture::build($p,$instructor,$student);
                    $baseline=$this->decide($fixture,$class,$alg,$baseVersion);
                    $matches=$this->baselineMatches($baseline,$old);
                    $mismatch+=(int)!$matches;
                    fputcsv($b,[$p['case_id'],$alg,(int)$matches,$baseline['status'],$baseline['final_action'],
                        $baseline['target_concept_code'],$baseline['centroid'],$baseline['selected_fuzzy_output'],
                        $baseline['fired_rule_ids'],$baseline['reason_code']],',','"','',"\n");
                    if (!$robust) {
                        foreach ($variants as [$v,$def,$hash]) {
                            $code=$v['id'];
                            // Only one extra definition is visible to the registry during this call.
                            // Baseline definitions stay byte-for-byte unchanged in runtime config.
                            config(['adaptive.algorithms'=>$original+[$code=>$def],$selector=>$code]);
                            try {
                                $actual=$this->decide($fixture,$class,$code,$def['version']);
                            } finally {
                                config(['adaptive.algorithms'=>$original,$selector=>$alg]);
                            }
                            $this->write($f,$p,$old,$actual,['base_algorithm'=>$alg,'algorithm_code'=>$code,
                                'algorithm_version'=>$def['version'],'variant_id'=>$code,'parameter'=>$v['path'],
                                'delta'=>$v['delta'],'old_value'=>$v['baseline'],'new_value'=>$v['value'],
                                'input_hash'=>$this->digest($p),'config_hash'=>$hash]);
                            $attempts++;
                        }
                    }
                } finally {
                    DB::rollBack($level);
                }
                if (!$robust) continue;
                foreach ($plan['perturbations'] as $field=>$deltas) foreach ($deltas as $delta) {
                    $q=$p;$val=$p[$field];$new='';
                    $meta=['base_algorithm'=>$alg,'algorithm_code'=>$alg,'algorithm_version'=>$baseVersion,
                        'variant_id'=>'noise-'.$field.'-'.($delta>0?'plus':'minus').abs($delta),
                        'parameter'=>$field,'delta'=>$delta,'old_value'=>$val,'config_hash'=>$baseHash];
                    if ($val==='') {
                        $actual=['status'=>'not_applicable','engine_called'=>0];
                    } else {
                        $isScore=str_contains($field,'mastery');
                        $numeric=$isScore?min(100,max(0,(float)$val+$delta)):max(0,(int)$val+$delta);
                        $new=$isScore?number_format($numeric,2,'.',''):(string)$numeric;
                        $q[$field]=$new;
                        if ($new===$val) {
                            $actual=['status'=>'clipped_no_change','engine_called'=>0];
                        } else {
                            $l=DB::transactionLevel();DB::beginTransaction();
                            try {
                                $fix=ExperimentFixture::build($q,$instructor,$student);
                                $actual=$this->decide($fix,$class,$alg,$baseVersion);
                            } catch (Throwable $e) {
                                $actual=['status'=>'failed','engine_called'=>0,'error_class'=>get_class($e)];
                            } finally {
                                DB::rollBack($l);
                            }
                        }
                    }
                    $meta['new_value']=$new;$meta['input_hash']=$this->digest($q);
                    $this->write($f,$p,$old,$actual,$meta);$attempts++;
                }
            }
        } finally {
            config(['adaptive.algorithms'=>$original,$selector=>$alg]);
            fclose($f);fclose($b);
        }
        $this->assertSame($original,config('adaptive.algorithms'));
        $expected=1480*($robust?array_sum(array_map('count',$plan['perturbations'])):count($variants));
        $this->assertSame($expected,$attempts);
        $this->assertSame(0,$mismatch,'Baseline replay mismatch; do not interpret changes.');
        fwrite(STDOUT,"PHASE4_SHARD ".$shard." recorded=".$attempts." baseline_matches=1480\n");
    }
}
