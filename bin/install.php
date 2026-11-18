<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
if(is_file(__DIR__.'/../app/config.local.php')) {fwrite(STDERR,"Ya existe configuración. No se sobrescribió.\n");exit(1);}
$adminUsername=getenv('VACAREL_ADMIN_USERNAME')?:'admin';
$adminPassword=getenv('VACAREL_ADMIN_PASSWORD')?:bin2hex(random_bytes(10));
if(strlen($adminPassword)<12 || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,39}$/D',$adminUsername)) {fwrite(STDERR,"Usuario inválido o contraseña menor a 12 caracteres.\n");exit(1);}
$root=new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4',getenv('VACAREL_DB_ADMIN')?:'root',getenv('VACAREL_DB_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$exists=$root->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='vacarel'")->fetchColumn();
if($exists) {fwrite(STDERR,"La base vacarel ya existe. Instalación detenida para proteger sus datos.\n");exit(1);}
$root->exec('CREATE DATABASE vacarel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$root->exec('USE vacarel');
$root->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
$root->prepare("INSERT INTO users(name,username,password,role) VALUES(?,?,?,'admin')")->execute(['Administrador',$adminUsername,$adminPassword]);
$dbPassword=bin2hex(random_bytes(24));$dbUser='vacarel_'.bin2hex(random_bytes(4));
$account=$root->quote($dbUser)."@'127.0.0.1'";
$root->exec('CREATE USER '.$account.' IDENTIFIED BY '.$root->quote($dbPassword));
$root->exec('GRANT SELECT,INSERT,UPDATE,DELETE ON vacarel.* TO '.$account);
$config=['dsn'=>'mysql:host=127.0.0.1;port=3306;dbname=vacarel;charset=utf8mb4','user'=>$dbUser,'password'=>$dbPassword];
file_put_contents(__DIR__.'/../app/config.local.php',"<?php\nreturn ".var_export($config,true).";\n");
echo "Instalación completa.\nUsuario: $adminUsername\nContraseña inicial: $adminPassword\nGuardá estas credenciales; no se vuelven a mostrar.\n";
