<?php
declare(strict_types=1);

// This page intentionally runs local deployment commands. Never expose it to remote clients.
$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddress, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) {
    http_response_code(403);
    exit('Installer is available only from this computer.');
}

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; connect-src 'self'; base-uri 'none'; form-action 'self'");

session_name('vengg3_installer');
session_set_cookie_params([
    'path' => '/vengg3/',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();
if (empty($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['install_token'];

function runDeployment(bool $preflight, callable $onLine): int
{
    if (!function_exists('proc_open')) {
        $onLine('PHP proc_open is disabled; enable it in php.ini.');
        return 1;
    }
    $script = __DIR__ . DIRECTORY_SEPARATOR . 'deploy-xampp.ps1';
    if (!is_file($script)) {
        $onLine('deploy-xampp.ps1 is missing from the project directory.');
        return 1;
    }
    $command = [
        'powershell.exe', '-NoProfile', '-ExecutionPolicy', 'Bypass',
        '-File', $script, '-XamppRoot', dirname(__DIR__, 2), '-WebMode',
    ];
    if ($preflight) {
        $command[] = '-PreflightOnly';
    }
    $pipes = [];
    try {
        $process = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]],
            $pipes,
            __DIR__,
            null,
            ['bypass_shell' => true]
        );
    } catch (Throwable $error) {
        $onLine('Could not start PowerShell. Check permissions for the Apache account.');
        return 1;
    }
    if (!is_resource($process)) {
        $onLine('Could not start PowerShell. Check permissions for the Apache account.');
        return 1;
    }
    fclose($pipes[0]);
    while (($line = fgets($pipes[1])) !== false) {
        $line = trim($line);
        if ($line !== '') {
            $onLine($line);
        }
    }
    fclose($pipes[1]);
    return proc_close($process);
}

$action = $_GET['action'] ?? '';
if ($action === 'check') {
    header('Content-Type: application/json; charset=utf-8');
    $checks = [];
    try {
    if (function_exists('proc_open')) {
        $checkProcess = @proc_open(
            ['powershell.exe', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File',
                __DIR__ . '/deploy/check-requirements.ps1', '-XamppRoot', dirname(__DIR__, 2)],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', 'NUL', 'w']],
            $checkPipes, __DIR__, null, ['bypass_shell' => true]
        );
        if (is_resource($checkProcess)) {
            fclose($checkPipes[0]);
            $checkOutput = stream_get_contents($checkPipes[1]);
            fclose($checkPipes[1]);
            $checkExit = proc_close($checkProcess);
            $report = json_decode($checkOutput, true);
            if ($checkExit === 0 && is_array($report['checks'] ?? null)) {
                $checks = $report['checks'];
            }
        }
    }
    } catch (Throwable $error) {
        // The installer row reports failure; do not expose process environment details.
    }
    $lines = [];
    $exitCode = runDeployment(true, static function (string $line) use (&$lines): void {
        $lines[] = $line;
    });
    $checks[] = ['id' => 'installer', 'status' => $exitCode === 0 && $checks !== [] ? 'passed' : 'failed',
        'detail' => '', 'hint' => 'Check PowerShell, PHP proc_open and Apache account permissions.'];
    $ready = $exitCode === 0 && count(array_filter($checks, static fn(array $check): bool => $check['status'] !== 'passed')) === 0;
    echo json_encode(['ok' => $ready, 'checks' => $checks, 'lines' => $lines], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($action === 'install') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
        !hash_equals($csrfToken, (string)($_POST['token'] ?? ''))) {
        http_response_code(403);
        exit('Invalid installer request.');
    }
    session_write_close();
    $lock = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vengg3-installer.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
        http_response_code(409);
        exit('Another installation is already running.');
    }
    set_time_limit(0);
    header('Content-Type: application/x-ndjson; charset=utf-8');
    header('X-Accel-Buffering: no');
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    $send = static function (array $event): void {
        echo json_encode($event, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE) . "\n";
        flush();
    };
    $send(['type' => 'line', 'text' => 'Starting XAMPP deployment...']);
    $exitCode = runDeployment(false, static function (string $line) use ($send): void {
        $send(['type' => 'line', 'text' => $line]);
    });
    $send(['type' => 'done', 'ok' => $exitCode === 0]);
    flock($lock, LOCK_UN);
    fclose($lock);
    exit;
}

if ($action !== '') {
    http_response_code(404);
    exit('Unknown installer action.');
}

$safeToken = htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= $safeToken ?>">
  <title>ติดตั้ง vengg3 บน XAMPP</title>
  <style>
    :root { color-scheme: light; font-family: system-ui, "Segoe UI", sans-serif; color: #203243; background: #edf2f6; }
    * { box-sizing: border-box; }
    body { margin: 0; padding: 28px 16px; }
    main { max-width: 780px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 12px 40px #1a35541a; }
    h1 { margin: 0 0 8px; font-size: 1.65rem; }
    h2 { font-size: 1.08rem; margin: 28px 0 10px; }
    p { line-height: 1.6; margin: 8px 0; }
    .subtle { color: #576b7d; }
    .panel { border: 1px solid #d8e2e9; border-radius: 10px; padding: 16px; background: #f8fafc; }
    .status { font-weight: 700; margin-bottom: 10px; }
    .ok { color: #17724f; }
    .error { color: #ab3434; }
    .checklist { list-style: none; padding: 0; margin: 14px 0; }
    .checklist li { display: flex; gap: 12px; align-items: flex-start; padding: 12px 0; border-bottom: 1px solid #dfe7ed; }
    .checklist li:last-child { border-bottom: 0; }
    .check-icon { flex: 0 0 26px; height: 26px; border-radius: 50%; text-align: center; line-height: 26px; background: #e6edf2; color: #576b7d; font-weight: 700; }
    .passed .check-icon { background: #daf2e7; color: #17724f; }
    .failed .check-icon { background: #fce3e3; color: #ab3434; }
    .check-content { flex: 1; min-width: 0; }
    .check-title { font-weight: 650; }
    .check-detail { color: #576b7d; font-size: .88rem; margin-top: 3px; overflow-wrap: anywhere; }
    .check-badge { font-size: .8rem; flex-shrink: 0; padding-top: 3px; }
    details { margin-top: 14px; }
    summary { cursor: pointer; color: #576b7d; }
    .bar { height: 12px; border-radius: 99px; background: #e0e9ef; overflow: hidden; margin: 14px 0; }
    .bar > div { width: 0; height: 100%; background: #168867; transition: width .25s ease; }
    button { border: 0; border-radius: 8px; padding: 11px 16px; color: #fff; background: #166c87; font: inherit; font-weight: 700; cursor: pointer; }
    button:disabled { opacity: .48; cursor: not-allowed; }
    button.secondary { color: #166c87; background: #e8f1f5; margin-left: 8px; }
    pre { white-space: pre-wrap; overflow-wrap: anywhere; max-height: 330px; overflow-y: auto; margin: 12px 0 0; padding: 12px; background: #172b39; color: #e8f4f8; border-radius: 8px; font-size: .84rem; line-height: 1.45; }
    .links { margin-top: 14px; display: none; }
    a { color: #086d91; }
  </style>
</head>
<body>
<main>
  <h1>ติดตั้ง vengg3 บน XAMPP</h1>
  <p class="subtle">หน้าเว็บ <strong>/vengg3/</strong> · API <strong>/vengg3/api</strong></p>
  <p>ตัวติดตั้งจะตรวจ XAMPP, PHP, Node.js, Composer, Apache และฐานข้อมูลก่อนเริ่มลงไฟล์ ต้องมี <code>backend/src/config/database.local.php</code> และนำเข้า <code>database.sql</code> แล้ว</p>

  <h2>1. ตรวจสอบความพร้อม</h2>
  <div class="panel">
    <div id="check-status" class="status">กำลังตรวจสอบ...</div>
    <ul id="checklist" class="checklist" aria-live="polite" aria-busy="true"></ul>
    <button id="check-button" class="secondary" type="button">ตรวจสอบอีกครั้ง</button>
    <details><summary>รายละเอียดการตรวจสอบ</summary><pre id="check-log"></pre></details>
  </div>

  <h2>2. ติดตั้งหรืออัปเดต</h2>
  <div class="panel">
    <div id="install-status" class="status subtle">รอผลตรวจสอบ</div>
    <button id="install-button" type="button" disabled>เริ่มติดตั้ง</button>
    <div class="bar" role="progressbar" aria-label="ความคืบหน้าการติดตั้ง" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div id="bar-fill"></div></div>
    <pre id="install-log" aria-live="polite">ยังไม่เริ่มติดตั้ง</pre>
    <div id="links" class="links"><a href="/vengg3/">เปิดหน้าเว็บ</a> · <a href="/vengg3/api?route=test">ทดสอบ API</a></div>
  </div>
</main>
<script>
const checkStatus = document.getElementById('check-status');
const checkLog = document.getElementById('check-log');
const checkButton = document.getElementById('check-button');
const installButton = document.getElementById('install-button');
const installStatus = document.getElementById('install-status');
const installLog = document.getElementById('install-log');
const progress = document.querySelector('[role="progressbar"]');
const barFill = document.getElementById('bar-fill');
let busy = false;
const checklist = document.getElementById('checklist');
const requirements = {
  xampp: ['XAMPP', 'ติดตั้ง XAMPP ที่มี Apache, PHP และ MySQL'],
  files: ['ไฟล์โปรเจกต์ครบถ้วน', 'ดาวน์โหลดโปรเจกต์ให้ครบ รวมโฟลเดอร์ frontend, backend และ deploy'],
  php: ['PHP 8.2 ขึ้นไป', 'ใช้ XAMPP ที่มี PHP 8.2 ขึ้นไป'],
  php_pdo_mysql: ['PHP: PDO MySQL', 'เปิด pdo_mysql ใน php.ini แล้วเริ่ม Apache ใหม่'],
  php_curl: ['PHP: cURL', 'เปิด curl ใน php.ini แล้วเริ่ม Apache ใหม่'],
  php_mbstring: ['PHP: mbstring', 'เปิด mbstring ใน php.ini แล้วเริ่ม Apache ใหม่'],
  php_fileinfo: ['PHP: fileinfo', 'เปิด fileinfo ใน php.ini แล้วเริ่ม Apache ใหม่'],
  php_zip: ['PHP: ZIP', 'เปิด zip ใน php.ini แล้วเริ่ม Apache ใหม่'],
  node: ['Node.js 22 ขึ้นไป', 'ติดตั้ง Node.js 22 ขึ้นไป แล้วเริ่ม Apache ใหม่เพื่ออ่าน PATH'],
  npm: ['npm', 'ติดตั้ง npm พร้อม Node.js แล้วเริ่ม Apache ใหม่'],
  composer: ['Composer', 'ติดตั้ง Composer และเพิ่มใน PATH แล้วเริ่ม Apache ใหม่'],
  apache: ['Apache พร้อมใช้งาน', 'เปิด Apache ใน XAMPP และตรวจพอร์ต Listen ใน httpd.conf'],
  rewrite: ['Apache: mod_rewrite', 'เปิด LoadModule rewrite_module ใน httpd.conf แล้วเริ่ม Apache ใหม่'],
  override: ['Apache: ใช้ไฟล์ .htaccess', 'ตั้ง AllowOverride All สำหรับ htdocs แล้วเริ่ม Apache ใหม่'],
  db_config: ['ไฟล์ตั้งค่าฐานข้อมูล', 'สร้าง backend/src/config/database.local.php พร้อมค่าการเชื่อมต่อ'],
  database: ['MySQL และโครงสร้างฐานข้อมูล', 'เปิด MySQL ตรวจค่าการเชื่อมต่อ และนำเข้า database.sql'],
  installer: ['ตัวติดตั้งพร้อมทำงาน', 'ตรวจ PowerShell, PHP proc_open และสิทธิ์ของบัญชีที่รัน Apache; ดูรายละเอียดการตรวจสอบด้านล่าง']
};
function renderChecklist(checks = [], finished = false) {
  const byId = new Map(checks.map(check => [check.id, check]));
  checklist.replaceChildren();
  for (const [id, [label, hint]] of Object.entries(requirements)) {
    const check = byId.get(id);
    const state = check?.status || (finished ? 'unavailable' : 'pending');
    const row = document.createElement('li');
    row.className = state;
    const icon = document.createElement('span');
    icon.className = 'check-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = state === 'passed' ? '✓' : state === 'failed' ? '✕' : '…';
    const content = document.createElement('div');
    content.className = 'check-content';
    const title = document.createElement('div');
    title.className = 'check-title';
    title.textContent = label;
    const detail = document.createElement('div');
    detail.className = 'check-detail';
    const details = {'Complete': 'ไฟล์ครบถ้วน', 'Enabled': 'เปิดใช้งาน', 'Available in PATH': 'เรียกใช้ได้จาก PATH', 'Enabled in httpd.conf': 'เปิดใช้งานใน httpd.conf', 'AllowOverride All configured': 'ตั้งค่า AllowOverride All แล้ว', 'database.local.php found': 'พบไฟล์ database.local.php', 'Connection and user table OK': 'เชื่อมต่อได้และพบตาราง user'};
    detail.textContent = state === 'failed' ? hint : state === 'passed' ? (details[check.detail] || check.detail || 'พร้อมใช้งาน') : state === 'unavailable' ? 'ตัวตรวจสอบไม่ส่งผลรายการนี้ กรุณาตรวจสอบอีกครั้ง' : 'กำลังตรวจสอบ';
    const badge = document.createElement('span');
    badge.className = `check-badge ${state === 'passed' ? 'ok' : state === 'failed' ? 'error' : 'subtle'}`;
    badge.textContent = state === 'passed' ? 'ผ่าน' : state === 'failed' ? 'ไม่ผ่าน' : state === 'unavailable' ? 'ยังไม่ได้ตรวจ' : 'รอผล';
    content.append(title, detail);
    row.append(icon, content, badge);
    checklist.append(row);
  }
}

function appendLine(element, line) {
  element.textContent += (element.textContent ? '\n' : '') + line;
  element.scrollTop = element.scrollHeight;
}
function setProgress(value) {
  progress.setAttribute('aria-valuenow', String(value));
  barFill.style.width = `${value}%`;
}
async function checkRequirements() {
  if (busy) return;
  checkButton.disabled = true;
  installButton.disabled = true;
  checkStatus.textContent = 'กำลังตรวจสอบ...';
  checkStatus.className = 'status';
  checkLog.textContent = '';
  checklist.setAttribute('aria-busy', 'true');
  renderChecklist();
  try {
    const response = await fetch('?action=check', {cache: 'no-store'});
    const result = await response.json();
    if (!response.ok || !Array.isArray(result.checks)) throw new Error('ไม่ได้รับผลเช็กลิสต์จากตัวตรวจสอบ');
    renderChecklist(result.checks, true);
    checkLog.textContent = result.lines.join('\n');
    checkStatus.textContent = result.ok ? 'พร้อมติดตั้ง' : 'ยังไม่พร้อมติดตั้ง — ดูรายละเอียดด้านล่าง';
    checkStatus.className = `status ${result.ok ? 'ok' : 'error'}`;
    installButton.disabled = !result.ok;
    if (!installStatus.classList.contains('ok') && !installStatus.classList.contains('error')) {
      installStatus.textContent = result.ok ? 'พร้อมเริ่ม' : 'แก้สิ่งที่ขาดแล้วตรวจสอบอีกครั้ง';
    }
  } catch (error) {
    renderChecklist(Object.keys(requirements).map(id => ({id, status: 'unavailable'})), true);
    checkStatus.textContent = 'ตรวจสอบไม่สำเร็จ';
    checkStatus.className = 'status error';
    checkLog.textContent = String(error);
  } finally {
    checklist.setAttribute('aria-busy', 'false');
    checkButton.disabled = false;
  }
}
async function install() {
  if (busy || installButton.disabled) return;
  busy = true;
  installButton.disabled = true;
  checkButton.disabled = true;
  document.getElementById('links').style.display = 'none';
  installLog.textContent = '';
  installStatus.textContent = 'กำลังติดตั้ง...';
  installStatus.className = 'status';
  setProgress(0);
  try {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const response = await fetch('?action=install', {
      method: 'POST', credentials: 'same-origin',
      body: new URLSearchParams({token})
    });
    if (!response.ok || !response.body) throw new Error(`HTTP ${response.status}: ${await response.text()}`);
    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let pending = '';
    let finished = false;
    while (true) {
      const {value, done} = await reader.read();
      pending += decoder.decode(value || new Uint8Array(), {stream: !done});
      const records = pending.split('\n');
      pending = records.pop();
      for (const record of records) {
        if (!record.trim()) continue;
        const event = JSON.parse(record);
        if (event.type === 'line') {
          appendLine(installLog, event.text);
          const step = event.text.match(/^\[(\d+)\/8\]/);
          if (step) setProgress(Math.round(Number(step[1]) * 100 / 8));
        } else if (event.type === 'done') {
          finished = true;
          installStatus.textContent = event.ok ? 'ติดตั้งสำเร็จ' : 'ติดตั้งไม่สำเร็จ — ดูรายละเอียดด้านล่าง';
          installStatus.className = `status ${event.ok ? 'ok' : 'error'}`;
          if (event.ok) {
            setProgress(100);
            document.getElementById('links').style.display = 'block';
          }
        }
      }
      if (done) break;
    }
    if (!finished) throw new Error('การเชื่อมต่อสิ้นสุดก่อนติดตั้งเสร็จ');
  } catch (error) {
    appendLine(installLog, String(error));
    installStatus.textContent = 'ติดตั้งไม่สำเร็จ';
    installStatus.className = 'status error';
  } finally {
    busy = false;
    checkButton.disabled = false;
    await checkRequirements();
  }
}
checkButton.addEventListener('click', checkRequirements);
installButton.addEventListener('click', install);
checkRequirements();
</script>
</body>
</html>
