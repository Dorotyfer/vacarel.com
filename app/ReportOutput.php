<?php
declare(strict_types=1);

/** Shared output for the full authorised result, independent of screen pagination. */
final class ReportOutput
{
    public static function csv(array $report,$stream): void
    {
        fwrite($stream,"\xEF\xBB\xBF");
        $write=static function(array $cells)use($stream):void{fputcsv($stream,array_map(fn($v)=>Reports::csvCell((string)($v??'')),$cells),';','"','');};
        $write([$report['title']]);$write(['Generado',$report['generated_at']]);$write([$report['note']]);
        foreach($report['filter_labels'] as $key=>$value)$write([$key,$value]);
        foreach($report['summary'] as $s)$write([$s['label'],$s['value']]);
        $write([]);$write(array_column($report['columns'],'label'));
        foreach($report['rows'] as $row)$write(array_map(fn($c)=>$row[$c['key']]??'',$report['columns']));
    }
    public static function display(mixed $value,string $format): string
    {
        $s=(string)($value??'—');if($format!=='money')return $s;
        // Group the decimal string without conversion to floating point.
        $parts=explode('.',$s,2);$parts[0]=preg_replace('/\B(?=(\d{3})+(?!\d))/','.',$parts[0]);
        return 'Gs. '.$parts[0].(isset($parts[1])&&trim($parts[1],'0')!==''?','.rtrim($parts[1],'0'):'');
    }
}
