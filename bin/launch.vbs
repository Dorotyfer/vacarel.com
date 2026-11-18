Option Explicit
Dim shell, fs, scriptDir, command
Set shell = CreateObject("WScript.Shell")
Set fs = CreateObject("Scripting.FileSystemObject")
scriptDir = fs.GetParentFolderName(WScript.ScriptFullName)
command = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File """ & scriptDir & "\start-local.ps1"""
If WScript.Arguments.Count > 0 Then
  If WScript.Arguments(0) = "backup" Then command = command & " -BackupOnly"
End If
shell.Run command, 0, False
