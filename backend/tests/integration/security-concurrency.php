<?php
if (PHP_SAPI !== 'cli' || getenv('SECURITY_TEST_ALLOW_MUTATIONS') !== '1' || getenv('DB_NAME') !== 'vengg_security_test' || getenv('DB_PORT') !== '33307') exit(2);
require_once dirname(__DIR__,2) . '/src/Models/VenTransferModel.php';
$fixture=getenv('SECURITY_TEST_FIXTURE');
$db=new PDO('mysql:host=127.0.0.1;port=33307;dbname=vengg_security_test',getenv('DB_USER'),getenv('DB_PASS'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if (($argv[1] ?? '')==='worker') {
    $deadline=microtime(true)+5;
    while(!is_file($fixture.'/start-concurrency')) { if(microtime(true)>$deadline) exit(3); clearstatcache(true,$fixture.'/start-concurrency'); usleep(1000); }
    echo json_encode((new VenTransferModel($db))->performTransfer('viewer',1,'finance',0,null));
    exit;
}
$db->exec('UPDATE system_settings SET allow_swap=1, advance_swap_days=0');
$date=(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->modify('+7 days')->format('Y-m-d');
$db->prepare("UPDATE ven_schedule SET user_id='viewer',status=1,ven_date=? WHERE id=1")->execute([$date]);
$db->exec('DELETE FROM ven_change');
$processes=[];
for($i=0;$i<2;$i++) {
    $process=proc_open([PHP_BINARY,__FILE__,'worker'],[0=>['pipe','r'],1=>['pipe','w'],2=>['file','NUL','w']],$pipes,null,null,['bypass_shell'=>true]);
    fclose($pipes[0]);$processes[]=[$process,$pipes[1]];
}
file_put_contents($fixture.'/start-concurrency','go');
$results=[];
foreach($processes as [$process,$pipe]) { $results[]=json_decode(stream_get_contents($pipe),true);fclose($pipe);if(proc_close($process)!==0) throw new RuntimeException('Concurrent worker did not finish'); }
$success=count(array_filter($results,static fn($result)=>($result['success']??false)===true));
$requests=(int)$db->query('SELECT COUNT(*) FROM ven_change')->fetchColumn();
if($success!==1 || $requests!==1 || count(array_filter($results,'is_array'))!==2) throw new RuntimeException('Concurrent transfer produced duplicate or missing request; success='.$success.' requests='.$requests.' codes='.json_encode(array_column($results,'code')));
file_put_contents($fixture.'/concurrency-results.json',json_encode(['workers'=>2,'successful_transfers'=>$success,'requests_created'=>$requests]));
echo "PASS: two concurrent MySQL transfers create exactly one request\n";
