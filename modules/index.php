<?php

Template::addFilter('t', function($content) {
    echo "Translate::use('user')->get($content);";
});

spl_autoload_register(function($name) {

    $assoc = array(
        'scss' => ['SCSS', 'scssc'],
        'translate' => ['Translate'],
        'orm' => ['ORM', 'AdvancedORM', 'RelationORM'],
        'mobile_detect' => ['MobileDetect', 'Mobile_Detect'],
        'googlefonts' => ['GoogleFonts'],
        'jwt' => ['JWT']
    );

    foreach($assoc as $k => $v) {

        if (in_array($name, $v)) {

            require_once(__DIR__ . "/$k/index.php");

            return;
        }

    }

});