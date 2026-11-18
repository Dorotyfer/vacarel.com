<?php
if(PHP_SAPI!=='cli') exit;
$name=$argv[1]??'';
if(!preg_match('/^vacarel_test_[a-f0-9]{12}$/D',$name)) throw new RuntimeException('Nombre de prueba inválido');
$db=new PDO('mysql:host=127.0.0.1;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(($argv[2]??'')==='drop') {$db->exec("DROP DATABASE `$name`");exit;}
$db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");$db->exec("USE `$name`");$db->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
$s=$db->prepare('INSERT INTO users(name,username,password,role) VALUES(?,?,?,?)');
$s->execute(['Administrador de prueba','admin','Test-only-Password-123','admin']);
$s->execute(['Vendedor de prueba','seller','Test-only-Password-123','vendedor']);
