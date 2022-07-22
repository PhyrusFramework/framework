<?php

if (class_exists('Test')) return;

ob_start();
if (!defined('USING_CLI') && !class_exists('WebLoader'))
    require_once(realpath(__DIR__ . '/../index.php'));
require_once(__DIR__ . '/index.php');
ob_clean();

define('RUNNING_TEST', true);

// Load tests
if ($useFrameworkTests) {
    WebLoader::php_in(Path::framework() . '/tests/framework');
} else {
    WebLoader::php_in(Path::root() . '/tests');
}

if (Test::$TEST_COUNT == 0) {
    echo "\nNo tests to run.\n";
}