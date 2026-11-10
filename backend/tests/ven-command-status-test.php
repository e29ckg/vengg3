<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
$backendRoot = getenv('VENGG_TEST_BACKEND_ROOT') ?: dirname(__DIR__);
require_once $backendRoot.'/src/Models/SettingModel.php';
require_once $backendRoot.'/src/Controllers/VenCommandController.php';
require_once $backendRoot.'/src/Services/TelegramService.php';

function statusCheck(bool $condition, string $description): void {
    if (!$condition) throw new RuntimeException($description);
}
function statusCall(VenCommandController $controller, mixed $body): array {
    http_response_code(200);ob_start();
    try {$controller->toggleStatus($body);$raw=ob_get_contents();} finally {ob_end_clean();}
    return [http_response_code(),json_decode($raw,true,512,JSON_THROW_ON_ERROR)];
}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE ven_com (id INTEGER PRIMARY KEY,status TEXT)');
$db->exec("INSERT INTO ven_com VALUES(1,'0')");
$db->exec('CREATE TABLE telegram_settings (id INTEGER PRIMARY KEY,bot_token TEXT,chat_id TEXT,notify_confirmed INTEGER)');
$db->exec("INSERT INTO telegram_settings VALUES(1,'fixture-not-a-real-token','fixture-chat',1)");

// Exercise the real service with cURL disabled: no external message can be sent.
if (!function_exists('curl_init')) {
    [$code,$body]=statusCall(new VenCommandController(new SettingModel($db),$db),['id'=>1,'status'=>1]);
    statusCheck($code===200 && ($body['success'] ?? false) && $body['notification_sent']===false,'Missing cURL must not fail a persisted status update');
    statusCheck((int)$db->query('SELECT status FROM ven_com WHERE id=1')->fetchColumn()===1,'Status not persisted when notification unavailable');
}

$calls=0;
$factory=static function($connection) use (&$calls): object {
    return new class($calls) {
        private $calls;
        public function __construct(&$calls) {$this->calls=&$calls;}
        public function sendMessage($message,$type): bool {$this->calls++;throw new Error('Simulated unavailable transport');}
    };
};
$controller=new VenCommandController(new SettingModel($db),$db,$factory);
[$code,$body]=statusCall($controller,['id'=>1,'status'=>1]);
statusCheck($code===200 && ($body['success'] ?? false) && $body['notification_sent']===false,'Notification exceptions must not fail a persisted update');
statusCheck($calls===1,'Notification path not exercised');
[$code,$body]=statusCall($controller,['id'=>1,'status'=>0]);
statusCheck($code===200 && ($body['success'] ?? false) && $calls===1,'Disabling must not send confirmation');
statusCheck((int)$db->query('SELECT status FROM ven_com WHERE id=1')->fetchColumn()===0,'Disable did not persist');
foreach ([null,[],['id'=>[],'status'=>1],['id'=>1,'status'=>77],['id'=>1,'status'=>true],['id'=>0,'status'=>1]] as $invalid) {
    [$code,$body]=statusCall($controller,$invalid);
    statusCheck($code===400 && isset($body['error']),'Invalid input must return 400');
    statusCheck((int)$db->query('SELECT status FROM ven_com WHERE id=1')->fetchColumn()===0,'Invalid input changed status');
}
$failing=new class {
    public function toggleVenCommandStatus($id,$status): bool {return false;}
};
[$code,$body]=statusCall(new VenCommandController($failing,$db,$factory),['id'=>1,'status'=>1]);
statusCheck($code===500 && $calls===1,'Failed update must not notify or report success');
echo "PASS: command status persists despite missing cURL/notification failure; disable, invalid input and update failure verified.\n";
