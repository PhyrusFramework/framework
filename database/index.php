<?php

if (defined('USING_CLI') && !defined('CLI_DATABASE') )
    return;

if (Config::get('database.database') == null) {
    return;
}

require_once(__DIR__.'/Medoo.php');
require_once(__DIR__.'/Database.php');
require_once(__DIR__.'/DBQueryResult.php');
require_once(__DIR__.'/DB.php');
require_once(__DIR__.'/DBQuery.php');
require_once(__DIR__.'/DBTable.php');
require_once(__DIR__.'/BackupDatabase.php');
require_once(__DIR__.'/DBBuilder.php');

autoload('InsecureString', __DIR__.'/InsecureString.php');

global $DATABASE;
try{
    $DATABASE = new DATABASE(Config::get('database'));
    global $DATABASE_CONNECTED;
    $DATABASE_CONNECTED = true;
} catch(Exception $e) {}