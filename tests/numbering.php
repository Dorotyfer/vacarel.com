<?php
require __DIR__.'/billing.php';
$numbering=$billing->numbering();ok((int)$numbering['next']===2,'numeración propone el siguiente formulario de la configuración');
$manual=['customer_id'=>2,'condition'=>'cash','credit_days'=>0,'lines'=>[['product_id'=>1,'quantity'=>1,'tax'=>10]],'physical_number'=>'001-001-0000005','number_reason'=>'El talonario continúa desde este formulario','key'=>'manual-number'];
$quote=$cashier->preview($manual);ok($quote['expected_number']==='001-001-0000005','operador puede cargar número físico antes de confirmar');
ok((int)$billing->settings()['next']===2,'revisar un número no avanza la secuencia');
$manual['expected_number']=$quote['expected_number'];$manual['quote_token']=$quote['quote_token'];$manualId=$cashier->confirm($manual);
ok($billing->document($manualId)['number']==='001-001-0000005'&&(int)$billing->settings()['next']===6,'confirmación usa número cargado y continúa su secuencia');
$reused=$manual;$reused['key']='reuse-manual';no(fn()=>$cashier->preview($reused),'número anterior o utilizado rechazado');
$missing=$manual;$missing['physical_number']='0000007';unset($missing['number_reason']);no(fn()=>$cashier->preview($missing),'salto exige motivo');
$wrong=$manual;$wrong['physical_number']='002-001-0000007';no(fn()=>$cashier->preview($wrong),'serie incorrecta rechazada');
$outside=$manual;$outside['physical_number']='0000099';no(fn()=>$cashier->preview($outside),'fuera de rango rechazado');
$snapshot=$billing->numbering();$snapshot['next']=7;$billing->saveNumbering($snapshot);ok((int)$billing->settings()['next']===7,'módulo de numeración actualiza la propuesta');
no(fn()=>$billing->saveNumbering($snapshot),'configuración desactualizada rechazada');
no(fn()=>$cashier->saveNumbering($billing->numbering()),'caja no cambia el rango autorizado');
