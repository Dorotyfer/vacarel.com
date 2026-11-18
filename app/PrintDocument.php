<?php
declare(strict_types=1);
function printDocument(Billing $billing,bool $test,int $id): array
{
    if(!$test)return $billing->document($id);
    $billing->authorize(true);$settings=$billing->settings();
    $sample=InvoiceMath::calculate([
        ['code'=>'PR-001','name'=>'Producto exento de prueba','quantity'=>2,'price'=>'1000','tax'=>0],
        ['code'=>'PR-002','name'=>'Producto al cinco por ciento','quantity'=>1,'price'=>'21000','tax'=>5],
        ['code'=>'PR-003','name'=>'Producto al diez por ciento','quantity'=>2,'price'=>'11000','tax'=>10]
    ],true);
    $sample+=['customer'=>['name'=>'PRUEBA DE ALINEACIÓN','document'=>'00000000-0','address'=>'DATOS FICTICIOS — PAPEL COMÚN','phone'=>'0000000000'],'date'=>date('Y-m-d'),'condition'=>'credit','credit_days'=>30,'template'=>$settings,'number'=>'PRUEBA'];
    return ['id'=>0,'number'=>'PRUEBA','snapshot'=>$sample,'attempts'=>[]];
}
function invoiceFields(array $snapshot): array
{
    return ['date'=>date('d/m/Y',strtotime($snapshot['date'])),'name'=>$snapshot['customer']['name'],'ruc'=>$snapshot['customer']['document'],'address'=>$snapshot['customer']['address'],'phone'=>$snapshot['customer']['phone'],'cash_x'=>$snapshot['condition']==='cash'?'X':'','credit_x'=>$snapshot['condition']==='credit'?'X':'','term'=>$snapshot['condition']==='credit'?$snapshot['credit_days'].' días':'','total'=>number_format((float)$snapshot['total'],0,',','.'),'iva5'=>number_format((float)$snapshot['iva5'],0,',','.'),'iva10'=>number_format((float)$snapshot['iva10'],0,',','.')];
}
