<?php


class Config {

    /**
     * [Managed by framework] Project configurations
     * 
     * @var Arr $_config
     */
    private static $config;

    /**
     * Get a project configuration.
     * 
     * @param string $key Name of configuration using dot notation.
     * @param mixed $def Default value if not exists.
     * 
     * @return mixed
     */
    public static function get(string $key, $def = null) {

        if (self::$config == null) {
            self::decode();
        }

        $value = self::$config->get($key);
        if ($value == null) return $def;
        return $value;
    }

    /**
     * Gets configuration from JSON file and converts it into an Arr object.
     */
    private static function decode() {
        global $PROJECT_PATH;
        $json = file_get_contents($PROJECT_PATH . '/config.json');
        self::$config = arr(json_decode($json, true));

        if (isset(self::$config['environment'])) {
            $env = self::$config['environment'];

            $file = $PROJECT_PATH . "/config.$env.json";
            if (file_exists($file)) {

                $envjson = file_get_contents($file);
                self::$config->merge( json_decode($envjson, true) );

            }
        }
    }

    /**
     * Set configuration setting on runtime.
     * 
     * @param string $key Configuration name using dot notation.
     * @param mixed $value
     */
    public static function set(string $key, $value) {
        
        if (self::$config == null) {
            self::decode();
        }

        self::$config->set($key, $value);
    }

    /**
     * Change a configuration setting and save the json file.
     * 
     * @param string $key Configuration name using dot notation.
     * @param mixed $value
     */
    public static function save(string $key, $value) {
        self::set($key, $value);

        global $PROJECT_PATH;
        $json = json_encode(self::$config->getArray(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        file_put_contents($PROJECT_PATH . '/config.json', $json);
    }

}