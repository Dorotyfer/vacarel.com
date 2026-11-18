<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
$config=require __DIR__.'/../app/config.local.php';
$db=new PDO($config['dsn'],getenv('VACAREL_DB_ADMIN')?:'root',getenv('VACAREL_DB_ADMIN_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if($db->query("SHOW COLUMNS FROM users LIKE 'username'")->fetch()) {echo "La migración ya fue aplicada.\n";exit;}
$users=$db->query('SELECT id,email,password FROM users ORDER BY id')->fetchAll();
$accessPath=__DIR__.'/../app/initial-access.txt';
$access=is_file($accessPath)?file_get_contents($accessPath):'';
preg_match('/Contraseña inicial: ([^\r\n]+)/u',$access,$match);
$password=getenv('VACAREL_ADMIN_PASSWORD')?:($match[1]??'');
// No es posible recuperar el texto original de un hash. No inventar ni reemplazar
// contraseñas de otros colaboradores durante esta migración de la cuenta inicial.
if(count($users)!==1 || $password==='' || !password_verify($password,$users[0]['password'])) {
    fwrite(STDERR,"Se necesita la contraseña original de la cuenta inicial y una migración específica si existen otros usuarios. No se modificaron datos.\n");exit(1);
}
$db->exec('ALTER TABLE users CHANGE email username VARCHAR(190) NOT NULL');
$db->beginTransaction();
try {
    $db->prepare('UPDATE users SET username=?,password=? WHERE id=?')->execute(['admin',$password,$users[0]['id']]);
    $db->prepare('INSERT INTO audit(user_id,action,resource,resource_id,before_data,after_data,created_at) VALUES(?,?,?,?,?,?,?)')->execute([$users[0]['id'],'migrate_username','users',$users[0]['id'],json_encode(['email'=>$users[0]['email']]),json_encode(['username'=>'admin']),gmdate('c')]);
    $db->commit();
} catch(Throwable $e) {$db->rollBack();throw $e;}
if(is_file($accessPath)) file_put_contents($accessPath,preg_replace('/Correo: [^\r\n]+/u','Usuario: admin',$access));
echo "Migración aplicada. Usuario: admin. Se conserva la contraseña inicial.\n";
