<?php

/**
 * Register a file to be imported when an unknown class is used.
 * 
 * @param mixed $classname
 * @param mixed $file
 * @param callable $callback
 * 
 */
function autoload($classnames, $files, callable $callback = null) {

    spl_autoload_register(function($name) use ($classnames, $files, $callback) {

        $classes = is_array($classnames) ? $classnames : [$classnames];

        $found = false;
        foreach($classes as $cl) {
            if ($name == $cl || Text::instance($name)->match($cl)) {
                $found = true;
                break;
            }
        }

        if (!$found) return;

        $filesarr = is_array($files) ? $files : [$files];

        foreach($filesarr as $f) {

            if (file_exists($f)) {
                require_once($f);
            }

        }

        if ($callback != null) {
            $callback();
        }
    
    });

}