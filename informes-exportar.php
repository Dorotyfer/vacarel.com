<?php
require __DIR__.'/app/report-request.php';
try{
    if($format==='csv'){$stream=fopen('php://temp','w+b');ReportOutput::csv($report,$stream);rewind($stream);$body=stream_get_contents($stream);fclose($stream);$mime='text/csv; charset=utf-8';}
    else{require __DIR__.'/app/ReportExport.php';$body=$format==='pdf'?ReportExport::pdf($report):ReportExport::xlsx($report);$mime=$format==='pdf'?'application/pdf':'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';}
    header('Content-Type: '.$mime);header('Content-Disposition: attachment; filename="vacarel-'.$report['type'].'-'.$report['as_of'].'.'.$format.'"');
    header('Content-Length: '.strlen($body));echo $body;
}catch(Throwable $e){error_log($e->getMessage());http_response_code(503);header('Content-Type: text/plain; charset=utf-8');echo 'No se pudo generar el archivo. Intentá nuevamente.';}
