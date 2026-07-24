param([string]$Time = "08:00")

$project = Split-Path -Parent $PSScriptRoot
$runner = Join-Path $project "scripts\run_reminders.cmd"
if (-not (Test-Path -LiteralPath $runner)) { throw "Khong tim thay runner: $runner" }
$quotedRunner = [char]34 + $runner + [char]34
schtasks.exe /Create /SC DAILY /ST $Time /TN "TodoPHPDeadlineReminder" /TR $quotedRunner /F
if ($LASTEXITCODE -ne 0) { throw "Khong tao duoc Task Scheduler." }
Write-Host "Da tao TodoPHPDeadlineReminder, chay moi ngay luc $Time."
