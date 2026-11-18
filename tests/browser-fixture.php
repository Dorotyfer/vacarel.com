<?php
// Datos ficticios exclusivamente para la base temporal validada por mysql-fixture.
require __DIR__.'/mysql-fixture.php';
require __DIR__.'/../app/Domain.php';require __DIR__.'/../app/Billing.php';
date_default_timezone_set('America/Asuncion');$user=['id'=>1,'role'=>'admin'];
$domain=new Domain($db,$user);$billing=new Billing($db,$user);
$domain->save('products',['code'=>'PRUEBA-01','name'=>'Producto de prueba','price'=>11000,'box_size'=>12,'tax'=>10,'min_stock'=>1,'track_expiry'=>1]);
$domain->save('suppliers',['name'=>'Proveedor de prueba']);
$domain->save('customers',['name'=>'Cliente de prueba','document'=>'00000000-0','address'=>'Dirección ficticia','phone'=>'0000000000','credit_limit'=>100000,'credit_days'=>30]);
$domain->receive(['key'=>'test-browser-receipt','product_id'=>1,'supplier_id'=>1,'quantity'=>10,'presentation'=>'unit','cost'=>9000,'reference'=>'Recepción de prueba','lot_code'=>'PRUEBA-LOTE','expiry'=>date('Y-m-d',strtotime('+1 year'))]);
$s=Billing::defaults();$s=array_replace($s,['timbrado'=>'99999999','start'=>1,'end'=>5,'next'=>1,'valid_from'=>date('Y-m-d',strtotime('-1 day')),'valid_until'=>date('Y-m-d',strtotime('+1 year')),'fiscal_checked'=>true,'calibrated'=>true,'enabled'=>true]);$billing->saveSettings($s);
