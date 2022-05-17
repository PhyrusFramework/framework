<?php

function php_in($directory) {

    $___files = subfiles($directory, 'php');
    foreach($___files as $_file) {
        include($_file);
    }

    $dirs = subfolders($directory);
    foreach($dirs as $dir) {
        php_in($dir);
    }

}

require_once(__DIR__."/Router.php");