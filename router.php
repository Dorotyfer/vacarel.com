<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(preg_match('~^/(app|bin|database|tests|docs)(/|$)~',$path) || str_contains($path,'..') || !preg_match('~^/(?:|index\.php|api\.php|informes-exportar\.php|informes-imprimir\.php|preimpresa\.php|preimpresa-estilo\.php|assets/[a-zA-Z0-9._-]+|assets/fonts/Inter-(?:Regular|SemiBold|Bold)\.(?:woff|ttf))$~',$path)) {http_response_code(404);exit;}
if(is_file(__DIR__.$path)) return false;
require __DIR__.'/index.php';
