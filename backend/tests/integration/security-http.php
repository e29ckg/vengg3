<?php
// Run only against the disposable localhost fixture, never a production deployment.
if (PHP_SAPI !== 'cli' || getenv('SECURITY_TEST_ALLOW_MUTATIONS') !== '1' || getenv('SECURITY_TEST_BASE_URL') !== 'http://127.0.0.1:18099/') exit(2);
$base = getenv('SECURITY_TEST_BASE_URL');
$fixture = getenv('SECURITY_TEST_FIXTURE');
$pass = 'Audit-test-password-2026!';
$checks = [];
$avatars = [];
function request(string $route, string $method = 'GET', ?string $token = null, $body = null, array $extraHeaders = []): array {
    global $base;
    $curl = curl_init($base . '?route=' . $route);
    $headers = $extraHeaders;
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    if ($body !== null && !(is_array($body) && array_filter($body, static fn($value) => $value instanceof CURLFile))) {
        $headers[] = 'Content-Type: application/json';
        if (!is_string($body)) $body = json_encode($body);
    }
    curl_setopt_array($curl, [CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>$headers,CURLOPT_HEADER=>true]);
    if ($body !== null) curl_setopt($curl,CURLOPT_POSTFIELDS,$body);
    $response = curl_exec($curl);
    if ($response === false) throw new RuntimeException('HTTP test connection failed');
    $headerSize=curl_getinfo($curl,CURLINFO_HEADER_SIZE);
    $result=['status'=>curl_getinfo($curl,CURLINFO_HTTP_CODE),'headers'=>substr($response,0,$headerSize),'body'=>substr($response,$headerSize)];
    curl_close($curl); return $result;
}
function check(bool $result, string $label): void {
    global $checks;
    if (!$result) throw new RuntimeException('FAILED: ' . $label);
    $checks[]=$label; echo 'PASS: ' . $label . "\n";
}
function login(string $username, string $password): string {
    $r=request('auth/login','POST',null,['username'=>$username,'password'=>$password]);
    if ($r['status']!==200) throw new RuntimeException('Fixture login failed');
    return json_decode($r['body'],true)['token'];
}
try {
    $admin=login('admin',$pass); $viewer=login('viewer',$pass); $director=login('director',$pass); $finance=login('finance',$pass);
    check(request('auth/me')['status']===401,'anonymous token access rejected');
    check(request('auth/me','GET',str_repeat('a',32))['status']===401,'legacy bearer rejected');
    foreach (['admin/user/list','admin/templates/list','admin/program/status','admin/google_settings/get','admin/telegram_settings','admin/logs/get','admin/backup/sql','admin/backup/images'] as $route) {
        foreach ([$viewer,$director,$finance] as $token) check(request($route,'GET',$token)['status']===403,$route . ' rejects non-admin');
    }
    check(request('admin/templates/reset&kind=duty&ven_name_id=991','DELETE',$viewer)['status']===403,'non-admin template reset rejected');
    check(request('admin/program/update','POST',$viewer,['latest'=>str_repeat('a',40)])['status']===403,'non-admin updater execution rejected');
    check(request('admin/user/update','POST',$viewer,['id'=>'viewer','role'=>9,'status'=>10,'first_name'=>'viewer'])['status']===403,'ordinary user cannot grant itself admin role');
    check(request('user/profile/update','POST',$viewer,['first_name'=>'viewer','last_name'=>'audit','role'=>9])['status']===200,'self profile update is allowed');
    check(json_decode(request('auth/me','GET',$viewer)['body'],true)['role']==1,'extra role field cannot escalate profile permissions');
    check(request('get_commands&month=2026-10','GET',$viewer)['status']===403,'finance data protected');
    check(request('get_commands&month=2026-10','GET',$finance)['status']===200,'finance role allowed');
    check(request('admin/ven_user/allUsers','GET',$director)['status']===200,'director assignment directory allowed');
    check(!str_contains(request('admin/ven_user/allUsers','GET',$director)['body'],'bank_account'),'assignment directory does not disclose bank fields');
    check(!str_contains(request('users/list','GET',$director)['body'],'bank_account'),'report directory does not disclose bank fields');
    check(request('admin/user/delete','GET',$admin)['status']===405,'GET cannot mutate users');
    check(request('ven/list','GET',$viewer)['status']===400,'calendar requires a bounded month');
    check(request('ven/list&monthYear=2026-13','GET',$viewer)['status']===400,'calendar rejects invalid month');
    $calendar=json_decode(request('ven/list&monthYear=2026-10','GET',$viewer)['body'],true);
    check(is_array($calendar) && count($calendar)===2,'calendar accepts frontend monthYear and returns only that month');
    check(request('auth/login','POST',null,'{bad')['status']===400,'malformed JSON rejected');
    $oversized=request('auth/login','POST',null,str_repeat('a',1048577));
    check($oversized['status']===413,'oversized JSON rejected');
    check(request('auth/login','OPTIONS',null,null,['Origin: https://attacker.invalid'])['status']===403,'untrusted CORS preflight rejected');
    $cors=request('auth/login','OPTIONS',null,null,['Origin: http://localhost:5173']);
    check($cors['status']===204 && str_contains($cors['headers'],'Access-Control-Allow-Origin: http://localhost:5173'),'development CORS allowlist works');
    $telegramSettings=['bot_token'=>'fixture-token','chat_id'=>'123','notify_confirmed'=>true,'notify_change_request'=>true,'notify_approval'=>true,'notify_times'=>[]];
    check(request('admin/telegram_settings/update','POST',$admin,$telegramSettings)['status']===200,'admin can store Telegram token in isolated fixture');
    $telegram=json_decode(request('admin/telegram_settings','GET',$admin)['body'],true);
    check(!array_key_exists('bot_token',$telegram) && $telegram['has_bot_token']===true,'Telegram settings never return token value');
    $telegramSettings['bot_token']='';
    check(request('admin/telegram_settings/update','POST',$admin,$telegramSettings)['status']===200 && json_decode(request('admin/telegram_settings','GET',$admin)['body'],true)['has_bot_token']===true,'other setting updates preserve stored token');
    $telegramSettings['clear_bot_token']=true;
    check(request('admin/telegram_settings/update','POST',$admin,$telegramSettings)['status']===200 && json_decode(request('admin/telegram_settings','GET',$admin)['body'],true)['has_bot_token']===false,'explicit clear removes stored Telegram token');
    $settings=request('system_settings','GET',$viewer);
    check(str_contains(strtolower($settings['headers']),'x-content-type-options: nosniff') && str_contains(strtolower($settings['headers']),'cache-control: no-store'),'API security headers present');
    check(request('ven/transfer/perform','POST',$viewer,['schedule_id'=>2,'new_user_id'=>'finance','is_swap'=>0])['status']===403,'schedule ownership enforced');
    check(request('ven/transfer/perform','POST',$viewer,['schedule_id'=>1,'new_user_id'=>'finance','is_swap'=>0])['status']===403,'disabled transfer rule enforced server-side');
    $saved=request('admin/system_settings','POST',$admin,['system_name'=>'Audit Verified','advance_swap_days'=>3]);
    check($saved['status']===200 && (json_decode($saved['body'],true)['success']??false),'bulk security settings can be saved');
    request('admin/system_settings','POST',$admin,['allow_swap'=>1,'advance_swap_days'=>-1]);
    check((int)json_decode(request('system_settings','GET',$viewer)['body'],true)['allow_swap']===0,'invalid bulk setting rolls back prior writes');
    check(request('admin/user/delete','POST',$admin,['id'=>'admin'])['status']===409,'last administrator cannot be deleted');
    check(request('admin/user/status','POST',$admin,['id'=>'admin','status'=>0])['status']===409,'last administrator cannot be disabled');
    $templates=json_decode(request('admin/templates/list','GET',$admin)['body'],true)['templates'];
    $scope=array_values(array_filter($templates,static fn($row)=>$row['ven_name_id']===991));
    check(count($scope)===1 && !$scope[0]['custom'],'isolated template scope starts empty');
    $upload=['kind'=>'duty','ven_name_id'=>'991','template'=>new CURLFile(dirname(__DIR__,2).'/resources/templates/duty_report_form.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document','template.docx')];
    check(request('admin/templates/validate','POST',$viewer,$upload)['status']===403,'non-admin ZIP validation rejected');
    check(request('admin/templates/validate','POST',$admin,$upload)['status']===200,'server ZIP validation succeeds before browser inflation');
    check(request('admin/templates/upload','POST',$admin,$upload)['status']===200,'admin can upload valid DOCX');
    $download=request('documents/template&kind=duty&ven_name_id=991','GET',$viewer);
    check($download['status']===200 && substr($download['body'],0,2)==='PK','authenticated report uses DOCX template');
    $hash=hash('sha256',$download['body']);
    $upload['template']=new CURLFile($fixture.'/fake.docx','application/octet-stream','fake.docx');
    check(request('admin/templates/upload','POST',$admin,$upload)['status']===400,'fake DOCX upload rejected');
    check(hash('sha256',request('documents/template&kind=duty&ven_name_id=991','GET',$viewer)['body'])===$hash,'failed upload preserves current template');
    check(request('admin/templates/reset&kind=duty&ven_name_id=991','DELETE',$admin)['status']===200,'admin template reset works');
    check(request('user/profile/upload_avatar','POST',$viewer,['avatar'=>new CURLFile($fixture.'/fake.docx','image/jpeg','fake.jpg')])['status']===400,'fake avatar rejected');
    $image=request('user/profile/upload_avatar','POST',$viewer,['avatar'=>new CURLFile($fixture.'/pixel.png','image/png','pixel.png')]);
    check($image['status']===200,'real image accepted');
    $avatars[]=json_decode($image['body'],true)['avatar'];
    check(request('admin/google_settings/upload','POST',$admin,['credential_file'=>new CURLFile($fixture.'/fake-google.json','application/json','credentials.json')])['status']===400,'untrusted service-account token endpoint rejected');
    check(request('user/profile/password','POST',$viewer,['old_password'=>$pass,'new_password'=>'short'])['status']===400,'weak password rejected');
    check(request('user/profile/password','POST',$viewer,['old_password'=>$pass,'new_password'=>'Changed-audit-password!'])['status']===200,'password change succeeds');
    check(request('auth/me','GET',$viewer)['status']===401,'password change revokes prior token');
    $viewer=login('viewer','Changed-audit-password!');
    check(request('auth/logout','POST',$viewer,[])['status']===200,'server logout succeeds');
    check(request('auth/me','GET',$viewer)['status']===401,'logout token cannot be reused');
    $victim=login('victim',$pass);
    check(request('admin/user/delete','POST',$admin,['id'=>'victim'])['status']===200,'admin can delete ordinary account');
    check(request('auth/me','GET',$victim)['status']===401,'deleted account session revoked');
    check(request('auth/login','POST',null,['username'=>'victim','password'=>$pass])['status']===401,'deleted account login rejected');
    for($i=0;$i<10;$i++) $limited=request('auth/login','POST',null,['username'=>$i%2 ? 'FINANCE ' : 'finance','password'=>'wrong-password']);
    check(request('auth/login','POST',null,['username'=>'Finance','password'=>'wrong-password'])['status']===429,'username aliases cannot bypass login throttling');
    $curl=curl_init($base.'install.php');curl_setopt_array($curl,[CURLOPT_HTTPHEADER=>['Host: attacker.invalid'],CURLOPT_RETURNTRANSFER=>true]);curl_exec($curl);
    check(curl_getinfo($curl,CURLINFO_HTTP_CODE)===403,'installer rejects rebinding Host');curl_close($curl);
    file_put_contents($fixture.'/http-results.json',json_encode(['passed'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT));
    echo 'HTTP checks passed: '.count($checks)."\n";
} finally {
    file_put_contents($fixture.'/cleanup.json',json_encode(['avatars'=>$avatars]));
}
