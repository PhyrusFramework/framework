<?php

/**
 * Get the file name with or without extension.
 * 
 * @param string $path
 * @param bool $extension [Default true]
 * 
 * @return string
 */
function file_name(string $path, bool $extension = true) : string {
    $path_parts = pathinfo($path);
    if ($extension) {
        return $path_parts['basename'];
    } else {
        return $path_parts['filename'];
    }
}

/**
 * Get the file extension.
 * 
 * @param string $path
 * 
 * @return string
 */
function file_extension(string $path) : string {
    $path_parts = pathinfo($path);
    return $path_parts['extension'];
}

/**
 * File last modification date in format Y-m-d H:i:s
 * 
 * @param string $filename
 * 
 * @return string
 */
function last_modification_date(string $filename) : string {
    if (!file_exists($filename)) return '';
    return date('Y-m-d H:i:s', filemtime($filename));
}

/**
 * Create a folder and all parent folders needed to complete the path.
 * 
 * @param string $path
 * @param int $permissions [Default 0777]
 * 
 * @return bool
 */
function create_folder(string $path, int $permissions = 0777) : bool {
    $root = Path::root();
    $diff = str_replace($root, '', $path);
    $diff = str_replace("\\", '/', $diff);
    $parts = explode('/', $diff);

    $r = '';
    foreach($parts as $p) {
        if (empty($p)) continue;
        
        $r .= "/$p";
        if (!in_array($p, array('.', '..')) && !is_dir(Path::root() . $r) && !file_exists($path)) {
            mkdir($path, $permissions, true);
        }
    }
    
    return is_dir($path);
}

/**
 * Get directory subfolders.
 * 
 * @param string $path
 * 
 * @return string[]
 */
function subfolders(string $path) : array {
    if (!is_dir($path)) return [];
    return array_filter(glob($path . '/*'), 'is_dir');
}

/**
 * Get files in directory.
 * 
 * @param string $path
 * @param string $extension [Default *]
 * 
 * @return string[]
 */
function subfiles(string $path, string $extension = '*') : array {
    return glob($path . "/*.$extension");
}

/**
 * Deletes a folder.
 * 
 * @param string $path
 */
function delete_folder(string $path) {
    $folder = new Folder($path);
    $folder->delete();
}

class Folder {

    /**
     * Folder path
     * 
     * @var string $path
     */
    public string $path;

    function __construct($path) {
        $this->path = $path;
    }

    /**
     * Create a folder object instance.
     * 
     * @param string $path
     * 
     * @return Folder
     */
    public static function instance(string $path) : Folder {
        return new Folder($path);
    }

    /**
     * Get files in folder.
     * 
     * @param string $extension [Default *]
     * 
     * @return string[]
     */
    public function subfiles(string $extension = '*') : array {
        return subfiles($this->path, $extension);
    }

    /**
     * Get folders in this directory.
     * 
     * @return array
     */
    public function subfolders() : array {
        return subfolders($this->path);
    }

    /**
     * Create this folder if not exists.
     */
    public function create() {
        create_folder($this->path);
    }

    /**
     * Check if folder exists.
     * 
     * @return bool
     */
    public function exists() : bool {
        return file_exists($this->path) && is_dir($this->path);
    }

    /**
     * Delete everything in this folder.
     */
    public function empty() {
        if (!is_dir($this->path)) return;

        $files = $this->subfiles();
        foreach($files as $file) {
            if ($file == '.' || $file == '..') continue;
            if ($file == '/') continue;

            unlink($file);
        }

        $folders = $this->subfolders();
        foreach($folders as $f) {
            $fo = new Folder($f);
            $fo->delete();
        }
    }

    /**
     * Delete this folder.
     */
    public function delete() {
        if (!is_dir($this->path)) return;
        $this->empty();
        rmdir($this->path);
    }

    /**
     * Get the parent folder.
     * 
     * @return Folder
     */
    public function parent() : Folder {
        return new Folder( dirname($this->path));
    }

    /**
     * Get a list of everything in the directory, files and folders.
     * 
     * @return array
     */
    public function ls() : array {
        return glob($this->path . '/*');
    }

    /**
     * Navigate through the directory.
     * 
     * @param string $displace
     */
    public function cd(string $displace) {

        if (strpos($displace, '/'))
            $d = explode('/', $displace);
        else
            $d = array($displace);

        foreach($d as $p) {
            if ($p == '.') continue;
            if ($p == '..') $this->path = dirname($this->path);

            else if (is_dir($this->path . "/$p"))
                $this->path .= "/$p";
            else
                return;
        }

    }

    /**
     * Create a file in this directory.
     * 
     * @param string $name File name
     * 
     * @param string $content [Default empty]
     */
    public function createFile(string $name, string $content = '') : File {
        $myfile = fopen($this->path . "/$name", 'w');
        fwrite($myfile, $content);
        fclose($myfile);
        return new File($myfile);
    }

    /**
     * Get File object of a file in this directory.
     * 
     * @param string $name Relative to this path.
     * 
     * @return File $file
     */
    public function getFile(string $name) : File {
        return new File($this->path . "/$name");
    }

    /**
     * Copy folder and all its contents to another location.
     * 
     * @param string $newpath
     */
    public function copyTo(string $newpath) {

        if (!is_dir($newpath)) {
            create_folder($newpath);

            if (!is_dir($newpath)) return;
        }

        $files = $this->subfiles();
        foreach($files as $file) {

            $name = basename($file);
            file_put_contents($newpath . "/$name", file_get_contents($file));
        }

        $folders = $this->subfolders();
        foreach($folders as $fold) {
            $name = basename($fold);
            $p = "$newpath/$name";
            Folder::instance($fold)->copyTo($p);
        }

    }

    /**
     * Move this folder and all its content to another location.
     * 
     * @param string $newpath
     */
    public function moveTo(string $newpath) {
        
        $this->copyTo($newpath);
        $this->delete();

    }

}

class File {

    /**
     * File path.
     * 
     * @var string $path
     */
    public string $path;

    function __construct($filename) {
        $this->path = $filename;
    }

    /**
     * Get a File object instance.
     * 
     * @param string $path
     * 
     * @return File
     */
    public static function instance(string $path) : File {
        return new File($path);
    }

    /**
     * Check if this file exists.
     * 
     * @return bool
     */
    public function exists() : bool {
        return file_exists($this->path);
    }

    /**
     * Get the file name with or without extension.
     * 
     * @param bool $extension [Default true]
     * 
     * @return string
     */
    public function name(bool $extension = true) : string {
        $path_parts = pathinfo($this->path);
        if ($extension) {
            return $path_parts['basename'];
        } else {
            return $path_parts['filename'];
        }
    }

    /**
     * Get the file extension.
     * 
     * @return string
     */
    public function extension() : string {
        return file_extension($this->path);
    }

    /**
     * Get directory of this file.
     * 
     * @return string
     */
    public function folder() : string {
        return dirname($this->path);
    }

    /**
     * Get the file content.
     * 
     * @return string
     */
    public function content() : string {
        if (!file_exists($this->path)) return '';
        return file_get_contents($this->path);
    }

    /**
     * Write content into file.
     * 
     * @param string $content
     */
    public function write(string $content) {
        $file = fopen($this->path, 'w');
        fwrite($file, $content);
        fclose($file);
    }

    /**
     * Append content to file.
     * 
     * @param string $content
     */
    public function append(string $content) {
        $c = $this->content();
        $this->write($c . $content);
    }

    /**
     * Prepend content to file.
     * 
     * @param string $content
     */
    public function prepend($content) {
        $c = $this->content();
        $this->write($content . $c);
    }

    /**
     * Delete file.
     */
    public function delete() {
        if (!$this->exists()) return;
        unlink($this->path);
    }

    /**
     * Get the file last modification date.
     * 
     * @param string
     */
    public function modification_date() : string {
        if (!file_exists($this->path)) return '';
        return date('Y-m-d H:i:s', filemtime($this->path));
    }

    /**
     * Get Folder object of the file directory.
     * 
     * @return Folder
     */
    public function getFolder() : Folder {
        return new Folder(dirname($this->path));
    }

    /**
     * Create a File from base64 string.
     * 
     * @param string $base64
     * @param string $filename
     * 
     * @return File
     */
    public static function parseBase64(string $base64, string $filename) : File {
        $ifp = fopen( $filename, 'wb' ); 
        $data = explode( ',', $base64 );
        fwrite( $ifp, base64_decode( $data[ 1 ] ) );
        fclose( $ifp ); 
        return new File($filename); 
    }

    /**
     * Try to guess the mime type.
     * 
     * @return string
     */
    public function getMime() : string {
        return mime_content_type($this->path);
    }

    /**
     * Convert this file to base64 string.
     * 
     * @param string $filetype [Default automatic] (png, jpeg, etc)
     * 
     * @return string
     */
    public function toBase64(string $filetype = null) : string {

        $type = $filetype ?? mime_content_type($this->path);

        $binary = fread(fopen($this->path, 'r'), filesize($this->path));
        return 'data:' . $type . ';base64,' . base64_encode($binary);

    }

    /**
     * Copy this file to another location.
     * 
     * @param string $newpath
     * @param bool $overwrite [Default false]
     * 
     * @param File
     */
    public function copyTo(string $newpath, bool $overwrite = false) : ?File {
        if (file_exists($newpath) && !$overwrite) {
            return null;
        }
        file_put_contents($newpath, file_get_contents($this->path));
        return File::instance($newpath);
    }

    /**
     * Move this file to another location.
     * 
     * @param string $newpath
     * @param bool $overwrite [Default false]
     * 
     * @return File
     */
    public function moveTo(string $newpath, bool $overwrite = false) : File {
        $this->copyTo($newpath, $overwrite);
        $this->delete();
        return File::instance($newpath);
    }

}