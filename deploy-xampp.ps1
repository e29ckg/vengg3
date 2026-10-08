param(
    [string]$XamppRoot = 'C:\xampp'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$xamppRoot = [IO.Path]::GetFullPath($XamppRoot)
$target = Join-Path $xamppRoot 'htdocs\vengg3'
$php = Join-Path $xamppRoot 'php\php.exe'
$apache = Join-Path $xamppRoot 'apache\bin\httpd.exe'
$mysql = Join-Path $xamppRoot 'mysql\bin\mysql.exe'
$httpdConf = Join-Path $xamppRoot 'apache\conf\httpd.conf'
$steps = 8
$step = 0

function Update-Step([string]$message) {
    $script:step++
    $percent = [Math]::Min(100, [int](($script:step - 1) * 100 / $script:steps))
    Write-Progress -Activity 'Deploy vengg3 to XAMPP' -Status $message -PercentComplete $percent
    Write-Host ("[{0}/{1}] {2}" -f $script:step, $script:steps, $message)
}

function Assert-Exit([string]$name) {
    if ($LASTEXITCODE -ne 0) { throw "$name failed (exit code $LASTEXITCODE)." }
}

function Copy-Tree([string]$from, [string]$to, [string[]]$excludeDirs = @()) {
    New-Item -ItemType Directory -Force -Path $to | Out-Null
    $args = @($from, $to, '/E', '/R:2', '/W:1', '/NFL', '/NDL', '/NJH', '/NJS', '/NP', '/XF', 'database.local.php')
    if ($excludeDirs) { $args += '/XD'; $args += $excludeDirs }
    & robocopy @args | Out-Null
    if ($LASTEXITCODE -gt 7) { throw "Copy failed from $from to $to (robocopy $LASTEXITCODE)." }
}

try {
    Update-Step 'Checking project files and XAMPP'
    foreach ($file in @($php, $apache, $mysql, $httpdConf,
        (Join-Path $projectRoot 'frontend\package-lock.json'),
        (Join-Path $projectRoot 'backend\composer.lock'),
        (Join-Path $projectRoot 'deploy\xampp.htaccess'))) {
        if (!(Test-Path -LiteralPath $file -PathType Leaf)) { throw "Missing required file: $file" }
    }
    if (!(Test-Path -LiteralPath (Join-Path $xamppRoot 'htdocs') -PathType Container)) {
        throw "XAMPP htdocs directory is missing."
    }
    $sameDirectory = [IO.Path]::GetFullPath($projectRoot).TrimEnd('\') -ieq [IO.Path]::GetFullPath($target).TrimEnd('\')

    Update-Step 'Checking PHP extensions, Node.js and Composer'
    $phpVersion = (& $php -r 'echo PHP_VERSION;')
    Assert-Exit 'PHP version check'
    if ([version]$phpVersion -lt [version]'8.2.0') { throw "PHP 8.2 or newer is required; found $phpVersion." }
    $modules = & $php -m
    Assert-Exit 'PHP extension check'
    foreach ($module in @('pdo_mysql', 'curl', 'mbstring', 'fileinfo', 'zip')) {
        if ($modules -notcontains $module) { throw "PHP extension $module is required." }
    }
    $node = Get-Command node.exe -ErrorAction SilentlyContinue
    $npm = Get-Command npm.cmd -ErrorAction SilentlyContinue
    $composer = Get-Command composer.bat -ErrorAction SilentlyContinue
    if (!$node -or !$npm -or !$composer) { throw 'Node.js, npm.cmd and Composer must be installed and available in PATH.' }
    $nodeVersion = (& $node.Source --version).TrimStart('v')
    Assert-Exit 'Node.js version check'
    if ([version]$nodeVersion -lt [version]'22.0.0') { throw "Node.js 22 or newer is required; found $nodeVersion." }

    Update-Step 'Checking Apache and its listening port'
    $previousErrorAction = $ErrorActionPreference
    try {
        # Apache writes successful diagnostics (including "Syntax OK") to stderr.
        $ErrorActionPreference = 'Continue'
        $apacheCheck = & $apache -t 2>&1
        $apacheExit = $LASTEXITCODE
        $apacheModules = & $apache -M 2>&1
        $modulesExit = $LASTEXITCODE
    } finally { $ErrorActionPreference = $previousErrorAction }
    if ($apacheExit -ne 0 -or $modulesExit -ne 0) { throw 'Apache configuration check failed.' }
    if (-not ($apacheModules | Select-String 'rewrite_module')) { throw 'Apache mod_rewrite is required.' }
    $conf = Get-Content -LiteralPath $httpdConf
    $listen = $conf | Where-Object { $_ -match '^\s*Listen\s+(?:\S+:)?(\d+)\s*$' } | Select-Object -First 1
    if (!$listen) { throw 'Could not find the Apache Listen port in httpd.conf.' }
    $port = [int]([regex]::Match($listen, '(\d+)\s*$').Groups[1].Value)
    $client = [Net.Sockets.TcpClient]::new()
    try {
        $connect = $client.BeginConnect('127.0.0.1', $port, $null, $null)
        if (!$connect.AsyncWaitHandle.WaitOne(2000)) { throw 'Apache is not listening.' }
        $client.EndConnect($connect)
    } finally { $client.Dispose() }
    if (-not ($conf | Select-String '^\s*AllowOverride\s+All\s*$')) {
        throw 'Apache must allow .htaccess overrides for htdocs.'
    }

    Update-Step 'Checking database connection and schema'
    $targetConfig = Join-Path $target 'backend\src\config\database.local.php'
    $sourceConfig = Join-Path $projectRoot 'backend\src\config\database.local.php'
    $config = if (Test-Path -LiteralPath $targetConfig) { $targetConfig } else { $sourceConfig }
    if (!(Test-Path -LiteralPath $config)) {
        throw 'Create backend/src/config/database.local.php and import database.sql before deploying. See readme.md.'
    }
    & $php (Join-Path $projectRoot 'deploy\check-db.php') $config
    Assert-Exit 'Database check'

    Update-Step 'Building frontend for /vengg3/ and /vengg3/api/'
    Push-Location (Join-Path $projectRoot 'frontend')
    try {
        & $npm.Source ci
        Assert-Exit 'npm ci'
        $oldApi = $env:VITE_API_BASE_URL
        try {
            $env:VITE_API_BASE_URL = '/vengg3/api/'
            & $npm.Source run build -- --base=/vengg3/
            Assert-Exit 'frontend build'
        } finally { $env:VITE_API_BASE_URL = $oldApi }
    } finally { Pop-Location }

    Update-Step 'Installing backend and Composer dependencies'
    if (!$sameDirectory) {
        foreach ($folder in @('src', 'public', 'bin', 'cron')) {
            $excluded = if ($folder -eq 'public') { @('uploads') } else { @() }
            Copy-Tree (Join-Path $projectRoot "backend\$folder") (Join-Path $target "backend\$folder") $excluded
        }
        foreach ($file in @('composer.json', 'composer.lock')) {
            Copy-Item -LiteralPath (Join-Path $projectRoot "backend\$file") -Destination (Join-Path $target "backend\$file") -Force
        }
        if (!(Test-Path -LiteralPath $targetConfig)) {
            Copy-Item -LiteralPath $sourceConfig -Destination $targetConfig
        }
    }
    Push-Location (Join-Path $target 'backend')
    try {
        & $composer.Source install --no-dev --no-interaction --prefer-dist --no-progress
        Assert-Exit 'composer install'
    } finally { Pop-Location }

    Update-Step 'Copying frontend and configuring URLs'
    Get-ChildItem -LiteralPath (Join-Path $projectRoot 'frontend\dist') -Force | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination $target -Recurse -Force
    }
    $rewriteFile = Join-Path $target '.htaccess'
    if (Test-Path -LiteralPath $rewriteFile) {
        Copy-Item -LiteralPath $rewriteFile -Destination (Join-Path $target '.htaccess.xampp-backup') -Force
    }
    Copy-Item -LiteralPath (Join-Path $projectRoot 'deploy\xampp.htaccess') -Destination $rewriteFile -Force

    Update-Step 'Verifying frontend, API and private-file access'
    $baseUrl = "http://localhost:$port/vengg3/"
    $page = Invoke-WebRequest -Uri $baseUrl -UseBasicParsing -TimeoutSec 10
    if ($page.StatusCode -ne 200 -or $page.Content -notmatch '/vengg3/assets/') { throw 'Frontend response is invalid.' }
    $assetPath = [regex]::Match($page.Content, 'src="(/vengg3/assets/[^"]+\.js)"').Groups[1].Value
    if (!$assetPath) { throw 'Frontend JavaScript asset is missing.' }
    $asset = Invoke-WebRequest -Uri "http://localhost:$port$assetPath" -UseBasicParsing -TimeoutSec 10
    if ($asset.StatusCode -ne 200) { throw 'Frontend asset request failed.' }
    $api = Invoke-RestMethod -Uri "${baseUrl}api?route=test" -TimeoutSec 10
    if ($api.status -ne 'success') { throw 'API response is invalid.' }
    try {
        Invoke-WebRequest -Uri "${baseUrl}backend/src/config/database.php" -UseBasicParsing -TimeoutSec 10 | Out-Null
        throw 'Private backend files are publicly accessible.'
    } catch {
        if ($_.Exception.PSObject.Properties.Name -notcontains 'Response' -or
            !$_.Exception.Response -or
            [int]$_.Exception.Response.StatusCode -ne 403) { throw }
    }
    Write-Progress -Activity 'Deploy vengg3 to XAMPP' -Completed
    Write-Host "Deploy complete: $baseUrl" -ForegroundColor Green
    Write-Host "API: ${baseUrl}api?route=test"
} catch {
    Write-Progress -Activity 'Deploy vengg3 to XAMPP' -Completed
    Write-Error "Deploy stopped at step $step/$steps`: $($_.Exception.Message) $($_.ScriptStackTrace)"
    exit 1
}
