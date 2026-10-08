<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Services/LoginRateLimiter.php';
require_once __DIR__ . '/../src/Services/AvatarUploadService.php';
require_once __DIR__ . '/../src/Services/GoogleCredentialValidator.php';
require_once __DIR__ . '/../src/Models/LogModel.php';
require_once __DIR__ . '/../src/Models/SettingModel.php';
require_once __DIR__ . '/../src/Controllers/TelegramController.php';

final class SecurityRegressionTest extends TestCase
{
    private function users(): array
    {
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec('CREATE TABLE user (id TEXT, username TEXT, password_hash TEXT, role INTEGER, status INTEGER, is_deleted INTEGER, auth_key TEXT)');
        $db->exec('CREATE TABLE profile (user_id TEXT, prefix_name TEXT, first_name TEXT, last_name TEXT, avatar TEXT)');
        $db->prepare('INSERT INTO user VALUES (?,?,?,?,?,?,NULL)')->execute(['u1','alice',password_hash('initial-test-password', PASSWORD_DEFAULT),1,10,0]);
        $db->exec("INSERT INTO profile VALUES ('u1','','Alice','','')");
        return [$db, new User($db)];
    }
    public function testTokenIsHashedAndLegacyOrTamperedTokensAreRejected(): void
    {
        [$db,$user] = $this->users();
        $result = $user->login('alice', 'initial-test-password');
        self::assertTrue($result['success']);
        self::assertNotSame($result['token'], $db->query('SELECT auth_key FROM user')->fetchColumn());
        self::assertSame(32, strlen($db->query('SELECT auth_key FROM user')->fetchColumn()));
        self::assertSame('u1', $user->validateToken($result['token'])['id']);
        self::assertFalse($user->validateToken(str_repeat('a',32)));
        self::assertFalse($user->validateToken($result['token'] . 'a'));
    }
    public function testExpiredAndFutureTokensAreRejected(): void
    {
        self::assertFalse(SessionSecurity::valid(SessionSecurity::issue(time() - SessionSecurity::TTL - 1)));
        self::assertFalse(SessionSecurity::valid(SessionSecurity::issue(time() + 60)));
    }
    public function testPasswordChangeAndLogoutRevokeSession(): void
    {
        [$db,$user] = $this->users();
        $token = $user->login('alice','initial-test-password')['token'];
        self::assertFalse($user->changePassword('u1','initial-test-password','short'));
        self::assertTrue($user->changePassword('u1','initial-test-password','changed-test-password'));
        self::assertFalse($user->validateToken($token));
        $new = $user->login('alice','changed-test-password')['token'];
        $user->revokeSession('u1');
        self::assertFalse($user->validateToken($new));
    }
    public function testDeletedAndDisabledAccountsCannotAuthenticate(): void
    {
        [$db,$user] = $this->users();
        $token = $user->login('alice','initial-test-password')['token'];
        $user->deleteUser('u1');
        self::assertFalse($user->validateToken($token));
        self::assertFalse($user->login('alice','initial-test-password')['success']);
        $db->exec('UPDATE user SET is_deleted=0');
        $token = $user->login('alice','initial-test-password')['token'];
        $user->toggleStatus('u1',0);
        self::assertFalse($user->validateToken($token));
        self::assertFalse($user->login('alice','initial-test-password')['success']);
    }
    public function testLoginErrorsDoNotEnumerateUsers(): void
    {
        [, $user] = $this->users();
        self::assertSame($user->login('alice','wrong-password')['message'], $user->login('unknown','wrong-password')['message']);
    }
    public function testLoginRateLimitAndSuccessReset(): void
    {
        $directory = sys_get_temp_dir() . '/vengg-limit-test-' . bin2hex(random_bytes(8));
        $limiter = new LoginRateLimiter($directory);
        try {
            for ($i=0; $i<10; $i++) self::assertTrue($limiter->attempt('127.0.0.1','alice'));
            self::assertFalse($limiter->attempt('127.0.0.1','ALICE'));
            $limiter->succeeded('127.0.0.1','alice');
            self::assertTrue($limiter->attempt('127.0.0.1','alice'));
        } finally { foreach (glob($directory . '/*') as $file) unlink($file); rmdir($directory); }
    }
    public function testLoginRateLimitFollowsAccountAcrossIpAddresses(): void
    {
        $directory = sys_get_temp_dir() . '/vengg-limit-test-' . bin2hex(random_bytes(8));
        $limiter = new LoginRateLimiter($directory);
        try {
            for ($i = 0; $i < 10; $i++) {
                self::assertTrue($limiter->attempt('192.0.2.' . ($i + 1), 'id:42'));
            }
            self::assertFalse($limiter->attempt('198.51.100.7', 'id:42'));
            self::assertTrue($limiter->attempt('198.51.100.7', 'id:43'));
        } finally {
            foreach (glob($directory . '/*') as $file) unlink($file);
            rmdir($directory);
        }
    }
    public function testAvatarRejectsFakeImageAndTraversalNames(): void
    {
        $file = tempnam(sys_get_temp_dir(),'vengg-image-');
        file_put_contents($file, '<?php echo "payload";');
        try {
            try { AvatarUploadService::validate($file,'photo.jpg'); self::fail('Fake image accepted'); }
            catch (RuntimeException $error) { self::assertFalse(AvatarUploadService::safeStoredName('../../config/database.local.php')); }
            self::assertTrue(AvatarUploadService::safeStoredName('avatar_abc_123.png'));
        } finally { unlink($file); }
    }
    public function testGoogleCredentialsRejectUntrustedTokenEndpoint(): void
    {
        $this->expectException(RuntimeException::class);
        GoogleCredentialValidator::validate(json_encode(['type'=>'service_account','client_email'=>'test@demo.iam.gserviceaccount.com','private_key'=>'invalid','token_uri'=>'http://127.0.0.1/private']));
    }
    public function testValidGoogleServiceAccountKeyIsAccepted(): void
    {
        $options=['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA];
        if (is_file('C:/xampp/php/extras/openssl/openssl.cnf')) $options['config']='C:/xampp/php/extras/openssl/openssl.cnf';
        $key=openssl_pkey_new($options);self::assertNotFalse($key);
        openssl_pkey_export($key,$private,null,$options);
        $validated=GoogleCredentialValidator::validate(json_encode(['type'=>'service_account','client_email'=>'test@demo.iam.gserviceaccount.com','private_key'=>$private,'token_uri'=>'https://oauth2.googleapis.com/token','universe_domain'=>'googleapis.com']));
        self::assertSame('test@demo.iam.gserviceaccount.com',$validated['client_email']);
    }
    public function testOverlongPasswordsAreRejected(): void
    {
        self::assertFalse(SessionSecurity::passwordAllowed(str_repeat('a',73)));
        self::assertTrue(SessionSecurity::passwordAllowed(str_repeat('a',12)));
    }
    public function testLastActiveAdministratorCannotBeDeletedOrDisabled(): void
    {
        [$db,$user] = $this->users();
        $db->exec('UPDATE user SET role=9');
        self::assertFalse($user->deleteUser('u1'));
        self::assertFalse($user->toggleStatus('u1',0));
        self::assertSame(0,(int)$db->query('SELECT is_deleted FROM user')->fetchColumn());
        self::assertSame(10,(int)$db->query('SELECT status FROM user')->fetchColumn());
    }
    public function testLogsRedactSecretsAndBankAccountFields(): void
    {
        [$db] = $this->users();
        $db->exec('CREATE TABLE system_logs (user_id TEXT, action TEXT, module TEXT, description TEXT, old_data TEXT, new_data TEXT)');
        (new LogModel($db))->addLog('u1','TEST','SECURITY','test',null,['password'=>'test secret','settings'=>['bot_token'=>'test token','privateKey'=>'test key'],'bank_account'=>'test bank','bank_comment'=>'test bank note','phone'=>'test phone','safe'=>'kept']);
        $data = json_decode($db->query('SELECT new_data FROM system_logs')->fetchColumn(),true);
        self::assertSame('[REDACTED]',$data['password']);
        self::assertSame('[REDACTED]',$data['settings']['bot_token']);
        self::assertSame('[REDACTED]',$data['settings']['privateKey']);
        self::assertSame('[REDACTED]',$data['bank_account']);
        self::assertSame('[REDACTED]',$data['bank_comment']);
        self::assertSame('[REDACTED]',$data['phone']);
        self::assertSame('kept',$data['safe']);
    }
    public function testTelegramTokenIsHiddenAndOnlyClearedExplicitly(): void
    {
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec('CREATE TABLE telegram_settings (id INTEGER PRIMARY KEY, bot_token TEXT, chat_id TEXT, notify_confirmed INTEGER, notify_change_request INTEGER, notify_approval INTEGER)');
        $db->exec("INSERT INTO telegram_settings VALUES (1, 'fixture-token', '123', 1, 1, 1)");
        $db->exec('CREATE TABLE telegram_notify_times (send_time TEXT, notify_day INTEGER, status INTEGER)');
        $model = new SettingModel($db);
        ob_start();
        (new TelegramController($db, $model))->getSettings();
        $response = json_decode(ob_get_clean(), true);
        self::assertArrayNotHasKey('bot_token', $response);
        self::assertTrue($response['has_bot_token']);

        $settings = ['bot_token' => '', 'chat_id' => '123', 'notify_confirmed' => true,
            'notify_change_request' => true, 'notify_approval' => true, 'notify_times' => []];
        self::assertTrue($model->updateTelegramSettings($settings));
        self::assertSame('fixture-token', $db->query('SELECT bot_token FROM telegram_settings')->fetchColumn());
        self::assertTrue($model->updateTelegramSettings($settings + ['clear_bot_token' => true]));
        self::assertSame('', $db->query('SELECT bot_token FROM telegram_settings')->fetchColumn());
        $settings['bot_token'] = 'replacement-token';
        self::assertTrue($model->updateTelegramSettings($settings));
        self::assertSame('replacement-token', $db->query('SELECT bot_token FROM telegram_settings')->fetchColumn());
    }
}
