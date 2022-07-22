<?php

function php_in($directory, $autoloadByClass = null) {

    if (!file_exists($directory)) {
        return;
    }

    if (!is_dir($directory)) {
        if (!$autoloadByClass)
            require_once($directory);
        else {
            $cl = basename($directory);
            $cl = str_replace('.php', '', $cl);

            autoload($cl, $directory);
        }

        return;
    }

    $___files = subfiles($directory, 'php');
    foreach($___files as $_file) {
        php_in($_file);
    }

    $dirs = subfolders($directory);
    foreach($dirs as $dir) {
        php_in($dir);
    }

}

require_once(__DIR__."/Router.php");