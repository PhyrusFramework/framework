<?php
require_once(__DIR__ . '/minify/Minify.php');
require_once(__DIR__ . '/minify/CSS.php');
require_once(__DIR__ . '/minify/JS.php');
require_once(__DIR__ . '/minify/Exception.php');
require_once(__DIR__ . '/minify/Exceptions/BasicException.php');
require_once(__DIR__ . '/minify/Exceptions/FileImportException.php');
require_once(__DIR__ . '/minify/Exceptions/IOException.php');
require_once(__DIR__ . '/path-converter/ConverterInterface.php');
require_once(__DIR__ . '/path-converter/Converter.php');

use MatthiasMullie\Minify;

class Minifier {

    /**
     * The minifier that handles Javascript
     * 
     * @var Minify\JS $jsminifier
     */
    private Minify\JS $jsminifier;

        /**
     * The minifier that handles CSS
     * 
     * @var Minify\CSS $jsminifier
     */
    private Minify\CSS $cssminifier;

    /**
     * Folders queued to be minified
     * 
     * @var string[] $folders
     */
    private array $folders = [];

    /**
     * Files queued to be minified
     * 
     * @var string[] $files
     */
    private array $files = [];

    public function __construct() {
        $this->jsminifier = new Minify\JS('');
        $this->cssminifier = new Minify\CSS('');
    }

    /**
     * Add folder to be minified
     * 
     * @param string $path
     * @param bool $type (Default *) css or js to search only one kind of asset
     * @param bool $recursive (Default true) Minify subfolders
     */
    public function addFolder(string $path, string $type = '*', bool $recursive = true) {
        $this->folders[$path] = [
            'type' => '*',
            'recursive' => $recursive
        ];
    }

    /**
     * Add single file to the minifier
     * 
     * @param string $path
     */
    public function addFile(string $path) {
        $this->files[] = $path;
    }

    /**
     * Minify all enqueued files into a generated file.
     * 
     * @param string $path Directory of the new file
     * @param string $name Name of the minified file without extension.
     */
    public function minify(string $path, string $name) {

        // CSS
        $cssused = false;

        foreach($this->folders as $key => $folder) {

            if (!is_dir($key)) continue;

            if ($folder['type'] == 'js') continue;

            $cssused = $cssused || $this->searchFolder($key, 'css', $folder['recursive']);

        }

        // JS
        $jsused = false;

        foreach($this->folders as $key => $folder) {

            if (!is_dir($key)) continue;

            if ($folder['type'] == 'css') continue;

            $jsused = $jsused || $this->searchFolder($key, 'js', $folder['recursive']);

        }

        // Files

        foreach($this->files as $file) {

            if (!file_exists($file)) {
                $file = Path::project() . $file;
                if (!file_exists($file)) continue;
            }
            $type = file_extension($file);

            if ($type == 'css') {
                $this->cssminifier->add($file);
                $cssused = true;
            } else if ($type == 'js') {
                $this->jsminifier->add($file);
                $jsused = true;
            }

        }

        // generate files

        if ($cssused)
        $this->cssminifier->minify("$path/$name.css");

        if ($jsused)
        $this->jsminifier->minify("$path/$name.js");
    }


    /**
     * Enqueue assets for minification.
     * 
     * @param string $path Directory where assets are searched.
     * @param string $name Name of the minified file without extension.
     * @param string $recursive Search in subfolders.
     * 
     * @return bool Something was enqueued.
     */
    private function searchFolder(string $path, string $type, bool $recursive = true) : bool {

        if (!is_dir($path)) return false;

        $used = false;
        $folder = Folder::instance($path);

        $files = $folder->subfiles($path, $type);
        foreach($files as $file)
        {
            $used = true;
            $this->{$type . 'minifier'}->add($file);
        }

        if (!$recursive) return $used;

        $dirs = $folder->subfolders($path);
        foreach($dirs as $dir)
        {
            $used = $used || $this->searchFolder($dir, $type, true);
        }

        return $used;

    }


    //// STATIC

    /**
     * The oficial framework minifier
     * 
     * @var Minifier $webMinifier
     */
    private static $webMinifier;

    /**
     * Get instance of the web minifier
     * 
     * @return Minifier
     */
    public static function instance() : Minifier {
        if (self::$webMinifier == null)
            self::$webMinifier = new Minifier();

        return self::$webMinifier;
    }

    /**
     * [Managed by framework] Minify enqueued assets
     */
    public static function minifyWeb() {

        $savepath = Path::src() . '/cache';
        create_folder($savepath);
        $savepath .= '/assets';
        create_folder($savepath);
    
        $minifier = Minifier::instance();

        $minifier->addFolder(Path::css(), 'css');
    
        // Framework
        foreach(Phyrus::frameworkStyles() as $st) {
            $file = Path::framework() . "/assets/css/$st.css";
            $minifier->addFile($file);
        }
    
        // Assets
        $minifier->addFolder(Path::js(), 'js');

        // Framework
        foreach(Phyrus::frameworkScripts() as $fs)
        {
            $file = Path::framework() . "/assets/javascript/$fs.js";
            $minifier->addFile($file);
        }

        Assets::addMinifier($minifier);
    
        // generate file
        $minifier->minify($savepath, 'web');
    }

}