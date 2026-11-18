<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$config=require __DIR__.'/../app/config.local.php';
$db=new PDO($config['dsn'],getenv('VACAREL_DB_ADMIN')?:'root',getenv('VACAREL_DB_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec(file_get_contents(__DIR__.'/../database/billing.sql'));
if(!$db->query("SHOW COLUMNS FROM lots LIKE 'purchase_tax'")->fetch())$db->exec('ALTER TABLE lots ADD COLUMN purchase_tax INTEGER NULL');
echo "Tablas de venta, reservas, impresión y configuración disponibles. No se habilitó emisión fiscal.\n";
