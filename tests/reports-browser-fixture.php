<?php
// Seeds the already-guarded disposable database, never the company's database.
require __DIR__.'/browser-fixture.php';
$sale=['customer_id'=>2,'condition'=>'credit','credit_days'=>30,'lines'=>[['product_id'=>1,'quantity'=>2,'tax'=>10]],'key'=>'reports-browser-sale','expected_number'=>'001-001-0000001'];
$preview=$billing->preview($sale);$sale['quote_token']=$preview['quote_token'];$billing->confirm($sale);
$domain->save('products',['code'=>'PRUEBA-02','name'=>'Producto para reponer','price'=>15000,'box_size'=>6,'tax'=>5,'min_stock'=>20,'track_expiry'=>1]);
$domain->receive(['key'=>'reports-browser-receipt','product_id'=>2,'supplier_id'=>1,'quantity'=>4,'presentation'=>'unit','cost'=>10000,'reference'=>'Recepción temporal','lot_code'=>'POR-VENCER','expiry'=>date('Y-m-d',strtotime('+5 days'))]);
