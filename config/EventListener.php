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

    function __construct(string $name) {
        $this->name = $name;
        self::$events[$name] = $this;
    }

    /**
     * Add action for this event.
     *
     * @param mixed $func Action when this event is triggered.
     */
    function add($func) {
        if (is_callable($func)) {
            $this->funcs[] = $func;
        }
        else {
            $this->funcs[] = function() use($func) {
                echo $func;
            };
        }
    }

    /**
     * Run this event.
     *
     * @param $parameters Parameter/s for each event action.
     */
    function run(&$parameters = null) {
        foreach($this->funcs as $func) {
            $func($parameters);
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
    public static function on(string $event, $action) {

        $ev = self::byName($event);
        if ($ev == null) {
            $ev = new EventListener($event);
        }
        $ev->add($action);
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
     * @param array ...$lines
     */
    public static function add(...$lines) {

        if (self::$event == null) {
            self::$event = new EventListener('head');
        }

        foreach($lines as $line) {
            self::$event->add($line);
        }

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
     * Add lines to the website footer.
     *
     * @param array ...$lines
     *
     */
    public static function add(...$lines) {

        if (self::$event == null) {
            self::$event = new EventListener('footer');
        }

        foreach($lines as $line) {
            self::$event->add($line);
        }

    }

    /**
     * Print Footer content
     */
    public static function print() {
        if (self::$event != null)
        self::$event->run();
    }

}