<?php

class Ajax {

    /**
     * Ajax defined functions
     *
     * @var array $functions
     */
    private static array $functions = [];

    /**
     * Define a new Ajax function
     *
     * @param string $name Ajax function name
     * @param callable $func Function
     */
    public static function add(string $name, callable $func) {
        Ajax::$functions[$name] = $func;
    }

    /**
     * Check if Ajax function exists.
     *
     * @param string $name Ajax function name
     * 
     * @return bool
     */
    public static function has(string $name) : bool {
        return isset(Ajax::$functions[$name]);
    }

    /**
     * Run ajax function
     *
     * @param string $name Ajax function name
     * 
     * @return bool
     */
    public static function run(string $name) : bool {
        if (!self::has($name)) return false;

        $ret = Ajax::$functions[$name]( new RequestData(true) );
        if (is_array($ret)) {
            echo JSON::stringify($ret);
        }
        return true;

    }

}