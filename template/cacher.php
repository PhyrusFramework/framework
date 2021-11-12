<?php

class Cacher {

    public static function getPath(string $file) {
        $base = Path::project();
        $path = str_replace($base, '', str_replace('\\', '/', $file));
        $path = str_replace('/' . Definitions::get('src', 'src') . '/', '/' . Definitions::get('cached', '_cached') . '/', $path);
        return $base . $path;
    }

    public static function reverse(string $file) {
        $base = Path::project();
        $path = str_replace($base, '', str_replace('\\', '/', $file));
        $path = str_replace('/' . Definitions::get('cached', '_cached') . '/', '/' . Definitions::get('src', 'src') . '/', $path);
        return $base . $path;
    }

    public static function hasFile(string $file) {
        $path = self::getPath($file);
        return file_exists($path) ? $path : null;
    }

    public static function write(string $file, string $content) : string {

        $base = Path::project();
        $path = str_replace($base, '', str_replace('\\', '/', $file));
        $path = str_replace('/' . Definitions::get('src', 'src') . '/', '/' . Definitions::get('cached', '_cached') . '/', $path);

        $dir = dirname($base . $path);
        $dirs = [];

        while(!file_exists($dir)) {
            $dirs[] = $dir;
            $dir = dirname($dir);
        }

        for($i = sizeof($dirs) - 1; $i >= 0; --$i) {
            mkdir($dirs[$i]);
        }

        file_put_contents($base . $path, $content);
        return $base . $path;
    }

    public static function getFile(string $file) : string {
        $cacheVersion = self::getPath($file);
        if (!file_exists($cacheVersion)) return '';

        return file_get_contents($cacheVersion);
    }

}