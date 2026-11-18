<?php
declare(strict_types=1);
require __DIR__.'/../app/Reports.php';
require __DIR__.'/../app/ReportOutput.php';
require __DIR__.'/../app/ReportExport.php';
function checkExport(bool $ok,string $text):void{if(!$ok)throw new RuntimeException($text);echo "OK $text\n";}
$report=['type'=>'sales','title'=>'Facturación por operación','as_of'=>'2026-11-14','generated_at'=>'2026-11-14 18:00','note'=>'Importes originales. IVA incluido.','filter_labels'=>['Desde'=>'2026-11-01','Cliente'=>'Razón social Ñandutí'],'summary'=>[['label'=>'Facturado','value'=>'1122000','format'=>'money']],'columns'=>[['key'=>'number','label'=>'Factura','format'=>'text'],['key'=>'customer','label'=>'Cliente','format'=>'text'],['key'=>'total','label'=>'Facturado (Gs.)','format'=>'money']],'rows'=>[],'pagination'=>['total'=>61]];
for($i=1;$i<=61;$i++)$report['rows'][]=['number'=>sprintf('001-001-%07d',$i),'customer'=>$i===1?'=HYPERLINK("riesgo")':($i===2?'<f>SUM(A1)</f>':'Cliente Ñandutí '.$i),'total'=>'11000'];
$html=ReportExport::html($report);
checkExport(str_contains($html,'assets/informes-imprimir.css')&&str_contains($html,'001-001-0000061'),'plantilla compartida contiene estilo y resultado completo');
checkExport(str_contains($html,'&lt;f&gt;SUM(A1)&lt;/f&gt;'),'plantilla escapa contenido de clientes');
$xlsx=ReportExport::xlsx($report);checkExport(str_starts_with($xlsx,'PK'),'Excel es un XLSX real');
$pdf=ReportExport::pdf($report);checkExport(str_starts_with($pdf,'%PDF-'),'PDF es un archivo real');
file_put_contents(__DIR__.'/../artifacts/informe-exportacion-prueba.xlsx',$xlsx);
file_put_contents(__DIR__.'/../artifacts/informe-exportacion-prueba.pdf',$pdf);
echo "Exportaciones generadas con datos temporales.\n";
