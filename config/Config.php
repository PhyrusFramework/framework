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
            $files = glob($folder . "/*.yaml");
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

        global $PROJECT_PATH;

        /// CHECK IF CACHED VERSION EXISTS
        $json = "$PROJECT_PATH/config/generated.json";
        
        if (file_exists($json)) {
            self::$config = arr(json_decode(file_get_contents($json), true));

            // IF PRODUCTION, USE CACHED MODE
            if (!self::get('project.development_mode')) {
                return;
            }
        }

        // IF NOT CACHED OR IN DEVELOPMENT MODE --> READ YAMLs
        $folder = "$PROJECT_PATH/config";

        self::$config = [];
        self::decode_folder($folder);

        if (isset(self::$config['project']) && isset(self::$config['project']['environment'])) {
            $env = self::$config['project']['environment'];
            self::decode_folder("$PROJECT_PATH/$env");
        }

        file_put_contents("$PROJECT_PATH/config/generated.json", json_encode(self::$config, JSON_UNESCAPED_UNICODE));
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
     * Change a configuration setting and saves the YAML file.
     * 
     * @param string $key Configuration name using dot notation.
     * @param mixed $value
     */
    public static function save(string $key, $value) {
        self::set($key, $value);

        global $PROJECT_PATH;
        $path = "$PROJECT_PATH/config";

        $parts = explode('.', $key);

        $yaml = $parts[0];
        $route = substr($key, strlen($yaml) + 1);

        $file = "$path/$yaml.yaml";

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

        $content = new YAML();
        $content = $content->dump($arr);
        file_put_contents($file, $content);

        $cached = "$PROJECT_PATH/config/generated.json";
        if (file_exists($cached)) {
            unlink($cached);
        }
    }

}