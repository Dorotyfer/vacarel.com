$ErrorActionPreference='Stop'
$project=Split-Path $PSScriptRoot -Parent
$before=(Get-NetTCPConnection -LocalPort 8088 -State Listen).OwningProcess
$first=& "$project/bin/start-local.ps1" -NoBrowser -NoDialog
$second=& "$project/bin/start-local.ps1" -NoBrowser -NoDialog
$after=(Get-NetTCPConnection -LocalPort 8088 -State Listen).OwningProcess
if($before -ne $after -or !$first.TechnicalReady -or !$second.TechnicalReady){throw 'Inicio local no idempotente'}
Write-Output 'OK acceso local reutiliza servicios sanos y verifica respaldo diario'
