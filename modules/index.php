<?php

spl_autoload_register(function($name) {

    $assoc = array(
        'translate' => ['Translate'],
        'orm' => ['ORM', 'AdvancedORM', 'RelationORM'],
        'mobile_detect' => ['MobileDetect', 'Mobile_Detect'],
        'jwt' => ['JWT']
    );

    foreach($assoc as $k => $v) {

        if (in_array($name, $v)) {

            require_once(__DIR__ . "/$k/index.php");

            return;
        }

    }

});

if (Config::get('translations.javascript', false)) {
    Translate::use('user')->addJavascript();
}