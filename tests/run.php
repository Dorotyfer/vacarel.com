<?php
declare(strict_types=1);
require __DIR__.'/../app/Domain.php';
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec(str_replace('INTEGER PRIMARY KEY AUTO_INCREMENT','INTEGER PRIMARY KEY AUTOINCREMENT',file_get_contents(__DIR__.'/../database/schema.sql')));
$db->exec("INSERT INTO users(name,username,password,role) VALUES('Prueba','testadmin','x','admin')");
$app = new Domain($db, ['id'=>1,'role'=>'admin']);
function check(bool $ok, string $label): void { if (!$ok) throw new RuntimeException($label); echo "OK $label\n"; }
function rejects(callable $f, string $label): void { try {$f();} catch (DomainException $e) {echo "OK $label\n";return;} throw new RuntimeException($label); }
$app->save('products', ['code'=>'A1','name'=>'Arroz','box_size'=>12,'price'=>10000,'min_stock'=>5,'tax'=>10,'track_expiry'=>1]);
$app->save('suppliers', ['name'=>'Proveedor','document'=>'123','phone'=>'']);
$receipt=['product_id'=>1,'supplier_id'=>1,'quantity'=>2,'presentation'=>'box','lot_code'=>'L1','expiry'=>date('Y-m-d',strtotime('+60 days')),'cost'=>120000,'reference'=>'Compra 1','key'=>'test-1'];
$app->receive($receipt);
check((int)$db->query('SELECT SUM(quantity) FROM lots')->fetchColumn()===24,'dos cajas ingresan 24 unidades');
$app->receive($receipt);
check((int)$db->query('SELECT COUNT(*) FROM movements')->fetchColumn()===1,'reintento no duplica stock');
$changed=$receipt; $changed['quantity']=3;
rejects(fn()=>$app->receive($changed),'clave repetida con otro contenido rechazada');
$invalid=$receipt; $invalid['key']='bad'; $invalid['quantity']=-1;
rejects(fn()=>$app->receive($invalid),'cantidad negativa rechazada');
$invalid=$receipt; $invalid['key']='bad2'; $invalid['expiry']='2020-01-01';
rejects(fn()=>$app->receive($invalid),'recepción vencida rechazada');
$seller=new Domain($db,['id'=>1,'role'=>'vendedor']);
rejects(fn()=>$seller->receive($receipt),'vendedor no recibe mercadería');
rejects(fn()=>$seller->list('products'),'vendedor no consulta costos del catálogo administrativo');
$before=$db->query('SELECT COUNT(*) FROM audit')->fetchColumn();
$app->save('products',['id'=>1,'code'=>'A1','name'=>'Arroz','box_size'=>12,'price'=>12000,'min_stock'=>5,'tax'=>10,'track_expiry'=>1]);
check($db->query('SELECT COUNT(*) FROM audit')->fetchColumn()>$before,'cambio de precio auditado');
$expired=$receipt; $expired['key']='test-2'; $expired['quantity']=1; $expired['presentation']='unit'; $expired['cost']=9000; $expired['lot_code']='L2';
$app->receive($expired);
check((int)$db->query('SELECT SUM(quantity) FROM lots')->fetchColumn()===25,'unidad se conserva sin conversión');
check((float)$db->query('SELECT average_cost FROM products WHERE id=1')->fetchColumn()===9960.0,'costo promedio ponderado');
$duplicate=['code'=>'A1','name'=>'Duplicado','box_size'=>1,'price'=>1,'min_stock'=>0,'tax'=>10,'track_expiry'=>0];
try {$app->save('products',$duplicate); throw new RuntimeException('duplicado aceptado');} catch(PDOException $e) {echo "OK código único\n";}
$db->exec("UPDATE lots SET expiry='2020-01-01'");
check(count($app->dashboard()['low_stock'])===1,'mínimos excluyen mercadería vencida');
$collaborator=['name'=>'María','username'=>'maria.ventas','password'=>'Clave de prueba 123','role'=>'vendedor','active'=>1,'commission_rate'=>'3','commission_basis'=>'collected'];
$newUser=$app->save('collaborators',$collaborator);
$credentials=$db->query('SELECT username,password FROM users WHERE id='.(int)$newUser)->fetch(PDO::FETCH_ASSOC);
check($credentials['username']==='maria.ventas','colaborador usa nombre de usuario sin correo');
check($credentials['password']===$collaborator['password'],'contraseña almacenada tal cual por configuración solicitada');
$collaborator['id']=$newUser;unset($collaborator['password']);$app->save('collaborators',$collaborator);
check($db->query('SELECT password FROM users WHERE id='.(int)$newUser)->fetchColumn()==='Clave de prueba 123','editar sin contraseña conserva la existente');
check(!array_key_exists('password',$app->list('collaborators')[1]),'listado no expone contraseñas');
check(!str_contains((string)$db->query('SELECT after_data FROM audit ORDER BY id DESC LIMIT 1')->fetchColumn(),'Clave de prueba'),'auditoría no copia contraseña');
$invalidUser=$collaborator;$invalidUser['username']='maria@correo.com';
rejects(fn()=>$app->save('collaborators',$invalidUser),'correo rechazado como nombre de usuario');
echo "Todas las pruebas pasaron.\n";
