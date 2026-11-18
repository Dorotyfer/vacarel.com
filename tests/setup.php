<?php
declare(strict_types=1);
require __DIR__.'/../app/LocalSetup.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec(str_replace('INTEGER PRIMARY KEY AUTO_INCREMENT','INTEGER PRIMARY KEY AUTOINCREMENT',file_get_contents(__DIR__.'/../database/schema.sql')));
function setupCheck(bool $ok,string $text):void{if(!$ok)throw new RuntimeException($text);echo "OK $text\n";}
$setup=new LocalSetup($db,['id'=>1,'role'=>'admin']);$result=$setup->status();
setupCheck($result['ready_for_full_operation']===false,'instalación vacía no se presenta como operación completa');
setupCheck(count($result['pending_modules'])>0,'módulos faltantes identificados');
setupCheck(!str_contains(json_encode($result),'password'),'diagnóstico no divulga contraseñas');
setupCheck(in_array('products',array_column($result['checks'],'id'),true),'carga inicial de productos comprobada');
try{(new LocalSetup($db,['id'=>1,'role'=>'vendedor']))->status();throw new RuntimeException('Acceso incorrecto');}catch(DomainException $e){setupCheck($e->getCode()===403,'preparación restringida por servidor');}
