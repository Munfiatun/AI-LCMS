<?php

declare(strict_types=1);

namespace Tests\Research\AIComputational;

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

final class PerformanceBenchmarkTest extends TestCase
{
    private const ALGS=['rule-based-v1'=>[RuleBasedAdaptiveEngine::class,'1.0.0'],
        'fuzzy-v2'=>[FuzzyAdaptiveEngine::class,'2.0.0-experimental']];

    public function test_benchmark_one_isolated_batch(): void
    {
        ExperimentFixture::assertIsolated();
        $out=getenv('AI_PHASE6_BATCH_OUTPUT');
        $alg=getenv('AI_PHASE6_ALGORITHM');
        $stage=getenv('AI_PHASE6_STAGE');
        $batch=getenv('AI_PHASE6_BATCH');
        $position=getenv('AI_PHASE6_POSITION');
        $this->assertIsString($out);$this->assertDirectoryExists($out);
        $this->assertArrayHasKey($alg,self::ALGS);
        $this->assertContains($stage,['warmup','measured']);
        $this->assertMatchesRegularExpression('/^[1-9][0-9]*$/D',$batch);
        $this->assertContains($position,['1','2']);
        $this->assertFileDoesNotExist($out.'/observations.csv');
        [$class,$version]=self::ALGS[$alg];
        $archive=base_path('research/ai_computational/results/phase3/run-45928359249/phase2_replay');
        $manifest=json_decode(file_get_contents($archive.'/run_manifest.json'),true,512,JSON_THROW_ON_ERROR);
        $this->assertSame($manifest['results_sha256'],hash_file('sha256',$archive.'/comparative_results.csv'));
        $this->assertSame($manifest['adaptive_file_sha256'],hash_file('sha256',config_path('adaptive.php')));
        $this->assertSame($manifest['input_sha256']['dataset_random.csv'],hash_file('sha256',base_path('research/ai_computational/datasets/dataset_random.csv')));
        $cfg=config('adaptive.algorithms.'.$alg);
        $hash=hash('sha256',json_encode($cfg['configuration'],JSON_THROW_ON_ERROR|JSON_PRESERVE_ZERO_FRACTION));
        $this->assertSame($manifest['algorithm_config_sha256'][$alg],$hash);
        $this->assertSame($version,$cfg['version']);
        $profiles=array_values(array_filter(ExperimentRunner::load(base_path('research/ai_computational/datasets')),fn($p)=>$p['dataset']==='D4'));
        $this->assertCount(1000,$profiles);
        $old=[];
        $f=fopen($archive.'/comparative_results.csv','rb');$h=fgetcsv($f,0,',','"','');
        while (($v=fgetcsv($f,0,',','"',''))!==false) {
            $r=array_combine($h,$v);
            if ($r['dataset']==='D4'&&$r['algorithm_code']===$alg) $old[$r['case_id']]=$r;
        }
        fclose($f);$this->assertCount(1000,$old);
        $this->artisan('migrate',['--force'=>true])->assertSuccessful();
        app(AdaptiveAlgorithmRegistry::class)->syncConfiguredVersions();
        $teacher=User::factory()->create(['name'=>'Synthetic teacher','email'=>'phase6-teacher@example.invalid']);
        $student=User::factory()->create(['name'=>'Synthetic student','email'=>'phase6-student@example.invalid']);
        $engine=app($class);
        $f=fopen($out.'/observations.csv','xb');$this->assertNotFalse($f);
        $header=['stage','batch','position','call_kind','case_id','seed','algorithm_code','algorithm_version','config_hash','fixture_hash',
            'engine_called','execution_ns','status','error_class','final_action','target_concept_code','reason_code','selected_fuzzy_output','centroid','fired_rule_ids','archive_match'];
        fputcsv($f,$header,',','"','',"\n");
        $counts=['calls'=>0,'success'=>0,'errors'=>0,'mismatches'=>0,'rows'=>0];
        $batchStart=hrtime(true);
        $memoryBefore=memory_get_usage(true);
        try {
            // Fixed 20-case per-process prewarm, separate from the three host warmup batches.
            foreach (['prewarm'=>array_slice($profiles,0,20),'batch'=>$profiles] as $kind=>$rows) {
                foreach ($rows as $p) {
                    $record=array_fill_keys($header,'');
                    $record=array_replace($record,['stage'=>$stage,'batch'=>$batch,'position'=>$position,'call_kind'=>$kind,
                        'case_id'=>$p['case_id'],'seed'=>$p['seed'],'algorithm_code'=>$alg,'algorithm_version'=>$version,
                        'config_hash'=>$hash,'fixture_hash'=>hash('sha256',json_encode($p,JSON_THROW_ON_ERROR)),
                        'engine_called'=>0,'status'=>'error','archive_match'=>0]);
                    $level=DB::transactionLevel();DB::beginTransaction();
                    try {
                        $fixture=ExperimentFixture::build($p,$teacher,$student);
                        $record['engine_called']=1;$counts['calls']++;
                        $start=hrtime(true);
                        try {
                            $decision=$engine->decide($fixture['student'],$fixture['concept']);
                        } finally {
                            $record['execution_ns']=hrtime(true)-$start;
                        }
                        $record['final_action']=$decision->action;
                        $record['target_concept_code']=$fixture['targets'][$decision->targetConceptId]??'';
                        $record['reason_code']=$decision->reasonCode;
                        if ($alg==='fuzzy-v2') {
                            $record['selected_fuzzy_output']=$decision->metadata['selected_output']??'';
                            $record['centroid']=isset($decision->metadata['crisp_output'])?number_format((float)$decision->metadata['crisp_output'],4,'.',''):'';
                            $record['fired_rule_ids']=implode('|',array_column($decision->metadata['fired_rules']??[],'rule'));
                        }
                        if ($decision->algorithmCode!==$alg||$decision->algorithmVersion!==$version) throw new RuntimeException('Algorithm identity mismatch');
                        $match=true;
                        foreach (['final_action','target_concept_code','reason_code','selected_fuzzy_output','centroid','fired_rule_ids'] as $key) {
                            $match=$match&&$record[$key]===$old[$p['case_id']][$key];
                        }
                        $record['archive_match']=(int)$match;
                        $record['status']='success';
                        $counts['success']++;
                        $counts['mismatches']+=(int)!$match;
                    } catch (Throwable $e) {
                        $record['error_class']=get_class($e);$counts['errors']++;
                    } finally {
                        DB::rollBack($level);
                    }
                    if (fputcsv($f,array_values($record),',','"','',"\n")===false) throw new RuntimeException('CSV write failed');
                    $counts['rows']++;
                }
            }
        } finally {
            fclose($f);
        }
        $meta=['stage'=>$stage,'batch'=>(int)$batch,'position'=>(int)$position,'algorithm'=>$alg,
            'pid'=>getmypid(),'php_version'=>PHP_VERSION,'laravel_version'=>app()->version(),
            'sqlite_version'=>DB::selectOne('select sqlite_version() as version')->version,
            'php_sapi'=>PHP_SAPI,'opcache_cli'=>ini_get('opcache.enable_cli'),'jit'=>ini_get('opcache.jit'),
            'xdebug_loaded'=>extension_loaded('xdebug'),'memory_limit'=>ini_get('memory_limit'),
            'memory_before_loop_allocated_bytes'=>$memoryBefore,'peak_php_allocated_bytes'=>memory_get_peak_usage(true),
            'peak_php_used_bytes'=>memory_get_peak_usage(false),'loop_wall_ns'=>hrtime(true)-$batchStart,
            'counts'=>$counts,'csv_sha256'=>hash_file('sha256',$out.'/observations.csv')];
        file_put_contents($out.'/batch_manifest.json',json_encode($meta,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT).PHP_EOL);
        fwrite(STDOUT,'PHASE6_BATCH '.json_encode($meta,JSON_THROW_ON_ERROR).PHP_EOL);
        $this->assertSame(1020,$counts['rows']);$this->assertSame(1020,$counts['calls']);
        $this->assertSame(0,$counts['errors']);$this->assertSame(0,$counts['mismatches']);
    }
}
