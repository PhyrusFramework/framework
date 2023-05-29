<?php
require_once(__DIR__ . '/WatcherAction.php');

class Watcher {

    private array $actions = [];
    private ?WatcherAction $currentAction = null;

    public function __construct(Closure $constructor)
    {
        $constructor($this);
        $this->run();
    }

    public function every(int $value) : Watcher {
        if (!$this->currentAction) {
            $this->currentAction = new WatcherAction();
        }

        $this->currentAction->every($value);
        return $this;
    }

    public function on(int $value) : Watcher {
        if (!$this->currentAction) {
            $this->currentAction = new WatcherAction();
        }

        $this->currentAction->on($value);
        return $this;
    }

    public function at(?int $hour, ?int $minute, ?int $second) {
        if ($hour) $this->on($hour)->hour();
        if ($minute) $this->on($minute)->minute();
        if ($second) $this->on($second)->second();
    }

    private function setMeasure(string $measure) : Watcher {
        if (!$this->currentAction) return $this;
        $this->currentAction->setMeasure($measure);
        return $this;
    }

    public function second() : Watcher{
        return $this->setMeasure('second');
    }
    public function seconds() : Watcher {
        return $this->setMeasure('second');
    }

    public function minute() : Watcher {
        return $this->setMeasure('minute');
    }
    public function minutes() : Watcher {
        return $this->setMeasure('minute');
    }

    public function hour() : Watcher {
        return $this->setMeasure('hour');
    }
    public function hours() : Watcher {
        return $this->setMeasure('hour');
    }

    public function day() : Watcher {
        return $this->setMeasure('day');
    }
    public function days() : Watcher {
        return $this->setMeasure('day');
    }

    private function closeAction() {
        $this->actions[] = $this->currentAction;
        $this->currentAction = null;
    }

    public function do(Closure $action) {
        if (!$this->currentAction) return;
        $this->currentAction->setAction($action);
        $this->closeAction();
    }

    private function run() {

        // Sleep for a minute
        for($i = 0; $i < 59; ++$i) {
            $this->doRun();
            sleep(1);

            if (Cache::get('watcher.lock', 'status:started') == 'status:stopped') {
                break;
            }
        }

    }

    private function doRun() {
        // Date parts in number
        $now = date('Y-m-d-H-i-s');
        $dateParts = explode('-', $now);
        $nums = [
            'year' => intval($dateParts[0]),
            'month' => intval($dateParts[1]),
            'day' => intval($dateParts[2]),
            'hour' => intval($dateParts[3]),
            'minute' => intval($dateParts[4]),
            'second' => intval($dateParts[5])
        ];

        foreach($this->actions as $action) {
            if ($action->check($nums)) {
                $action->run();
            }
        }

    }

}