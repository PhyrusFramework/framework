<?php

class EventListener {

    /**
     * Registered events
     *
     * @var EventListener[] $events
     */
    private static array $events = [];

    /**
     * Functions registered for this event
     *
     * @var array $funcs
     */
    private array $funcs = [];

    /**
     * Name of this event
     *
     * @var string $name
     */
    private $name = '';

    /**
     * Current priority
     * 
     * @var int $priority
     */
    private int $priority = 10;

    function __construct(string $name) {
        $this->name = $name;
        self::$events[$name] = $this;
    }

    /**
     * Add action for this event.
     *
     * @param mixed $func Action when this event is triggered.
     * @param int $priority
     */
    function add($func, $priority = null) {

        $p = $priority;
        if ($p == null) {
            $p = $this->priority;
            $this->priority += 1;
        }

        if (is_callable($func)) {
            $this->funcs[] = [
                'func' => $func,
                'priority' => $p
            ];
        }
        else {
            $this->funcs[] = [
                'func' => function() use($func) {
                    echo $func;
                },
                'priority' => $p
            ];
        }
    }

    /**
     * Run this event.
     *
     * @param $parameters Parameter/s for each event action.
     */
    function run(&$parameters = null) {

        usort($this->funcs, function ($a, $b) {
            $pa = $a['priority'] ?? 10;
            $pb = $b['priority'] ?? 10;

            if (!is_int($pa)) $pa = 10;
            if (!is_int($pb)) $pb = 10;

            return $pa - $pb;
        });

        foreach($this->funcs as $func) {
            $func['func']($parameters);
        }
    }

    /**
     * Get an event listener by name.
     * 
     * @param string $name
     * 
     * @return EventListener
     */
    public static function byName(string $name) : ?EventListener {
        return isset(self::$events[$name]) ? self::$events[$name] : null;
    }

    /**
     * Add action to an event by name
     *
     * @param string $event Name of the event.
     * @param callable $action Action when the event is triggered.
     */
    public static function on(string $event, $action, $priority = null) {

        $ev = self::byName($event);
        if ($ev == null) {
            $ev = new EventListener($event);
        }
        $ev->add($action, $priority);
    }

    /**
     * Trigger an event by name
     *
     * @param string $event Name of the event
     * @param $parameters Parameter/s for the event actions
     */
    public static function trigger(string $event, &$parameters = null) {
        $ev = self::byName($event);
        if ($ev == null) return;
        return $ev->run($parameters);
    }

}

class Head {

    /**
     * Event object
     *
     * @var EventListener
     */
    protected static $event;

    /**
     * Add lines to the website head.
     *
     * @param callable $func
     * @param int $priority
     */
    public static function add($func, $priority = null) {

        if (self::$event == null) {
            self::$event = new EventListener('head');
        }

        self::$event->add($func, $priority);

    }

    /**
     * Print Head content
     */
    public static function print() {
        if (self::$event != null)
        self::$event->run();
    }

}

class Footer {

    /**
     * Event object
     *
     * @var EventListener
     */
    protected static $event;

    /**
     * Add content to the website footer.
     *
     * @param callable $func
     * @param int $priority
     *
     */
    public static function add($func, $priority = null) {

        if (self::$event == null) {
            self::$event = new EventListener('footer');
        }

        self::$event->add($func, $priority);

    }

    /**
     * Print Footer content
     */
    public static function print() {
        if (self::$event != null)
        self::$event->run();
    }

}