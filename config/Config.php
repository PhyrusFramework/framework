<?php

class Config {

    /**
     * [Managed by framework] Project configurations
     * 
     * @var Arr $_config
     */
    private static $config;

    /**
     * Get the path to the cached file.
     * 
     * @return string
     */
    private static function cachedPath() {
        return Path::framework() . '/config.json';
    }

    private static function configPath() {
        return Path::root() . '/' . Definition('config');
    }

    /**
     * Get a project configuration.
     * 
     * @param string $key Name of configuration using dot notation.
     * @param mixed $def Default value if not exists.
     * 
     * @return mixed
     */
    public static function get($key = null, $def = null) {

        if (self::$config == null) {
            self::decode();
        }

        if (empty($key)) {
            return self::$config;
        }

        $value = self::$config->get($key);
        if ($value == null) return $def;
        return $value;
    }

    /**
     * Read YAML files in directory and add their values to the configuration.
     * 
     * @param string $path
     */
    private static function decode_folder(string $folder) {

        if (file_exists($folder) && is_dir($folder)) {
            $files = glob("$folder/*.yaml");
            foreach($files as $file) {
                $content = YAML::fromFile($file);

                $path_parts = pathinfo($file);
                $name = $path_parts['filename'];

                if (!isset(self::$config[$name])) {
                    self::$config[$name] = [];
                }

                foreach($content as $k => $v) {
                    self::$config[$name][$k] = $v;
                }
            }
        }

    }

    /**
     * Gets configuration from JSON file and converts it into an Arr object.
     */
    private static function decode() {

        /// CHECK IF CACHED VERSION EXISTS
        $json = self::cachedPath();
        
        if (file_exists($json)) {
            self::$config = arr(json_decode(file_get_contents($json), true));

            if (!self::get('project.development_mode'))
                return;
        }

        // IF NOT CACHED OR IN DEVELOPMENT MODE --> READ YAMLs
        $folder = self::configPath();

        self::$config = [];
        self::decode_folder($folder);

        if (isset(self::$config['project']) && isset(self::$config['project']['environment'])) {
            $env = self::$config['project']['environment'];
            self::decode_folder("$folder/$env");
        }

        file_put_contents($json, json_encode(self::$config, JSON_UNESCAPED_UNICODE));
        self::$config = arr(self::$config);
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
     * Clear the cached version of the configuration.
     */
    public static function clear() {
        $cached = self::cachedPath();
        if (file_exists($cached)) {
            unlink($cached);
        }
    }

    /**
     * Change a configuration setting and saves the YAML file.
     * 
     * @param string $key Configuration name using dot notation.
     * @param mixed $value
     */
    public static function save(string $key, $value) {
        self::set($key, $value);

        $path = self::configPath();
        $parts = explode('.', $key);

        $yaml = $parts[0];

        $file = "$path/$yaml.yaml";

        $content = new YAML();

        if (sizeof($parts) > 1) {
            $arr = [];
            if (file_exists($file)) {
                $arr = YAML::fromFile($file);
            }

            $current = &$arr;
            if (sizeof($parts) > 1) {
                for($i = 1; $i < sizeof($parts); ++$i) {
                    $k = $parts[$i];
                    if ($i < sizeof($parts) - 1) {
                        $current[$k] = [];
                        $current = &$current[$k];
                    } else {
                        $current[$k] = $value;
                    }
                }
            } else {
                $current = $value;
            }

            $content = $content->dump($arr);
        }
        else {
            $content = $content->dump($value);
        }

        file_put_contents($file, $content);

        self::clear();
    }

    /**
     * Check if YAML configuration file exists.
     * 
     * @return bool
     */
    public static function hasFile($name) : bool {
        global $PROJECT_PATH;
        $path = "$PROJECT_PATH/config/$name.yaml";
        return file_exists($name);
    }

}