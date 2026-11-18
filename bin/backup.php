<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../app/Backup.php';require __DIR__.'/../app/LocalSetup.php';
try{$config=require __DIR__.'/../app/config.local.php';$backup=new Backup($config);$dir=LocalSetup::backupDirectory();
    if(in_array('--daily',$argv,true)){foreach(glob($dir.'/vacarel-'.gmdate('Ymd').'-*.json')?:[] as $f){Backup::verify($f);echo "Ya existe un respaldo íntegro de hoy.\n";exit;}}
    $manifest=$backup->create($dir);Backup::verify($manifest);echo "Respaldo creado y verificado:\n$manifest\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
