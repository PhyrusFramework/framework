<?php

define('PROJECT_PATH', str_replace('\\', '/', ROOT));

if (!file_exists(PROJECT_PATH . '/config')) {
    echo 'Error: Phyrus project not found at ' . PROJECT_PATH;
    die();
}

define('FRAMEWORK_PATH', str_replace('\\', '/', __DIR__));

///////////

require_once(__DIR__ . '/config/index.php');
autoload(['DB', 'DB*', 'DATABASE', 'Backup_Database', 'InsecureString'], [__DIR__ . '/database/index.php']);

function DBConnected() {
    return defined('DATABASE_CONNECTED');
}

$components = [
    'utilities',
    'debug',
    'router',
    'http',
    'modules'
];
foreach($components as $c) {
    require_once(__DIR__."/$c/index.php");
}

if (defined('WATCHER')) {
    require_once(__DIR__ . '/watcher/Watcher.php');
    Router::instance()->autoloadProjectPHPFiles();
}