<?php

if (class_exists('Test')) return;

ob_start();
if (!defined('USING_CLI'))
    require_once(realpath(__DIR__ . '/../index.php'));
require_once(__DIR__ . '/index.php');
ob_end_clean();

define('RUNNING_TEST', true);

echo "\nRunning tests...\n";

// Load tests
php_in(Path::root() . '/tests');

if (Test::$TEST_COUNT == 0) {
    echo "\nNo tests to run.\n";
}