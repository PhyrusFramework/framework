<?php

class Cache {

    /**
     * [Managed by framework] Current active cache file name.
     * 
     * @var string $current_cache_ID
     */
    private static $current_cache_ID;

    /**
     * [Managed by framework] Minifies web assets.
     * 
     */
    public static function minify() {
        if (!Config::get('cache.enabled') || !Config::get('cache.assets')) return;
        if (Cache::assets_cached()) return;
        Minifier::minifyWeb();
    }

    /**
     * Are assets cached?
     * 
     * @return bool
     */
    public static function assets_cached() {

        if (!Config::get('cache.enabled')) return false;
        if (!Config::get('cache.assets_cache')) return false;

        $css = Path::src() . '/cache/assets/web.css';
        $js = Path::src() . '/cache/assets/web.js';

        if (file_exists($css) && file_exists($js))
            return true;
        return false;

    }

    /**
     * Print cached HTML or start recording it.
     * 
     * @param string $ID Name of the cache file.
     * 
     * @return bool True if needs caching, false if it is cached.
     */
    public static function start(string $ID)
    {
        if (!Config::get('cache.enabled')) return true;
        if (!Config::get('cache.dom')) return true;

        $name = "$ID";
        $name = str_replace('/', '', $name);
        $name = str_replace('.', '', $name);

        self::$current_cache_ID = $name;

        $file = Path::src() . '/cache';
        if (!is_dir($file))
        {
            ob_start();
            return true;
        } 

        $file .= "/$name.cch";
        if (!file_exists($file))
        {
            ob_start();
            return true;
        } 

        $content = file_get_contents($file);
        echo $content;
        return false;
    }

     /**
     * Stop recording HTML
     *
     */
    public static function stop()
    {
        if (!Config::get('cache.enabled')) return;
        if (!Config::get('cache.dom')) return;

        $file = Path::src() . '/cache';
        if (!is_dir($file))
            create_folder($file);

        $id = self::$current_cache_ID;
        $file .= "/$id.cch";
        $content = ob_get_contents();

        file_put_contents($file, $content);
        ob_end_flush();
    }

    /**
     * Delete HTML cache file
     *
     * @param string $name
     */
    public static function delete(string $name) {
        $file = Path::src() . "/cache/$name.cch";
        if (file_exists($file))
            unlink($file);
    }

    /**
     * Clean all HTML cache files
     *
     */
    public static function clear() {

        $folder = new Folder(Path::src() . '/cache');
        $folder->empty();

    }

}

Template::addFilter('cache', function($content) {
    return "if (Cache::start($content)) { \n";
});

Template::addFilter('/cache', function($content) {
    return "Cache::stop(); \n }";
});