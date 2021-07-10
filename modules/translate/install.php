<?php
Config::save('translate', array(
    'use_cookies' => true,
    'default_language' => 'en',
    'supported_languages' => ['en'],
    'inherit' => [],
    'folder' => '/translations'
));

$path = Path::project() . '/translations';
if (!is_dir($path))
    mkdir($path);
$trans = '{}';
$file = $path . '/en.json';
if (!file_exists($file)) {
    file_put_contents($file, $trans);
}