<?php
declare(strict_types=1);
require __DIR__.'/../app/Backup.php';
$name='vacarel_test_'.bin2hex(random_bytes(6));$db=new PDO('mysql:host=127.0.0.1;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$dir=sys_get_temp_dir().'/vacarel-backup-test-'.bin2hex(random_bytes(6));$config=['dsn'=>'mysql:host=127.0.0.1;dbname='.$name.';charset=utf8mb4','user'=>'root','password'=>''];
try{
    $db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");$db->exec("USE `$name`");$db->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
    $db->prepare("INSERT INTO users(name,username,password,role) VALUES(?,?,?,'admin')")->execute(["Prueba Ñ ' \\ ", 'test','contraseña de prueba']);
    $db->exec("INSERT INTO products(id,code,name,price,tax) VALUES(1,'RESP','Producto',11000,10)");
    $db->exec("INSERT INTO parties(id,kind,name) VALUES(1,'suppliers','Origen'),(2,'customers','Destino')");
    $db->exec("INSERT INTO lots(product_id,supplier_id,code,quantity,unit_cost,reference,received_at,user_id) VALUES(1,1,'L1',7,9000,'Prueba','2026-11-14T12:00:00+00:00',1)");
    $db->exec("INSERT INTO sales(operation_key,payload_hash,customer_id,user_id,number,fiscal_key,issued_on,condition_name,credit_days,due_on,total,balance,snapshot,created_at) VALUES('restore','hash',2,1,'001-001-0000001','restore/1','2026-11-14','credit',30,'2026-12-14',22000,11000,'{}','2026-11-14T12:00:00+00:00')");
    $backup=new Backup($config);$manifest=$backup->create($dir);$verified=Backup::verify($manifest);
    if(!is_file($verified['sql'])||!str_contains(file_get_contents($verified['sql']),'CREATE TABLE'))throw new RuntimeException('Respaldo vacío');
    echo "OK respaldo fuera de carpeta web y manifiesto íntegro\n";
    $result=$backup->testRestore($manifest,['user'=>'root','password'=>'']);
    if($result['counts']['users']!==1)throw new RuntimeException('Restauración incompleta');echo "OK restauración aislada conserva filas\n";
    if($result['balances']!==['stock'=>'7','invoiced'=>'22000','outstanding'=>'11000'])throw new RuntimeException('Saldos restaurados incorrectos');echo "OK restauración conserva stock, facturación y deuda\n";
    file_put_contents($verified['sql'],"\nALTERADO",FILE_APPEND);
    try{Backup::verify($manifest);throw new RuntimeException('Alteración no detectada');}catch(DomainException $e){echo "OK alteración detectada\n";}
}finally{
    if(preg_match('/^vacarel_test_[a-f0-9]{12}$/D',$name))$db->exec("DROP DATABASE IF EXISTS `$name`");
    foreach(glob($dir.'/*')?:[] as $file)unlink($file);if(is_dir($dir))rmdir($dir);
}
