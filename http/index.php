<?php
require_once(__DIR__.'/server.php');

spl_autoload_register(function($name) {
    if ($name == 'http') {
        require_once(__DIR__ . '/client.php');
        return;
    }

    if ($name == 'ApiResponse') {
        require_once(__DIR__ . '/ApiResponse.php');
        return;
    }

    if ($name == 'CRUD') {
        require_once(__DIR__ . '/CRUD.php');
        return;
    }
});