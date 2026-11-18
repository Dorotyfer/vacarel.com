<?php
require __DIR__.'/app/bootstrap.php';require __DIR__.'/app/PrintDocument.php';
header('Content-Type: text/css; charset=utf-8');
try {
    $db=database();$user=currentUser($db);if(!$user){http_response_code(401);exit;}
    $doc=printDocument(new Billing($db,$user),($_GET['test']??'')==='1',(int)($_GET['id']??0));$s=$doc['snapshot']['template'];
    $width=(float)$s['width'];$height=(float)$s['height'];$font=(float)$s['font_size'];
    echo "@page{size:{$width}mm {$height}mm;margin:0}.paper{width:{$width}mm;height:{$height}mm}.field{font-size:{$font}pt}";
    foreach($s['fields'] as $key=>[$x,$y,$w]) {
        $left=$x*$width/100+$s['offset_x'];$top=$y*$height/100+$s['offset_y'];$size=$w*$width/100;
        echo '.field-'.$key.'{left:'.$left.'mm;top:'.$top.'mm;width:'.$size.'mm;}';
        if(in_array($key,['code','quantity','description','unit_price','exempt','vat5_gross','vat10_gross'],true))for($i=0;$i<$s['max_lines'];$i++)echo '.field-'.$key.'.slot-'.$i.'{top:'.($top+$i*$s['line_step']*$height/100).'mm;}';
    }
}catch(Throwable $e){error_log($e->getMessage());http_response_code(403);}
