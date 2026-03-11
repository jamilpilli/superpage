<?php
// Utilitários gerais

function generate_slug($string) {
    // Fallback manual para servidores sem a extensão INTL
    $string = mb_strtolower($string, 'UTF-8');
    $map = [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i',
        'ó'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'
    ];
    $string = strtr($string, $map);
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return rtrim($string, '-');
}

function get_upload_url($filepath) {
    require_once __DIR__ . '/Storage.php';
    return Storage::disk()->getUrl($filepath);
}

function format_currency($amount) {
    return 'R$ ' . number_format($amount, 2, ',', '.');
}

function redirect($url) {
    // Tratamento para ambiente de desenvolvimento XAMPP
    $baseDir = '/superpage';
    if (str_starts_with($url, '/') && strpos($_SERVER['REQUEST_URI'], $baseDir) === 0) {
        if (strpos($url, $baseDir) !== 0) {
            $url = rtrim($baseDir, '/') . $url;
        }
    }
    header("Location: $url");
    exit;
}

function d($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
}

function dd($data) {
    d($data);
    exit;
}
