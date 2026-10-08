param([string]$XamppRoot = 'C:\xampp')
$ErrorActionPreference = 'Stop'
$root = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..\..'))
$fixture = Join-Path $root ('build\security-audit\http-fixture-' + [guid]::NewGuid().ToString('N').Substring(0,8))
$php = Join-Path $XamppRoot 'php\php.exe'
$mysqlProcess = $null
$phpProcess = $null
$variables = @('DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','LOGIN_RATE_DIR','SECURITY_TEST_ALLOW_MUTATIONS','SECURITY_TEST_BASE_URL','SECURITY_TEST_FIXTURE')
$saved = @{}
foreach ($variable in $variables) { $saved[$variable] = [Environment]::GetEnvironmentVariable($variable,'Process') }
function Listening([int]$port) {
    $client=[Net.Sockets.TcpClient]::new()
    try { $async=$client.BeginConnect('127.0.0.1',$port,$null,$null); if(!$async.AsyncWaitHandle.WaitOne(200)){return $false};$client.EndConnect($async);return $true }
    catch {return $false} finally {$client.Dispose()}
}
try {
    if ((Listening 33307) -or (Listening 18099)) { throw 'Test ports are occupied; no fixture will be created.' }
    if (Test-Path (Join-Path $root 'backend\storage\document-templates\duty-991.docx')) { throw 'Test template scope is already in use.' }
    New-Item -ItemType Directory -Force -Path $fixture | Out-Null
    $data=Join-Path $fixture 'data'
    & (Join-Path $XamppRoot 'mysql\bin\mysql_install_db.exe') "--datadir=$data" --port=33307 --silent
    if($LASTEXITCODE -ne 0){throw 'Test database initialization failed'}
    $mysqlLog=Join-Path $fixture 'mysql-test.log'
    $mysqlProcess=Start-Process -FilePath (Join-Path $XamppRoot 'mysql\bin\mysqld.exe') -ArgumentList '--no-defaults',"--basedir=$XamppRoot\mysql",("--datadir=`"$data`""),'--port=33307','--bind-address=127.0.0.1',("--log-error=`"$mysqlLog`"") -WindowStyle Hidden -PassThru
    $deadline=[DateTime]::UtcNow.AddSeconds(30)
    while(!(Listening 33307)) {
        $mysqlProcess.Refresh()
        if($mysqlProcess.HasExited -or [DateTime]::UtcNow -gt $deadline){if(Test-Path $mysqlLog){Get-Content $mysqlLog -Tail 6};throw 'Test database startup failed'}
        Start-Sleep -Milliseconds 100
    }
    $env:SECURITY_TEST_ALLOW_MUTATIONS='1'
    $env:SECURITY_TEST_BASE_URL='http://127.0.0.1:18099/'
    $env:SECURITY_TEST_FIXTURE=$fixture
    & $php (Join-Path $PSScriptRoot 'prepare-fixture.php')
    if($LASTEXITCODE -ne 0){throw 'Fixture setup failed'}
    $runtime=Get-Content (Join-Path $fixture 'runtime.json') -Raw | ConvertFrom-Json
    foreach($property in $runtime.PSObject.Properties) { [Environment]::SetEnvironmentVariable($property.Name,[string]$property.Value,'Process') }
    $env:LOGIN_RATE_DIR=Join-Path $fixture 'limits'
    $phpProcess=Start-Process -FilePath $php -ArgumentList '-d','display_errors=0','-d',('upload_tmp_dir="'+$fixture+'"'),'-d',('sys_temp_dir="'+$fixture+'"'),'-S','127.0.0.1:18099',('-t "'+(Join-Path $root 'backend\public')+'"'),('"'+(Join-Path $PSScriptRoot 'router.php')+'"') -WorkingDirectory $root -RedirectStandardOutput (Join-Path $fixture 'http-stdout.log') -RedirectStandardError (Join-Path $fixture 'http-stderr.log') -WindowStyle Hidden -PassThru
    $deadline=[DateTime]::UtcNow.AddSeconds(10)
    while(!(Listening 18099)) { if([DateTime]::UtcNow -gt $deadline){throw 'Test HTTP startup timed out'};Start-Sleep -Milliseconds 100 }
    & $php (Join-Path $PSScriptRoot 'security-http.php')
    if($LASTEXITCODE -ne 0){throw 'HTTP security tests failed'}
    Copy-Item -LiteralPath (Join-Path $fixture 'http-results.json') -Destination (Join-Path $root 'build\security-audit\http-results.json') -Force
    & $php (Join-Path $PSScriptRoot 'security-concurrency.php')
    if($LASTEXITCODE -ne 0){throw 'Concurrent transfer test failed'}
    Copy-Item -LiteralPath (Join-Path $fixture 'concurrency-results.json') -Destination (Join-Path $root 'build\security-audit\concurrency-results.json') -Force
} finally {
    if($phpProcess -and !$phpProcess.HasExited) { Stop-Process -Id $phpProcess.Id; $phpProcess.WaitForExit() }
    if($mysqlProcess -and !$mysqlProcess.HasExited) {
        & (Join-Path $XamppRoot 'mysql\bin\mysqladmin.exe') --host=127.0.0.1 --port=33307 --user=root shutdown
        $mysqlProcess.WaitForExit(10000) | Out-Null
        if(!$mysqlProcess.HasExited){Stop-Process -Id $mysqlProcess.Id}
    }
    if(Test-Path (Join-Path $fixture 'cleanup.json')) {
        $cleanup=Get-Content (Join-Path $fixture 'cleanup.json') -Raw|ConvertFrom-Json
        foreach($avatar in $cleanup.avatars) {
            if($avatar -match '^avatar_viewer_[a-f0-9]{32}\.png$') { Remove-Item -LiteralPath (Join-Path $root "backend\public\uploads\avatars\$avatar") -ErrorAction SilentlyContinue }
        }
    }
    $template=Join-Path $root 'backend\storage\document-templates\duty-991.docx'
    if($mysqlProcess -and (Test-Path -LiteralPath $template)){Remove-Item -LiteralPath $template}
    foreach($variable in $variables){[Environment]::SetEnvironmentVariable($variable,$saved[$variable],'Process')}
    $allowed=[IO.Path]::GetFullPath((Join-Path $root 'build\security-audit')).TrimEnd('\')+'\'
    $resolved=[IO.Path]::GetFullPath($fixture)
    if($resolved.StartsWith($allowed,[StringComparison]::OrdinalIgnoreCase) -and (Split-Path $resolved -Leaf) -like 'http-fixture-*' -and (Test-Path -LiteralPath $resolved)) { Remove-Item -LiteralPath $resolved -Recurse -Force }
}
