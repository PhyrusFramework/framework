<?php

class Path {

    /**
     * Convert path to relative
     */
    public static function toRelative($path) {
        $path = str_replace(self::root(), '', str_replace('\\', '/', $path));
        return $path;
    }

    /**
     * Convert path to absolute.
     */
    public static function toAbsolute(string $src) : string {
        $root = self::root();
        return strpos($src, $root) === FALSE ? "$root$src" : $src;
    }

    public static function of($file, $relative = false) {
        $folder = str_replace('\\', '/', dirname($file));
        return $relative ? str_replace(self::root(), '', $folder) : $folder;
    }

    /**
     * Get root path.
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function root(bool $relative = false) : string {
        if ($relative) return '';
        global $PROJECT_PATH;
        return $PROJECT_PATH;
    }

    /**
     * Get root path.
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function project(bool $relative = false) : string {
        return self::root($relative);
    }

    /**
     * Get path to /src
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function src(bool $relative = false) : string {
        $w = Definition('src');
        return self::project($relative) . "/$w";
    }

    /**
     * Get path to /public
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function public(bool $relative = false) : string {
        $w = Definition('public');
        return self::project($relative) . "/$w";
    }


    /**
     * Get path to the framework
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function framework(bool $relative = false) : string {
        global $FRAMEWORK_PATH;
        return $relative ? self::toRelative($FRAMEWORK_PATH) : $FRAMEWORK_PATH;
    }

    /**
     * Get path to /tests
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function tests(bool $relative = false) : string {
        return self::project($relative) . '/' . Definition('tests');
    }

    /**
     * Get path to /src/code
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function code(bool $relative = false) : string {
        return self::src($relative) . '/' . Definition('code');
    }

    /**
     * Get path to /src/middlewares
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function middlewares(bool $relative = false) : string {
        return self::src($relative) . '/' . Definition('middlewares');
    }

    /**
     * Get path to /src/routes
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function routes(bool $relative = false) : string {
        return self::src($relative) . '/' . Definition('routes');
    }

}