<?php
autoload('Cache', __DIR__ . '/Cache.php');
autoload('Log', __DIR__ . '/Log.php');

require_once(__DIR__.'/DebugConsole.php');
require_once(__DIR__.'/FrameworkException.php');

if (Config::get('project.debug'))
{
    require_once(__DIR__.'/ErrorHandler.php');
    DebugConsole::showErrors();
}