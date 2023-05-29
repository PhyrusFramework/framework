<?php

class WatcherCommand extends Command {

    protected $command = 'watcher';

    public function command_create() {
        $this->createWatcher();
    }

    public function command_start() {
        $this->createWatcher();

        ob_start();
        $cron = Cron::instance()
            ->do('/usr/bin/php ' . Path::root() . '/' . Definition('watcher') . '.php');

        $cron->create();

        Cache::save('watcher.lock', 'status:started');
        ob_clean();

        echo "\n\nWatcher started\n";
            
    }

    public function command_stop() {

        $cron = Cron::select('* * * * * /usr/bin/php ' . Path::root() . '/' . Definition('watcher') . '.php');
        if ($cron) {
            $cron->delete();
            echo "\nWatcher stopped.\n";
        }
        else {
            echo "\nWatcher wasn't running.\n";
        }
        Cache::save('watcher.lock', 'status:stopped');
    }

    private function createWatcher() {
        $file = Path::root() . '/' . Definition('watcher') . '.php';

        if (file_exists($file)) {
            return;
        }

        ob_start();
?><<?= '?' ?>php
//////////////////////////////// Load Framework
define('ROOT', __DIR__);
define('WATCHER', true);
require(__DIR__.'/vendor/autoload.php');
///////////////////////////////////////////////

new Watcher(function($watcher) {

    // On 14th every 2 months, that day, every 5 minutes
    $watcher
        ->every(2)->months()
        ->on(14)->day()
        ->every(5)->minutes()

        // Action
        ->do(function() {
            Log::info('funciona');
        });

});
<?php
        $content = ob_get_clean();
        file_put_contents($file, $content);
    }

}