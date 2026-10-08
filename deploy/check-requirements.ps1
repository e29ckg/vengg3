param([string]$XamppRoot = 'C:\xampp')
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$php = Join-Path $XamppRoot 'php\php.exe'
$results = [Collections.Generic.List[object]]::new()

function Check([string]$id, [string]$label, [string]$hint, [scriptblock]$test) {
    try {
        $detail = & $test
        $results.Add([pscustomobject]@{id=$id; label=$label; status='passed'; detail=[string]$detail; hint=''})
    } catch {
        $results.Add([pscustomobject]@{id=$id; label=$label; status='failed'; detail=''; hint=$hint})
    }
}

Check 'xampp' 'XAMPP' 'Install XAMPP with Apache, PHP and MySQL.' {
    foreach ($path in @('php\php.exe', 'apache\bin\httpd.exe', 'mysql\bin\mysql.exe', 'htdocs')) {
        if (!(Test-Path -LiteralPath (Join-Path $XamppRoot $path))) { throw 'Missing XAMPP component' }
    }
    'Apache / PHP / MySQL'
}
Check 'files' 'Project files' 'Download the complete project, including frontend, backend and deploy folders.' {
    foreach ($path in @('frontend\package-lock.json', 'backend\composer.lock', '.htaccess', 'deploy-xampp.ps1', 'deploy\check-db.php')) {
        if (!(Test-Path -LiteralPath (Join-Path $projectRoot $path))) { throw 'Missing project file' }
    }
    'Complete'
}
Check 'php' 'PHP 8.2+' 'Use XAMPP with PHP 8.2 or newer.' {
    $version = & $php -r 'echo PHP_VERSION;'
    if ($LASTEXITCODE -ne 0 -or [version]$version -lt [version]'8.2.0') { throw 'Unsupported PHP' }
    $version
}
foreach ($extension in @('pdo_mysql', 'curl', 'mbstring', 'fileinfo', 'zip')) {
    Check "php_$extension" "PHP: $extension" "Enable $extension in XAMPP php.ini, then restart Apache." {
        $modules = & $php -m
        if ($LASTEXITCODE -ne 0 -or $modules -notcontains $extension) { throw 'Missing extension' }
        'Enabled'
    }
}
Check 'node' 'Node.js 22.13+' 'Install Node.js 22.13+ and restart Apache so it reads the new PATH.' {
    $node = Get-Command node.exe -ErrorAction Stop
    $version = (& $node.Source --version).TrimStart('v')
    if ($LASTEXITCODE -ne 0 -or [version]$version -lt [version]'22.13.0') { throw 'Unsupported Node.js' }
    "v$version"
}
Check 'npm' 'npm' 'Install npm with Node.js and restart Apache.' {
    $npm = Get-Command npm.cmd -ErrorAction Stop
    $version = & $npm.Source --version
    if ($LASTEXITCODE -ne 0) { throw 'npm unavailable' }
    $version
}
Check 'composer' 'Composer' 'Install Composer, add it to PATH and restart Apache.' {
    Get-Command composer.bat -ErrorAction Stop | Out-Null
    'Available in PATH'
}
Check 'apache' 'Apache' 'Start Apache in XAMPP and check the Listen port in httpd.conf.' {
    $conf = Get-Content -LiteralPath (Join-Path $XamppRoot 'apache\conf\httpd.conf')
    $listen = $conf | Where-Object { $_ -match '^\s*Listen\s+(?:\S+:)?(\d+)\s*$' } | Select-Object -First 1
    if (!$listen) { throw 'Listen port missing' }
    $port = [int]([regex]::Match($listen, '(\d+)\s*$').Groups[1].Value)
    $client = [Net.Sockets.TcpClient]::new()
    try {
        $connect = $client.BeginConnect('127.0.0.1', $port, $null, $null)
        if (!$connect.AsyncWaitHandle.WaitOne(2000)) { throw 'Apache not listening' }
        $client.EndConnect($connect)
    } finally { $client.Dispose() }
    "Port $port"
}
Check 'rewrite' 'Apache mod_rewrite' 'Enable LoadModule rewrite_module in httpd.conf and restart Apache.' {
    if (!(Get-Content -LiteralPath (Join-Path $XamppRoot 'apache\conf\httpd.conf') | Select-String '^\s*LoadModule\s+rewrite_module\s+')) { throw 'Rewrite disabled' }
    'Enabled in httpd.conf'
}
Check 'override' 'Apache .htaccess' 'Set AllowOverride All for htdocs in httpd.conf and restart Apache.' {
    if (!(Get-Content -LiteralPath (Join-Path $XamppRoot 'apache\conf\httpd.conf') | Select-String '^\s*AllowOverride\s+All\s*$')) { throw 'Overrides disabled' }
    $listen = Get-Content -LiteralPath (Join-Path $XamppRoot 'apache\conf\httpd.conf') | Where-Object {$_ -match '^\s*Listen\s+(?:\S+:)?(\d+)\s*$'} | Select-Object -First 1
    if (!$listen) { throw 'Missing Apache port' }
    $port = [int]([regex]::Match($listen,'(\d+)\s*$').Groups[1].Value)
    foreach ($privatePath in @('backend/src/config/database.php','.git','database.sql')) {
        $code = 0
        try { $response=Invoke-WebRequest -Uri "http://127.0.0.1:$port/vengg3/$privatePath" -UseBasicParsing -TimeoutSec 5; $code=[int]$response.StatusCode }
        catch { if ($_.Exception.PSObject.Properties.Name -contains 'Response' -and $_.Exception.Response) { $code=[int]$_.Exception.Response.StatusCode } }
        if ($code -ne 403) { throw 'Private paths are not denied by Apache' }
    }
    'HTTP access to private files denied'
}
Check 'db_config' 'Database configuration' 'Create backend/src/config/database.local.php with DB_HOST, DB_NAME, DB_USER and DB_PASS.' {
    if (!(Test-Path -LiteralPath (Join-Path $projectRoot 'backend\src\config\database.local.php'))) { throw 'Database config missing' }
    'database.local.php found'
}
Check 'database' 'MySQL and database schema' 'Start MySQL, check database.local.php and import database.sql.' {
    & $php (Join-Path $PSScriptRoot 'check-db.php') (Join-Path $projectRoot 'backend\src\config\database.local.php') 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Database check failed' }
    'Connection and user table OK'
}
Check 'administrator' 'Administrator account' 'Create the initial administrator through database setup.' {
    & $php (Join-Path $PSScriptRoot 'check-db.php') (Join-Path $projectRoot 'backend\src\config\database.local.php') --admin 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) { throw 'Administrator unavailable' }
    'Active administrator found'
}
$payload = @{ok=(@($results | Where-Object {$_.status -ne 'passed'}).Count -eq 0); checks=@($results.ToArray())}
$payload | ConvertTo-Json -Depth 4 -Compress
