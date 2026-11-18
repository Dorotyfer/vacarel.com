<?php
$e=static fn(mixed $s):string=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
$assetRoot='file://'.str_replace('\\','/',dirname(__DIR__)).'/assets/';
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=$e($report['title'])?> · Vacarel</title>
<?php if($pdf):?><style><?=str_replace('url("fonts/','url("'.$assetRoot.'fonts/',file_get_contents(__DIR__.'/../assets/informes-imprimir.css'))?></style><?php else:?><link rel="stylesheet" href="assets/informes-imprimir.css"><script defer src="assets/informes-imprimir.js"></script><?php endif;?>
</head><body class="report-document<?=$pdf?' pdf-document':''?>">
<?php if(!$pdf):?><div class="print-tools"><a href="./#reports">Volver a informes</a><button type="button" id="print-report">Imprimir informe</button><span>Todos los resultados filtrados · A4 horizontal</span></div><?php endif;?>
<table class="report-header"><tr><td class="logo-cell"><img src="<?=$pdf?$assetRoot.'vacarel-logo-informes.jpg':'assets/vacarel-logo-informes.jpg'?>" alt="Vacarel S.R.L."></td><td><span class="eyebrow">INFORMES DE GESTIÓN</span><h1><?=$e($report['title'])?></h1><p>Generado: <?=$e($report['generated_at'])?> · Hora de Paraguay</p></td></tr></table>
<p class="report-note"><?=$e($report['note'])?></p>
<?php if($report['filter_labels']):?><p class="filters"><strong>Filtros aplicados:</strong> <?php foreach($report['filter_labels'] as $key=>$value):?><?=$e($key)?>: <?=$e($value)?> · <?php endforeach;?></p><?php endif;?>
<table class="summary"><tr><?php foreach($report['summary'] as $i=>$s):?><?php if($i>0&&$i%4===0):?></tr><tr><?php endif;?><td><span><?=$e($s['label'])?></span><strong><?=$e(ReportOutput::display($s['value'],$s['format']))?></strong></td><?php endforeach;?></tr></table>
<table class="report-data"><thead><tr><?php foreach($report['columns'] as $c):?><th scope="col"><?=$e($c['label'])?></th><?php endforeach;?></tr></thead><tbody><?php foreach($report['rows'] as $r):?><tr><?php foreach($report['columns'] as $c):?><td class="<?=$c['format']==='money'?'amount':''?>"><?=$e(ReportOutput::display($r[$c['key']]??null,$c['format']))?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table>
<?php if(!$report['rows']):?><p class="empty">No hay resultados con estos filtros.</p><?php endif;?>
<footer>Vacarel · Informe de gestión · <?=$e($report['pagination']['total'])?> registros · Importes en guaraníes (Gs.)</footer></body></html>
