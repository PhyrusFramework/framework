<?php

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
     * 
     * @return File
     */
    public function write(string $content) : File {
        $file = fopen($this->path, 'w');
        fwrite($file, $content);
        fclose($file);
        return $this;
    }

    /**
     * Append content to file.
     * 
     * @param string $content
     * 
     * @return File
     */
    public function append(string $content) : File {
        $c = $this->content();
        $this->write($c . $content);
        return $this;
    }

    /**
     * Prepend content to file.
     * 
     * @param string $content
     * 
     * @return File
     */
    public function prepend($content) : File {
        $c = $this->content();
        $this->write($content . $c);
        return $this;
    }

    /**
     * Find and replace text inside the file.
     * 
     * @param string $text
     * @param string $replace
     * 
     * @return File self
     */
    public function replace(string $text, string $replace) : File {
        $c = $this->content();
        $c = str_replace($text, $replace, $c);
        $this->write($c);
        return $this;
    }

    /**
     * Delete file.
     * 
     * @return File
     */
    public function delete() : File {
        if (!$this->exists()) return $this;
        unlink($this->path);
        return $this;
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