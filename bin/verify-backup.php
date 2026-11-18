<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../app/Backup.php';
try{$config=require __DIR__.'/../app/config.local.php';$file=$argv[1]??'';if(!is_file($file))throw new RuntimeException('Indicá el manifiesto JSON del respaldo.');
    $result=(new Backup($config))->testRestore($file,['user'=>getenv('VACAREL_DB_ADMIN')?:'root','password'=>getenv('VACAREL_DB_ADMIN_PASSWORD')?:'']);echo 'Restauración aislada verificada: '.count($result['counts'])." tablas. La base temporal fue eliminada.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
