<?php
spl_autoload_register(function($name) {
    
    if ($name == 'Minifier' || $name == 'MatthiasMullie\Minify') {
        require_once(__DIR__ . '/Minifier.php');
    }

});

require_once(__DIR__.'/Cache.php');
require_once(__DIR__.'/cacher.php');