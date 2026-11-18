<?php
declare(strict_types=1);

/** Local Lucide SVG sprite. Sources and license: assets/vendor/lucide. */
function icon(string $name, string $class=''): string
{
    if(!preg_match('/^[a-z0-9-]+$/D',$name) || !is_file(__DIR__.'/../assets/vendor/lucide/'.$name.'.svg')) {
        throw new InvalidArgumentException('Icono no disponible.');
    }
    return '<svg class="icon '.htmlspecialchars($class,ENT_QUOTES,'UTF-8').'" width="20" height="20" aria-hidden="true" focusable="false"><use href="assets/icons.svg#'.$name.'"></use></svg>';
}
