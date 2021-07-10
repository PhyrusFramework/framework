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
        'mysqlbackup' => ['BackupDatabase', 'Backup_Database'],
        'htmlparser' => ['HTMLParser'],
        'mails' => ['Mail', 'PHPMailer\PHPMailer\PHPMailer'],
        'pdf' => ['PDF', 'Dompdf\Dompdf'],
        'googlefonts' => ['GoogleFonts']
    );

    foreach($assoc as $k => $v) {

        if (in_array($name, $v)) {

            require_once(__DIR__ . "/$k/index.php");

            return;
        }

    }

});