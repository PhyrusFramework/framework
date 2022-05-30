<?php
error_reporting(E_ERROR | E_PARSE);
define("USING_CLI", true);

require_once(__DIR__ . "/cli.php");
global $CLI;
$CLI = new CLI($argv);

require_once(realpath(__DIR__ . "/../index.php"));
php_in(Path::src() . "/cli");

$CLI->run();