param([switch]$NoBrowser,[switch]$BackupOnly,[switch]$NoDialog)
$ErrorActionPreference='Stop'
$projectDir=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$xamppDir=Split-Path (Split-Path $projectDir -Parent) -Parent
$php=Join-Path $xamppDir 'php/php.exe'
$localDir=Join-Path $env:LOCALAPPDATA 'Vacarel'
$logDir=Join-Path $localDir 'Logs'
New-Item -ItemType Directory -Force -Path $logDir | Out-Null
$mutex=New-Object Threading.Mutex($false,'Local\VacarelLocalStart')
$locked=$false
function Test-Port([int]$Port){$socket=New-Object Net.Sockets.TcpClient;try{$task=$socket.ConnectAsync('127.0.0.1',$Port);if(!$task.Wait(750)){return $false};return $socket.Connected}catch{return $false}finally{$socket.Dispose()}}
function Wait-Port([int]$Port){for($i=0;$i -lt 40;$i++){if(Test-Port $Port){return};Start-Sleep -Milliseconds 250};throw "No respondió el servicio local en el puerto $Port."}
function Show-Info([string]$Text,[bool]$ErrorMessage=$false){if(!$NoDialog){Add-Type -AssemblyName System.Windows.Forms;[Windows.Forms.MessageBox]::Show($Text,'Vacarel',[Windows.Forms.MessageBoxButtons]::OK,$(if($ErrorMessage){[Windows.Forms.MessageBoxIcon]::Error}else{[Windows.Forms.MessageBoxIcon]::Information})) | Out-Null}}
try{
    $locked=$mutex.WaitOne(0);if(!$locked){return}
    if(!(Test-Path -LiteralPath $php)){throw 'No se encontró PHP de XAMPP.'}
    if(!(Test-Port 3306)){
        $mysql=Join-Path $xamppDir 'mysql/bin/mysqld.exe';$ini=Join-Path $xamppDir 'mysql/bin/my.ini'
        Start-Process -FilePath $mysql -ArgumentList ('--defaults-file="'+$ini+'"'),'--skip-grant-tables=0','--bind-address=127.0.0.1' -WorkingDirectory $xamppDir -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logDir 'mysql-start.out.log') -RedirectStandardError (Join-Path $logDir 'mysql-start.err.log') | Out-Null
        Wait-Port 3306
    }
    $checkText=(& $php (Join-Path $PSScriptRoot 'check.php') 2>&1 | Out-String);if($LASTEXITCODE -ne 0){throw 'La instalación requiere atención. Revisá la conexión con MariaDB, la configuración y las dependencias.'};$check=$checkText|ConvertFrom-Json
    $backupArgs=@((Join-Path $PSScriptRoot 'backup.php'));if(!$BackupOnly){$backupArgs+='--daily'}
    $backupText=(& $php @backupArgs 2>&1 | Out-String);if($LASTEXITCODE -ne 0){throw 'No se pudo crear o verificar el respaldo. Revisá la carpeta local de respaldos antes de continuar.'}
    if($BackupOnly){Show-Info "Respaldo creado y verificado.`n`n$($check.backup_directory)";return}
    if(!(Test-Port 8088)){
        Start-Process -FilePath $php -ArgumentList '-S','127.0.0.1:8088',('"'+(Join-Path $projectDir 'router.php')+'"') -WorkingDirectory $projectDir -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logDir 'web.out.log') -RedirectStandardError (Join-Path $logDir 'web.err.log') | Out-Null
        Wait-Port 8088
    }
    $homeResponse=Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8088/' -TimeoutSec 15
    if($homeResponse.StatusCode -ne 200 -or $homeResponse.Headers['X-Vacarel-App'] -ne 'vacarel-local'){throw 'El puerto 8088 está ocupado por otro sitio. No se abrió para evitar confusiones.'}
    if(!$NoBrowser){Start-Process 'http://127.0.0.1:8088/'}
    [pscustomobject]@{TechnicalReady=$true;Url='http://127.0.0.1:8088/';BackupDirectory=$check.backup_directory}
}catch{
    $_.Exception.Message | Set-Content -LiteralPath (Join-Path $logDir 'ultimo-error.txt') -Encoding UTF8
    Show-Info ($_.Exception.Message+"`n`nLos detalles están en: $logDir") $true
    if($NoDialog){throw}
}finally{if($locked){$mutex.ReleaseMutex()};$mutex.Dispose()}
