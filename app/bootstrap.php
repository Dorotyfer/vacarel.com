<?php
declare(strict_types=1);
date_default_timezone_set('America/Asuncion');
ini_set('display_errors','0');
ini_set('session.use_strict_mode','1');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Lax','path'=>rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/').'/']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Vacarel-App: vacarel-local');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('Cache-Control: no-store');
require __DIR__.'/Domain.php';
require __DIR__.'/Billing.php';
require __DIR__.'/Reports.php';
require __DIR__.'/LocalSetup.php';
function database(): PDO {
    if(getenv('VACAREL_DSN')) $config=['dsn'=>getenv('VACAREL_DSN'),'user'=>getenv('VACAREL_DB_USER')?:'root','password'=>getenv('VACAREL_DB_PASSWORD')?:''];
    elseif(is_file(__DIR__.'/config.local.php')) $config=require __DIR__.'/config.local.php';
    else throw new RuntimeException('Instalación pendiente. Ejecutá php bin/install.php.');
    return new PDO($config['dsn'],$config['user'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
}
function currentUser(PDO $db): ?array {
    if(empty($_SESSION['user_id']) || time()-($_SESSION['activity']??0)>1800) {unset($_SESSION['user_id']);return null;}
    $stmt=$db->prepare('SELECT id,name,username,role,active FROM users WHERE id=? AND active=1');$stmt->execute([$_SESSION['user_id']]);
    $user=$stmt->fetch();if(!$user) return null;
    $_SESSION['activity']=time();return $user;
}
$_SESSION['csrf']??=bin2hex(random_bytes(32));
