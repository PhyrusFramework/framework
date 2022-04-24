<?php

if ((defined('USING_CLI') && !defined('CLI_DATABASE')) )
    return;

require_once(__DIR__.'/Medoo.php');
require_once(__DIR__.'/Database.php');
require_once(__DIR__.'/DBQueryResult.php');
require_once(__DIR__.'/DB.php');
require_once(__DIR__.'/DBTable.php');
require_once(__DIR__.'/BackupDatabase.php');

autoload('DBBuilder', __DIR__.'/DBBuilder.php');

global $DATABASE;
try{
    $DATABASE = new DATABASE(Config::get('database'));
    global $DATABASE_CONNECTED;
    $DATABASE_CONNECTED = true;
} catch(Exception $e) {}

class InsecureString {

    private $text = '';

    function __construct($value) {
        if (is_array($value)) {
            $this->text = JSON::stringify($value);
        } else if (gettype($value) == 'Arr') {
            $this->text = JSON::stringify($value->getArray());
        } else {
            $this->text = "$value";
        }
    }

    /**
     * Returns the string intact.
     * 
     * @return string
     */
    public function getString() : string {
        return $this->text;
    }

}