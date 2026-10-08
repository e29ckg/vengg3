param([Parameter(Mandatory=$true)][string]$ProjectRoot, [Parameter(Mandatory=$true)][ValidatePattern('^[a-f0-9]{40}$')][string]$ExpectedCommit)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$root = [IO.Path]::GetFullPath($ProjectRoot).TrimEnd('\')
$repository = 'https://github.com/e29ckg/vengg3.git'
$tempRoot = [IO.Path]::GetFullPath((Join-Path (Split-Path (Split-Path $root -Parent) -Parent) 'tmp')).TrimEnd('\') + '\'
$stage = [IO.Path]::GetFullPath((Join-Path $tempRoot ('v3u-' + [guid]::NewGuid().ToString('N').Substring(0,8))))
$applied = $false
$previous = ''
$vendorMoved = $false
$vendorInstalled = $false
$succeeded = $false
function Git-Run([string[]]$arguments) {
    $previousErrorAction = $ErrorActionPreference
    try {
        $ErrorActionPreference = 'Continue'
        $output = & git.exe -C $root @arguments 2>$null
        $code = $LASTEXITCODE
    } finally { $ErrorActionPreference = $previousErrorAction }
    if ($code -ne 0) { throw 'Git command failed; check GitHub connectivity and repository state.' }
    return ($output -join "`n").Trim()
}
try {
    Write-Host '[1/5] Checking checkout and local changes'
    if ((Git-Run -arguments @('rev-parse','--show-toplevel')).Replace('/','\').TrimEnd('\') -ine $root) { throw 'Project must be the repository root.' }
    if ((Git-Run -arguments @('branch','--show-current')) -ne 'main') { throw 'Updates require the main branch.' }
    if ((Git-Run -arguments @('status','--porcelain','--untracked-files=no')) -ne '') { throw 'Tracked files have local changes; save them before updating.' }
    $previous = Git-Run -arguments @('rev-parse','HEAD')
    if ($previous -eq $ExpectedCommit) { Write-Host 'Already up to date.'; exit 0 }
    Write-Host '[2/5] Downloading GitHub main'
    Git-Run -arguments @('fetch','--no-tags',$repository,'refs/heads/main') | Out-Null
    $latest = Git-Run -arguments @('rev-parse','FETCH_HEAD')
    if ($latest -ne $ExpectedCommit) { throw 'GitHub main changed; check the version again.' }
    & git.exe -C $root merge-base --is-ancestor $previous $latest
    if ($LASTEXITCODE -ne 0) { throw 'History diverged; an automatic fast-forward update is not possible.' }
    New-Item -ItemType Directory -Force -Path $tempRoot | Out-Null
    New-Item -ItemType Directory -Path $stage | Out-Null
    $archive = Join-Path $stage 'release.zip'
    Git-Run -arguments @('archive','--format=zip',"--output=$archive",$latest) | Out-Null
    $release = Join-Path $stage 'release'
    Expand-Archive -LiteralPath $archive -DestinationPath $release
    $vendor = [IO.Path]::GetFullPath((Join-Path $root 'backend\vendor'))
    $preparedVendor = [IO.Path]::GetFullPath((Join-Path $release 'backend\vendor'))
    $backupVendor = [IO.Path]::GetFullPath((Join-Path $stage 'previous-vendor'))
    if (-not $vendor.StartsWith($root + '\',[StringComparison]::OrdinalIgnoreCase) -or
        -not $preparedVendor.StartsWith($stage + '\',[StringComparison]::OrdinalIgnoreCase) -or
        -not $backupVendor.StartsWith($stage + '\',[StringComparison]::OrdinalIgnoreCase)) { throw 'Unexpected dependency paths.' }
    if (!(Test-Path -LiteralPath (Join-Path $release 'index.html')) -or !(Test-Path -LiteralPath (Join-Path $release 'assets'))) { throw 'The release has no compiled XAMPP frontend.' }
    $php = Join-Path (Split-Path (Split-Path $root -Parent) -Parent) 'php\php.exe'
    Write-Host '[3/5] Preparing dependencies before applying files'
    $dependenciesChanged = (Git-Run -arguments @('diff','--name-only',$previous,$latest,'--','backend/composer.json','backend/composer.lock')) -ne '' -or !(Test-Path -LiteralPath (Join-Path $vendor 'autoload.php'))
    if ($dependenciesChanged) {
        $composer = Get-Command composer.bat -ErrorAction Stop
        Push-Location (Join-Path $release 'backend')
        try {
            & $composer.Source install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-plugins
            if ($LASTEXITCODE -ne 0) { throw 'Composer dependency preparation failed.' }
        } finally { Pop-Location }
    } else { Write-Host 'Dependencies unchanged; keeping installed vendor files.' }
    foreach ($file in Get-ChildItem (Join-Path $release 'backend') -Filter '*.php' -Recurse -File | Where-Object {$_.FullName -notlike '*\vendor\*'}) {
        & $php -l $file.FullName | Out-Null
        if ($LASTEXITCODE -ne 0) { throw 'Release PHP syntax validation failed.' }
    }
    Write-Host '[4/5] Applying fast-forward update'
    if ((Git-Run -arguments @('status','--porcelain','--untracked-files=no')) -ne '' -or (Git-Run -arguments @('rev-parse','HEAD')) -ne $previous) { throw 'Checkout changed during preparation; update cancelled.' }
    Git-Run -arguments @('merge','--ff-only',$latest) | Out-Null
    $applied = $true
    if ($dependenciesChanged) {
        if (Test-Path -LiteralPath $vendor) {
            Move-Item -LiteralPath $vendor -Destination $backupVendor
            $vendorMoved = $true
        }
        Move-Item -LiteralPath $preparedVendor -Destination $vendor
        $vendorInstalled = $true
    }
    Write-Host '[5/5] Checking installed version'
    if ((Git-Run -arguments @('rev-parse','HEAD')) -ne $latest -or !(Test-Path (Join-Path $root 'backend\vendor\autoload.php'))) { throw 'Installed version validation failed.' }
    Write-Host "Update complete: $latest"
    $succeeded = $true
} catch {
    Write-Host "Update failed: $($_.Exception.Message)"
    if ($applied -and (Git-Run -arguments @('status','--porcelain','--untracked-files=no')) -eq '') {
        # Checkout was clean before and after applying; restore program files only.
        Git-Run -arguments @('reset','--hard',$previous) | Out-Null
        if ($vendorInstalled) { Move-Item -LiteralPath $vendor -Destination (Join-Path $stage 'failed-vendor') }
        if ($vendorMoved) { Move-Item -LiteralPath $backupVendor -Destination $vendor }
        $vendorMoved = $false
        Write-Host "Program files restored to: $previous"
    }
    exit 1
} finally {
    $resolvedStage = [IO.Path]::GetFullPath($stage)
    if (($succeeded -or !$vendorMoved) -and $resolvedStage.StartsWith($tempRoot,[StringComparison]::OrdinalIgnoreCase) -and (Split-Path $resolvedStage -Leaf) -like 'v3u-*' -and (Test-Path -LiteralPath $resolvedStage)) {
        try { Remove-Item -LiteralPath $resolvedStage -Recurse -Force -ErrorAction Stop }
        catch { Write-Host "Temporary cleanup incomplete; remove this folder later: $resolvedStage" }
    }
}
