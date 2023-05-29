<?php

/**
 * Get or set a framework definition.
 * 
 * @param string $key Name of definition.
 * @param mixed $value [Optional] Value if setting.
 * 
 */
function Definition(string $key, $value = null) {
    
    if ($value === null) 
        return Definitions::get($key);
    Definitions::set($key, $value);

}

class Definitions {

    /**
     * [Managed by framework] List of definitions.
     * 
     * @var array $_definitions
     */
    private static array $definitions = [];

    /**
     * Get a definition
     * 
     * @param string $key Name of definition.
     * @param string $default (Default = '')
     * 
     * @return string
     */
    public static function get(string $name, string $default = '') : string {

        if (isset(self::$definitions[$name]))
            return self::$definitions[$name];

        return $default;
    }

    /**
     * Set a definition
     * 
     * @param string $name Name of definition
     * @param string $value
     */
    public static function set(string $name, string $value) {
        self::$definitions[$name] = $value;
    }

    /**
     * [Managed by framework] Loads definitions from the framework JSON file.
     */
    public static function init() {
        $def = FRAMEWORK_PATH . '/definitions.json';
        if (file_exists($def)) {
            self::$definitions = json_decode(file_get_contents($def), true);
        }
    }

}
Definitions::init();