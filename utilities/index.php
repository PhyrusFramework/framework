<?php
require_once(__DIR__.'/Path.php');
require_once(__DIR__.'/Generic.php');
require_once(__DIR__.'/methods.php');
require_once(__DIR__.'/Time.php');
require_once(__DIR__.'/Text.php');
require_once(__DIR__.'/File.php');
require_once(__DIR__.'/Folder.php');
require_once(__DIR__.'/URL.php');
require_once(__DIR__.'/Javascript.php');

// autoload
spl_autoload_register(function($name) {

    if ($name == 'Validator') {
        require_once(__DIR__ . '/Validator.php');
        return;
    }

    if ($name == 'SESSION') $name = 'Cookie';

    $file = __DIR__ . "/$name.php";

    if (file_exists($file)) {
        require_once($file);
    }

});