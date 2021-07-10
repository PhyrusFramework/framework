<?php
require_once(__DIR__.'/DebugConsole.php');
require_once(__DIR__.'/FrameworkException.php');

if (Config::get('development_mode'))
{
    require_once(__DIR__.'/ErrorHandler.php');
    DebugConsole::showErrors();
}