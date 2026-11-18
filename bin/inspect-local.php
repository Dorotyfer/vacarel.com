<?php
if(PHP_SAPI!=='cli')exit;
$c=require __DIR__.'/../app/config.local.php';
$db=new PDO($c['dsn'],$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$counts=[];foreach(['users','products','parties','lots','sales','movements'] as $table)$counts[$table]=(int)$db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
$s=$db->query('SELECT payload FROM billing_settings WHERE id=1')->fetchColumn();$settings=$s?json_decode($s,true):[];
echo json_encode(['counts'=>$counts,'database_version'=>$db->query('SELECT VERSION()')->fetchColumn(),'skip_grant_tables'=>$db->query("SHOW VARIABLES LIKE 'skip_grant_tables'")->fetch(PDO::FETCH_ASSOC)['Value']??null,'fiscal'=>array_intersect_key($settings,array_flip(['enabled','fiscal_checked','calibrated','timbrado','start','end','next','valid_from','valid_until']))],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
try{new PDO('mysql:host=127.0.0.1;dbname=vacarel','vacarel_nonexistent_probe','invalid',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);echo "UNKNOWN_ACCOUNT_ACCEPTED\n";}catch(PDOException $e){echo "UNKNOWN_ACCOUNT_REJECTED\n";}
