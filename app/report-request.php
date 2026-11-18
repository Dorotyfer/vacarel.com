<?php
// Shared authenticated controller for report downloads and printable results.
require __DIR__.'/bootstrap.php';
require __DIR__.'/ReportOutput.php';
try {
    $db=database();$user=currentUser($db);if(!$user)throw new DomainException('Ingresá al sistema para consultar el informe.',401);
    $type=$_GET['type']??'';if(!is_string($type))throw new DomainException('Informe inválido.',422);
    $format=$_GET['format']??'csv';
    if(!is_string($format)||!in_array($format,['csv','xlsx','pdf'],true))throw new DomainException('Formato de exportación inválido.',422);
    $filters=$_GET;unset($filters['type'],$filters['format']);
    $report=(new Reports($db,$user))->run($type,$filters,false);
}catch(Throwable $e){
    $status=$e instanceof DomainException && in_array($e->getCode(),[401,403,422],true)?$e->getCode():503;
    http_response_code($status);header('Content-Type: text/html; charset=utf-8');
    $message=$status===503?'No se pudo consultar el informe. Intentá nuevamente.':$e->getMessage();
    if($status===503)error_log($e->getMessage());
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Informe no disponible</title><p>'.htmlspecialchars($message,ENT_QUOTES,'UTF-8').'</p><a href="./#reports">Volver a informes</a></html>';exit;
}
