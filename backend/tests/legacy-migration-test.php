<?php
declare(strict_types=1);
require_once __DIR__ . '/../../deploy/legacy-migration/Planner.php';

function migrationFixture(): array
{
    $src = array_fill_keys(['user','profile','fname','dep','group','ven_name','ven_name_sub','ven_user','ven_com','ven','ven_change','sign_name','line'],[]);
    $stamp = '2026-11-01 10:00:00';
    foreach ([1,2,3,4] as $id) {
        $src['user'][] = ['id'=>$id,'username'=>"legacy$id",'password_hash'=>password_hash('FixturePassword123!',PASSWORD_BCRYPT,['cost'=>4]),'email'=>null,'role'=>$id===1 ? 9 : 1,'status'=>$id===4 ? 77 : 10,'created_at'=>$stamp,'updated_at'=>null];
        $src['profile'][] = ['id'=>$id,'user_id'=>(string)$id,'fname'=>'นาย','name'=>"Fixture$id",'sname'=>'Test','img'=>'old.png','dep'=>'Clerk','workgroup'=>'Office','birthday'=>null,'created_at'=>$stamp,'updated_at'=>null];
    }
    $src['fname'] = [['id'=>1,'name'=>'นาย']];
    $src['dep'] = [['id'=>1,'name'=>'Clerk']];
    $src['group'] = [['id'=>1,'name'=>'Office']];
    $src['ven_name'] = [['id'=>24,'name'=>'Test duty','name_full'=>'Test duty full','DN'=>'กลางวัน','srt'=>1,'st'=>1]];
    $src['ven_name_sub'] = [['id'=>100,'ven_name_id'=>24,'name'=>'Clerk','price'=>420,'color'=>'#ffffff','srt'=>1,'st'=>1]];
    $src['ven_com'] = [['id'=>1000,'ven_com_num'=>'1/2569','ven_com_date'=>'2026-11-01','ven_month'=>'2026-11','vn_id'=>24,'status'=>'1','comment'=>null,'ref'=>'fixture','create_at'=>$stamp]];
    foreach ([[1,1,4,'10'],[2,2,4,'11'],[3,2,4,'10'],[4,1,1,'11'],[5,3,2,'10'],[6,3,77,'12']] as [$id,$user,$status,$day]) {
        $src['ven'][] = ['id'=>$id,'user_id'=>(string)$user,'ven_com_id'=>'[1000]','ven_com_idb'=>'1000','vn_id'=>24,'vns_id'=>100,'DN'=>'กลางวัน','price'=>'420.00','ven_date'=>"2026-11-$day",'ven_time'=>'08:30:01','ven_month'=>'2026-11','status'=>$status,'create_at'=>$stamp];
    }
    $src['ven_change'] = [
        ['id'=>'CH1','ven_id1_old'=>1,'ven_id1'=>3,'ven_id2_old'=>2,'ven_id2'=>4,'user_id1'=>1,'user_id2'=>2,'status'=>'1','create_at'=>$stamp],
        ['id'=>'CH2','ven_id1_old'=>3,'ven_id1'=>5,'ven_id2_old'=>null,'ven_id2'=>null,'user_id1'=>2,'user_id2'=>3,'status'=>'2','create_at'=>'2026-11-02 10:00:00'],
        ['id'=>'CH3','ven_id1_old'=>9999,'ven_id1'=>6,'ven_id2_old'=>null,'ven_id2'=>null,'user_id1'=>1,'user_id2'=>3,'status'=>'77','create_at'=>'2026-11-03 10:00:00'],
    ];
    $config = ['source_database'=>'fixture','username_overrides'=>[],'shift_periods'=>['กลางวัน'=>'08.30-16.30'],'restore_subs'=>[]];
    $target = ['system_settings'=>[['id'=>1,'user_options'=>'{"prefixes":["นาง"],"custom":"keep"}']]];
    return [$src,$target,$config];
}
function migrationCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function migrationUnitTests(): void
{
    [$src,$target,$config] = migrationFixture();
    $p = new LegacyMigrationPlanner(); $p->build($src,$target,$config);
    migrationCheck(!$p->errors,implode('; ',$p->errors));
    migrationCheck(count($p->rows['ven_schedule'])===2,'Collapse history without importing cancelled rows');
    migrationCheck($p->maps['ven_schedule']['1']===$p->maps['ven_schedule']['5'],'Repeated transfer maps to the same duty');
    migrationCheck($p->maps['ven_schedule']['2']===$p->maps['ven_schedule']['4'],'Swap second slot maps correctly');
    migrationCheck(count($p->rows['ven_change'])===2,'Cancelled change archived only');
    migrationCheck($p->rows['ven_change'][1]['status']===0 && $p->rows['ven_change'][1]['is_swap']===0,'Pending handover maps to new status 0');
    migrationCheck($p->rows['ven_change'][0]['is_swap']===1,'Swap distinction preserved');
    migrationCheck($p->rows['ven_com'][0]['ven_com_days']==='10,11','Days derived without cancelled row');
    migrationCheck($p->rows['user'][3]['is_deleted']===1 && $p->rows['user'][3]['status']===0,'Deleted accounts stay disabled');
    migrationCheck($p->rows['user'][0]['password_hash']===$src['user'][0]['password_hash'],'Hash not rehashed');
    migrationCheck(json_decode($p->rows['system_settings'][0]['user_options'],true)['custom']==='keep','Other settings retained');
    $broken=$src; $broken['ven_change'][0]['ven_id1']=4;
    $p->build($broken,$target,$config); migrationCheck((bool)$p->errors,'Wrong swap reference blocked');
    $broken=$src; $broken['ven_name_sub']=[];
    $p->build($broken,$target,$config); migrationCheck((bool)$p->errors,'Missing duty blocked');
    $fixed=$config; $fixed['restore_subs']=$src['ven_name_sub'];
    $p->build($broken,$target,$fixed); migrationCheck(!$p->errors,'Reviewed duty recovery accepted');
    $broken=$src; $broken['ven_change']=[];
    $p->build($broken,$target,$config); migrationCheck((bool)$p->errors,'Orphan pending schedule blocked');
    $broken=$src; $extra=$src['ven'][4];$extra['id']=99;$extra['ven_date']='2026-11-20';$broken['ven'][]=$extra;
    $fixed=$config;$fixed['preserve_locked_pending']=['99'=>'Preserve unresolved legacy state without inventing approval.'];
    $p->build($broken,$target,$fixed); migrationCheck(!$p->errors && (bool)array_filter($p->warnings,static fn($v)=>str_contains($v,'locked legacy pending schedule:99')),'Reviewed orphan preserved locked');
    $fixed=$config;$fixed['preserve_locked_pending']=['123'=>'Typo'];
    $p->build($src,$target,$fixed); migrationCheck((bool)$p->errors,'Stale orphan exception blocked');
    $broken=$src; $broken['ven'][0]['status']=1;
    $p->build($broken,$target,$config); migrationCheck((bool)$p->errors,'Multiple active owners blocked');
    $broken=$src; $broken['user'][0]['created_at']='0000-00-00 00:00:00';
    $p->build($broken,$target,$config); migrationCheck((bool)$p->errors,'Invalid timestamp blocked');
    $broken=$src; $broken['ven'][4]['price']='999.00';
    $p->build($broken,$target,$config); migrationCheck((bool)$p->errors,'Historical payment mismatch blocked');
    $broken=$src; $broken['ven_change'][]=['id'=>'LOOP','ven_id1_old'=>5,'ven_id1'=>1,'ven_id2_old'=>null,'ven_id2'=>null,'user_id1'=>3,'user_id2'=>1,'status'=>'1','create_at'=>'2026-11-04 10:00:00'];
    $p->build($broken,$target,$config); migrationCheck((bool)$p->errors,'Cyclic history blocked without hanging');
    $collision=$target; $collision['user']=[['id'=>'existing','username'=>'legacy1']];
    $p->build($src,$collision,$config); migrationCheck((bool)$p->errors,'Existing username blocked');
    $fixed=$config; $fixed['username_overrides']['1']='reviewed_legacy1';
    $p->build($src,$collision,$fixed); migrationCheck(!$p->errors,'Reviewed username rename accepted');
    echo "PASS: legacy migration planner scenarios\n";
}
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) migrationUnitTests();
