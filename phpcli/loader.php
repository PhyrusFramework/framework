<?php
error_reporting(E_ERROR | E_PARSE);
define("USING_CLI", true);

require_once(__DIR__ . "/cli.php");
global $CLI;
$CLI = new CLI($argv);

require_once(realpath(__DIR__ . "/../index.php"));
WebLoader::php_in(Path::src() . "/cli");

WebLoader::router(isset($CLI->flags['route']) ? $CLI->flags['route'] : '/');

$CLI->run();