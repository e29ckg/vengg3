param(
    [string]$XamppRoot = 'C:\xampp',
    [string]$TaskName = 'Vengg3 Telegram Daily Schedule',
    [switch]$Enable
)

$ErrorActionPreference = 'Stop'
$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principalCheck = [Security.Principal.WindowsPrincipal]::new($identity)
if (-not $principalCheck.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Run this script from an elevated PowerShell (Run as administrator).'
}
$php = Join-Path $XamppRoot 'php\php.exe'
$script = Join-Path $XamppRoot 'htdocs\vengg3\backend\cron\daily_notify.php'
if (-not (Test-Path -LiteralPath $php -PathType Leaf)) { throw "PHP not found: $php" }
if (-not (Test-Path -LiteralPath $script -PathType Leaf)) { throw "Notification script not found: $script" }

$action = New-ScheduledTaskAction -Execute $php -Argument ('-f "{0}"' -f $script) -WorkingDirectory (Split-Path $script)
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration (New-TimeSpan -Days 3650)
$principal = New-ScheduledTaskPrincipal -UserId 'NT AUTHORITY\NETWORK SERVICE' -LogonType ServiceAccount -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 1) -MultipleInstances IgnoreNew -StartWhenAvailable
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force -ErrorAction Stop | Out-Null
if (-not $Enable) { Disable-ScheduledTask -TaskName $TaskName -ErrorAction Stop | Out-Null }
Get-ScheduledTask -TaskName $TaskName -ErrorAction Stop | Select-Object TaskName, State
