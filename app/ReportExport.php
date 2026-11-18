<?php
declare(strict_types=1);
require_once __DIR__.'/vendor/autoload.php';
final class ReportExport
{
    public static function html(array $report,bool $pdf=false):string
    {ob_start();require __DIR__.'/report-template.php';return ob_get_clean();}
    public static function pdf(array $report):string
    {
        $root=dirname(__DIR__);$cache=__DIR__.'/cache/report-fonts';
        if(!is_dir($cache)&&!mkdir($cache,0770,true)&&!is_dir($cache))throw new RuntimeException('No se pudo preparar la fuente de impresión.');
        $options=new Dompdf\Options(['isRemoteEnabled'=>false,'isPhpEnabled'=>false,'isJavascriptEnabled'=>false,'chroot'=>$root,'fontDir'=>$cache,'fontCache'=>$cache,'defaultFont'=>'Inter','isFontSubsettingEnabled'=>true]);
        $pdf=new Dompdf\Dompdf($options);$pdf->setBasePath($root.'/');$pdf->setPaper('A4','landscape');$pdf->loadHtml(self::html($report,true),'UTF-8');$pdf->render();
        $canvas=$pdf->getCanvas();$font=$pdf->getFontMetrics()->getFont('Inter','normal');$canvas->page_text(720,570,'Página {PAGE_NUM} de {PAGE_COUNT}',$font,8,[.42,.49,.53]);
        return $pdf->output();
    }
    private static function raw(mixed $value):string{return Shuchkin\SimpleXLSXGen::raw((string)($value??''));}
    private static function heading(string $label):string{return '<b><style bgcolor="#063E53" color="#FFFFFF" height="30"><wraptext><raw>'.htmlspecialchars($label,ENT_QUOTES|ENT_XML1,'UTF-8').'</raw></wraptext></style></b>';}
    private static function number(mixed $value,bool $money=false):mixed
    {
        $s=(string)$value;
        // Excel supports 15 significant digits; retain larger values as text.
        if(!preg_match('/^-?\d+(\.\d+)?$/D',$s)||strlen(ltrim(str_replace(['.','-'],'',$s),'0'))>15)return self::raw($s);
        return '<right><style nf="'.($money?'&quot;Gs. &quot;#,##0.####':'#,##0').'">'.$s.'</style></right>';
    }
    public static function xlsx(array $report):string
    {
        $note='<style height="75"><wraptext><raw>'.htmlspecialchars($report['note'],ENT_QUOTES|ENT_XML1,'UTF-8').'</raw></wraptext></style>';
        $summary=[[self::heading('VACAREL S.R.L. · '.$report['title']),null],[self::raw('Generado · Paraguay'),self::raw($report['generated_at'])],[self::raw('Criterio del informe'),$note],[]];
        foreach($report['filter_labels'] as $key=>$value)$summary[]=[self::raw($key),self::raw($value)];
        $summary[]=[];$summary[]=[self::heading('Indicador'),self::heading('Total de todos los resultados')];
        foreach($report['summary'] as $s)$summary[]=[self::raw($s['label']),self::number($s['value'],$s['format']==='money')];
        $xlsx=new Shuchkin\SimpleXLSXGen();$xlsx->setDefaultFont('Inter')->setDefaultFontSize(11)->setTitle($report['title'])->setAuthor('Vacarel S.R.L.');
        $xlsx->addSheet($summary,'Resumen')->mergeCells('A1:B1')->setColWidth(1,42)->setColWidth(2,95);
        $rows=[array_map(fn($c)=>self::heading($c['label']),$report['columns'])];$numeric=['physical','reserved','available','unfit','minimum','suggested','quantity','opening','closing','days','tax','purchase_tax'];
        foreach($report['rows'] as $r){$row=[];foreach($report['columns'] as $c){$v=$r[$c['key']]??'';$row[]=$c['format']==='money'?self::number($v,true):(in_array($c['key'],$numeric,true)&&$v!==''?self::number($v):self::raw($v));}$rows[]=$row;}
        $xlsx->addSheet($rows,'Detalle')->freezePanes('A2')->autoFilter('A1:'.self::column(count($report['columns'])).count($rows));
        foreach($report['columns'] as $i=>$c)$xlsx->setColWidth($i+1,in_array($c['key'],['product','customer','supplier','reference'],true)?36:24);
        return (string)$xlsx;
    }
    private static function column(int $n):string{$s='';while($n>0){$n--;$s=chr(65+$n%26).$s;$n=intdiv($n,26);}return $s;}
}
