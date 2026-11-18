$ErrorActionPreference='Stop'
$projectDir=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$desktop=[Environment]::GetFolderPath('Desktop')
$shell=New-Object -ComObject WScript.Shell
$iconFile=Join-Path $projectDir 'assets/vacarel.ico'
Add-Type -AssemblyName System.Drawing
$source=[Drawing.Image]::FromFile((Join-Path $projectDir 'assets/vacarel-logo-srl.png'))
$bitmap=New-Object Drawing.Bitmap(256,256)
$graphics=[Drawing.Graphics]::FromImage($bitmap);$graphics.Clear([Drawing.Color]::White);$graphics.DrawImage($source,0,43,256,171)
$brandIcon=[Drawing.Icon]::FromHandle($bitmap.GetHicon());$iconStream=[IO.File]::Create($iconFile);$brandIcon.Save($iconStream);$iconStream.Close();$graphics.Dispose();$bitmap.Dispose();$source.Dispose()
foreach($item in @(@('Vacarel',''),@('Respaldar Vacarel',' backup'))){
    $shortcut=$shell.CreateShortcut((Join-Path $desktop ($item[0]+'.lnk')))
    $shortcut.TargetPath=Join-Path $env:WINDIR 'System32/wscript.exe'
    $shortcut.Arguments='"'+(Join-Path $PSScriptRoot 'launch.vbs')+'"'+$item[1]
    $shortcut.WorkingDirectory=$projectDir;$shortcut.Description='Vacarel - gestión local';$shortcut.IconLocation=$iconFile+',0';$shortcut.Save()
}
Write-Output 'Accesos Vacarel y Respaldar Vacarel creados en el escritorio.'
