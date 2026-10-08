<?php
if (PHP_SAPI !== 'cli' || getenv('SECURITY_TEST_ALLOW_MUTATIONS') !== '1') exit(2);
$fixture=getenv('SECURITY_TEST_FIXTURE');
$root=dirname(__DIR__,3);
$pdo=new PDO('mysql:host=127.0.0.1;port=33307','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE vengg_security_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE vengg_security_test');
$schema=preg_replace('/^DROP TABLE IF EXISTS `[^`]+`;\s*$/m','',file_get_contents($root.'/database.sql'));
$pdo->exec($schema);
$password=bin2hex(random_bytes(24));
$pdo->exec("CREATE USER 'audit_app'@'localhost' IDENTIFIED BY ".$pdo->quote($password));
$pdo->exec("GRANT ALL PRIVILEGES ON vengg_security_test.* TO 'audit_app'@'localhost'");
$insert=$pdo->prepare('INSERT INTO user (id,username,password_hash,role,status) VALUES (?,?,?,?,10)');
$profile=$pdo->prepare('INSERT INTO profile (user_id,first_name,last_name) VALUES (?, ?, ?)');
foreach(['admin'=>9,'viewer'=>1,'director'=>2,'finance'=>3,'victim'=>1] as $name=>$role) {
    $insert->execute([$name,$name,password_hash('Audit-test-password-2026!',PASSWORD_DEFAULT),$role]);
    $profile->execute([$name,$name,'audit fixture']);
}
$pdo->exec("INSERT INTO ven_name (id,name,name_full,dn,srt) VALUES (991,'Audit Duty','Audit Duty','กลางวัน',1)");
$pdo->exec("INSERT INTO ven_name_sub (id,name,ven_name_id,price,srt) VALUES (991,'Audit Role',991,0,1)");
$pdo->exec("INSERT INTO ven_com (id,com_num,com_date,ven_month,status,ven_name_id,ven_com_days) VALUES (991,'Audit','2026-10-01','2026-10','1',991,'[]')");
$pdo->exec("INSERT INTO ven_schedule (id,user_id,ven_com_id,ven_name_sub_id,ven_date,status) VALUES (1,'viewer',991,991,'2026-10-15',1),(2,'finance',991,991,'2026-10-16',1)");
$pdo->exec("INSERT INTO ven_user (user_id,ven_name_sub_id) VALUES ('viewer',991),('finance',991)");
$pdo->exec('UPDATE system_settings SET allow_swap=0');
file_put_contents($fixture.'/runtime.json',json_encode(['DB_HOST'=>'127.0.0.1','DB_PORT'=>'33307','DB_NAME'=>'vengg_security_test','DB_USER'=>'audit_app','DB_PASS'=>$password]));
file_put_contents($fixture.'/fake.docx','not a document');
file_put_contents($fixture.'/fake-google.json',json_encode(['type'=>'service_account','client_email'=>'test@demo.iam.gserviceaccount.com','private_key'=>'invalid','token_uri'=>'http://127.0.0.1/private']));
file_put_contents($fixture.'/pixel.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jZ0cAAAAASUVORK5CYII='));
echo "Disposable fixture prepared\n";
