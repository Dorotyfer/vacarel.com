<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../app/LocalSetup.php';
try{
    $c=require __DIR__.'/../app/config.local.php';$db=new PDO($c['dsn'],$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $status=(new LocalSetup($db,['id'=>1,'role'=>'admin']))->status();$technical=true;
    foreach($status['checks'] as $check)if(in_array($check['id'],['runtime','exports','database'],true)&&!$check['ok'])$technical=false;
    try{new PDO($c['dsn'],'vacarel_probe_'.bin2hex(random_bytes(4)),bin2hex(random_bytes(12)),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$technical=false;}catch(PDOException $e){if((int)($e->errorInfo[1]??0)!==1045)throw $e;}
    echo json_encode(['technical_ready'=>$technical,...$status],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";exit($technical?0:1);
}catch(Throwable $e){fwrite(STDERR,"No se pudo verificar la instalación local. Revisá la conexión, las tablas y las dependencias.\n");exit(1);}
