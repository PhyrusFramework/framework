<?php

class Path {

    /**
     * Convert path to relative
     */
    public static function toRelative($path) {
        return str_replace(self::root(), '', str_replace('\\', '/', $path));
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
     * Get path to /modules
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function modules(bool $relative = false) : string {
        return self::project($relative) . '/' . Definition('modules');
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
     * Get path to /src/assets
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function assets(bool $relative = false) : string {
        return self::src($relative) . '/assets';
    }

    /**
     * Get path to /src/assets/js
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function js(bool $relative = false) : string {
        return self::assets($relative) . '/js';
    }

    /**
     * Get path to /src/assets/css
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function css(bool $relative = false) : string {
        return self::assets($relative) . '/css';
    }

    /**
     * Get path to /src/assets/images
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function images(bool $relative = false) : string {
        return self::assets($relative) . '/images';
    }

    /**
     * Get path to /assets/fonts
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function fonts(bool $relative = false) : string {
        return self::assets($relative) . '/fonts';
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
     * Get path to /src/components
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function components(bool $relative = false) : string {
        return self::src($relative) . '/' . Definition('components');
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
     * Get path to /src/pages
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function pages(bool $relative = false) : string {
        return self::src($relative) . '/' . Definition('pages');
    }

    /**
     * Get path to the current controller.
     * 
     * @param bool $relative [Default false]
     * 
     * @return string
     */
    public static function page(bool $relative = false) : string {
        $folder = Controller::current()->directory();
        return $relative ? self::toRelative($folder) : $folder;
    }

}