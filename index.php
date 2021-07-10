<?php

global $PROJECT_PATH;

$_noComposer = realpath(__DIR__ . '/../') . '/config.json';
if (file_exists($_noComposer)) {
    $PROJECT_PATH = str_replace('\\', '/', realpath(__DIR__ . '/../'));
} else {
    $PROJECT_PATH = str_replace('\\', '/', realpath(__DIR__ . '/../../../'));
}

if (!file_exists($PROJECT_PATH . '/config.json')) {
    echo 'Error: Phyrus project not found at ' . $PROJECT_PATH;
    die();
}

global $FRAMEWORK_PATH;
$FRAMEWORK_PATH = __DIR__;

///////////

spl_autoload_register(function($name) {

    if ($name == 'Ajax') {
        require_once(__DIR__ . '/ajax/index.php');
    }
    else if ($name == 'DB' || $name == 'DATABASE' || $name == 'InsecureString') {
        require_once(__DIR__ . '/database/index.php');
    }
});

$components = [
    'config',
    'utilities',
    'debug',
    'ajax',
    'template',
    'router',
    'assets',
    'cache',
    'http',
    'modules'
];
foreach($components as $c) {
    require_once(__DIR__."/$c/index.php");
}

if (class_exists('CLI_Performance')) {
    CLI_Performance::record('Framework loaded');
}