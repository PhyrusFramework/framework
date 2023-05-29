<?php
require_once(__DIR__.'/server.php');
require_once(__DIR__.'/Controller.php');

spl_autoload_register(function($name) {
    if ($name == 'HTTP') {
        require_once(__DIR__ . '/HTTP.php');
        return;
    }

    if ($name == 'ApiResponse') {
        require_once(__DIR__ . '/ApiResponse.php');
        return;
    }

    if ($name == 'Uploader') {
        require_once(__DIR__ . '/Uploader.php');
    }
});