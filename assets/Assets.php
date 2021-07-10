<?php

class Assets {
    
    /**
     * Assets imported until now must be minified?
     *
     * @var bool
     */
    private static bool $minify = true;

    /**
     * CSS files queued to minify
     *
     * @var string[]
     */
    private static array $css_imports = [];

    /**
     * JS files queued to minify
     *
     * @var string[]
     */
    private static array $js_imports = [];

    /**
     * Assets imported from now on will stop being minified.
     */
    public static function stopMinify() {
        self::$minify = false;
    }

    /**
     * Get relative URL to an image in /assets/images
     *
     * @param string $name path to image from /assets/images
     * 
     * @return string
     */
    public static function image(string $name) : string {
        return Path::images(true) . "/$name";
    }

    /**
     * Get relative URL to a CSS file
     *
     * @param string $name path to file from /assets/css
     * 
     * @return string
     */
    public static function css(string $name) : string {
        return Path::css(true) . "/$name";
    }

    /**
     * Get relative URL to a JS file
     *
     * @param string $name path to file from /assets/js
     * 
     * @return string
     */
    public static function js(string $name) : string {
        return Path::js(true) . "/$name";
    }

    /**
     * Get relative URL to a font file
     *
     * @param string $name path to file from /assets/fonts
     * 
     * @return string
     */
    public static function font(string $name) : string {
        return Path::fonts(true) . "/$name";
    }

    /**
     * Get relative URL to any file in assets.
     *
     * @param string $name path to file from /assets
     * 
     * @return string
     */
    public static function asset(string $name) : string {
        return Path::assets(true) . "/$name";
    }

    /**
     * Change the website favicon
     *
     * @param string $name path to the image
     */
    public static function setFavicon(string $img) {
        Head::add('<link rel="shortcut icon" type="image/png" href="' . $img . '"/>');
    }

    /**
     * Add css to the head
     *
     * @param string $src path to css
     */
    public static function include_css(string $src) {
        if (self::$minify) {
            self::$css_imports[] = $src;
        } else {
            Head::add('<link rel="stylesheet" href="' . $src . '">');
        }
    }

    /**
     * Add js to the head or footer
     *
     * @param string $src path to js
     * @param bool $footer (default false)
     */
    public static function include_js(string $src, bool $footer = false) {
        $line = '<script type="text/javascript" src="' . $src .'"></script>';
        if (!$footer) {
            if (self::$minify)
                self::$js_imports[] = $src;
            else
                Head::add($line);
        } else {
            Footer::add($line);
        }
    }

    /**
     * Automatically detect and import all kind of assets (css, js, scss) in directory.
     *
     * @param string $path
     * @param bool $recursive (default true) Check subfolders
     */
    public static function assets_in(string $path, bool $recursive = true) {
        self::css_in("$path/css", $recursive);
        self::scss_in("$path/scss", $recursive);
        self::js_in("$path/js", $recursive);
        self::js_in("$path/js-footer", $recursive, true);
    }

    /**
     * Automatically detect and import css in directory.
     *
     * @param string $path
     * @param bool $recursive (default true) Check subfolders
     */
    public static function css_in(string $path, bool $recursive = true) {
        if (!is_dir($path)) return;
        $dirs = array_filter(glob($path . '/*'), 'is_dir');

        if ($recursive) {
            foreach($dirs as $dir)
                self::css_in($dir);
        }
    
        $files = glob($path . '/*.css');
        foreach($files as $file)
        {
            self::include_css( Path::toRelative($file) );
        }
    }

    /**
     * Automatically detect and import scss in directory.
     *
     * @param string $path
     * @param bool $recursive (default true) Check subfolders
     */
    public static function scss_in(string $path, bool $recursive = true) {
        if (!is_dir($path)) return;
        $dirs = array_filter(glob($path . '/*'), 'is_dir');

        if ($recursive) {
            foreach($dirs as $dir)
                self::scss_in($dir);
        }
    
        $files = glob($path . '/*.scss');
        if (sizeof($files) > 0) {
            SCSS::loadDirectory($path);
        }
    }

    /**
     * Automatically detect and import js in directory.
     *
     * @param string $path
     * @param bool $recursive (default true) Check subfolders
     * @param bool $footer Import scripts in footer
     */
    public static function js_in(string $path, bool $recursive = true, bool $footer = false)
    {
        if (!is_dir($path)) return;
        $dirs = array_filter(glob($path . '/*'), 'is_dir');

        if ($recursive) {
            foreach($dirs as $dir)
                self::js_in($dir, true, $footer);
        }

        $files = glob($path . '/*.js');
        foreach($files as $file)
        {
            $rel = Path::toRelative($file);
            self::include_js($rel, $footer);
        }
    }

    /**
     * [Managed by framework] Load assets queued for minification where they won't be minified.
     */
    public static function loadQueuedAssets() {

        foreach(self::$css_imports as $src) {
            echo '<link rel="stylesheet" href="' . $src . '">';
        }

        foreach(self::$js_imports as $src) {
            echo '<script type="text/javascript" src="' . $src .'"></script>';
        }
    
    }

    /**
     * [Managed by framework] Pass minifier to minify queued assets.
     * 
     * @param Minifier $minifier
     */
    public static function addMinifier(Minifier $minifier) {

        foreach(self::$css_imports as $src) {
            $path = Path::project() . $src;

            if (file_exists($path))
                $minifier->addFile($path);

            // Probably remote resource, so echo
            else {
                echo '<link rel="stylesheet" href="' . $src . '">';
            }
        }

        foreach(self::$js_imports as $src) {
            $path = Path::project() . $src;

            if (file_exists($path))
                $minifier->addFile($path);

            // Probably remote resource, so echo
            else {
                echo '<script type="text/javascript" src="' . $src .'"></script>';
            }
        }
    }

    /**
     * Generate random image URL for development purposes.
     * 
     * @param int $width (Default 400)
     * @param int height (Default null, keep ratio 400:300)
     * 
     * @return string URL
     */
    public static function randomImage(int $width = 400, int $height = null) : string {
        $h = $height != null ? $height : intval($width * (3/4));
        return 'https://picsum.photos/seed/'. Text::random(6) ."/$width/$h";
    }
}