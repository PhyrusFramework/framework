<?php

class Cache {

    private static string $_current_file = '';
    CONST EXTENSION = '.cch';

    private static function dir(string $file = '') : string {
        return Path::root() . '/' . Definition('cache') . ($file == '' ? '' : "/$file" . self::EXTENSION);
    }

    /**
     * Check if Cache file exists.
     * 
     * @param string Cache file name
     * 
     * @return bool
     */
    public static function has(string $name) : bool {
        return file_exists(self::dir($name));
    }

    /**
     * Get last modification date of cache file, if exists.
     * 
     * @param string Cache file name
     * 
     * @return string
     */
    public static function lastWrite(string $name) : string {
        $file = self::dir($name);

        if (file_exists($file)) {
            return last_modification_date($file);
        }

        return '';
    }

    /**
     * Get contents of cache file, if exists.
     * 
     * @param string Cache file name
     * @param string Default value if does not exist
     * 
     * @return string
     */
    public static function get(string $name, string $default = '') : string {
        $file = self::dir($name);

        if (file_exists($file)) {
            return file_get_contents($file);
        }

        return $default;
    }

    /**
     * Save cache content.
     * 
     * @param string Cache file name
     * @param string File content
     * 
     * @return string
     */
    public static function save(string $name, string $content) : string {
        $file = self::dir($name);

        $dir = dirname($file);
        create_folder($dir);

        file_put_contents($file, $content);
        return $file;
    }
 
    /**
     * Start recording the output buffer to save into a file later.
     * 
     * @param string Cache file name
     * @param bool If true, will always return true
     * 
     * @return bool
     */
    public static function start(string $name, bool $forceRefresh = false) : bool {
        $file = file_exists(self::dir($name));

        if (file_exists($file) && !$forceRefresh) {
            $content = file_get_contents($file);
            echo $content;
            return false;
        }

        self::$_current_file = $name;
        ob_flush();
        ob_start();
        return true;
    }

    /**
     * Save all the output since start(), and save it into a cache file.
     * 
     * @param bool Flush recorded content?
     * 
     * @return string
     */
    public static function end(bool $flush = true) : string {
        if (empty(self::$_current_file)) return '';

        $content = $flush ? ob_get_flush() : ob_get_clean();
        if (!empty($content)) {
            self::save(self::$_current_file, $content);
        }

        return $content;
    }

}