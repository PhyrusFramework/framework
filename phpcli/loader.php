<?php
error_reporting(E_ERROR | E_PARSE);
define("USING_CLI", true);

require_once(__DIR__ . "/cli.php");
global $CLI;
$CLI = new CLI($argv);

$vendor = realpath(__DIR__ . '/../../../../') . '/vendor';
if (file_exists($vendor)) {
    require_once(realpath("$vendor/autoload.php"));
} else {
    require_once(realpath(__DIR__ . "/../index.php"));
}

Router::loadAutoloads();
php_in(Path::root() . "/cli");

$CLI->run();