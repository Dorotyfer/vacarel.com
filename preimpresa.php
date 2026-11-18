<?php
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/PrintDocument.php';
function h(string $s): string {return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
try {
    $db=database();$user=currentUser($db);if(!$user){http_response_code(401);exit('Ingresá al sistema para imprimir.');}
    $test=($_GET['test']??'')==='1';$id=(int)($_GET['id']??0);$billing=new Billing($db,$user);$doc=printDocument($billing,$test,$id);$snapshot=$doc['snapshot'];$settings=$snapshot['template'];$fields=invoiceFields($snapshot);$rows=InvoiceLayout::rows($snapshot['lines'],$settings);
}catch(DomainException $e){http_response_code($e->getCode()?:422);exit(h($e->getMessage()));}catch(Throwable $e){error_log($e->getMessage());http_response_code(503);exit('No se pudo cargar el documento.');}
$params=$test?'test=1':'id='.$id;
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="<?=h($_SESSION['csrf'])?>"><title><?=h($test?'Prueba de alineación':'Factura '.$doc['number'])?></title><link rel="stylesheet" href="assets/preimpresa.css"><link rel="stylesheet" href="preimpresa-estilo.php?<?=$params?>"><script defer src="assets/preimpresa.js"></script></head>
<body data-sale-id="<?=$id?>" data-test="<?=$test?'1':'0'?>" data-auto="<?=($_GET['auto']??'')==='1'?'1':'0'?>">
<header class="print-tools"><h1><?=$test?'Prueba sobre papel común':'Factura '.h($doc['number'])?></h1><p><?=$test?'No utiliza numeración fiscal. Las posiciones son aproximadas hasta calibrar en la impresora.':'Verificá que el número del formulario físico coincida con el documento mostrado arriba.'?> La hoja impresa contiene únicamente los datos de llenado. Usá tamaño real (100 %), sin encabezados ni pies del navegador.</p>
<?php if($doc['attempts']): ?><label>Motivo de reimpresión <input id="print-reason" type="text" maxlength="255" placeholder="Indicá qué ocurrió con la impresión anterior"></label><?php endif; ?>
<button id="print-button" type="button">Imprimir<?=$test?' prueba':''?></button><a href="./#sales">Volver al sistema</a><p id="print-message" role="status"></p><div id="print-result" hidden><p>¿Qué ocurrió con el papel? Cerrar el diálogo no confirma que se haya impreso.</p><button data-result="printed" type="button">Impresión correcta</button><button data-result="failed" type="button">No imprimió / salió mal</button></div></header>
<main class="paper" aria-label="Datos que se imprimirán sobre el formulario">
<?php foreach($fields as $key=>$value): ?><div class="field field-<?=$key?>"><?=h($value)?></div><?php endforeach; ?>
<?php foreach($rows as $row): $slot=(int)$row['slot'];?><div class="field field-description slot-<?=$slot?>"><?=h($row['description'])?></div><?php if($row['line']): foreach(['code','quantity','unit_price','exempt','vat5_gross','vat10_gross'] as $key): $value=$row['line'][$key];if($value!=='' && in_array($key,['unit_price','exempt','vat5_gross','vat10_gross'],true))$value=number_format((float)$value,0,',','.');?><div class="field field-<?=$key?> slot-<?=$slot?>"><?=h((string)$value)?></div><?php endforeach;endif;endforeach; ?>
</main></body></html>
