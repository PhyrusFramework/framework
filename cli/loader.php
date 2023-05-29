<?php

// Display errors in terminal
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ERROR | E_PARSE);

// Note down that is CLI running
define("USING_CLI", true);

// Load the framework
if (!class_exists('Router')) {
    $vendor = ROOT . '/vendor';
    if (file_exists($vendor)) {
        require_once(realpath("$vendor/autoload.php"));
    } else {
        require_once(realpath(__DIR__ . "/../index.php"));
    }  
    
    // Load project PHP files
    Router::instance()->autoloadProjectPHPFiles();
}

// Load the CLI classes
require_once(__DIR__ . "/CLI.php");

// Load project commands (if there are)
$_commands_path = ROOT . '/' . Definition('commands');
if (file_exists($_commands_path)) {
    php_in($_commands_path);
}