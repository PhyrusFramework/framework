<?php

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
     * 
     * @return Folder
     */
    public function create() : Folder {
        create_folder($this->path);
        return $this;
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
     * 
     * @return Folder
     */
    public function empty() : Folder {
        if (!is_dir($this->path)) return $this;

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

        return $this;
    }

    /**
     * Delete this folder.
     * 
     * @return Folder
     */
    public function delete() : Folder {
        if (!is_dir($this->path)) return $this;
        $this->empty();
        rmdir($this->path);
        return $this;
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
        return glob($this->path . "/{,.}*[!.]*",GLOB_MARK|GLOB_BRACE);
    }

    /**
     * Navigate through the directory.
     * 
     * @param string $displace
     * 
     * @return Folder
     */
    public function cd(string $displace) : Folder {

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
                return $this;
        }

        return $this;

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
     * 
     * @return Folder
     */
    public function copyTo(string $newpath) : Folder {

        if (!is_dir($newpath)) {
            create_folder($newpath);

            if (!is_dir($newpath)) return $this;
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

        return $this;
    }

    /**
     * Move this folder and all its content to another location.
     * 
     * @param string $newpath
     * 
     * @return Folder
     */
    public function moveTo(string $newpath) : Folder {
        
        $this->copyTo($newpath);
        $this->delete();
        return $this;

    }

    /**
     * Copy files and directories inside to another location.
     * 
     * @param string new path
     * @param bool merge folders
     * 
     * @return Folder
     */
    public function copyContentsTo(string $newpath, bool $merge = true) : Folder {
        $contents = $this->ls();

        foreach($contents as $f) {

            $p = str_replace($this->path, $newpath, $f);

            if (is_dir($f)) {

                if (file_exists($p)) {

                    if (!$merge) {
                        Folder::instance($p)->delete();
                        Folder::instance($f)->copyTo($p);
                    } else {
                        Folder::instance($f)->copyContentsTo($p, true);
                    }

                } else {
                    Folder::instance($f)->copyTo($p);
                }

            } else {

                $file = new File($f);
                $file->copyTo($p, true);
            }

        }

        return $this;
    }

    /**
     * Get directory last modification date, based on the newest file.
     * 
     * @return string
     */
    public function lastModificationDate() : string {
        $newest = '0000-00-00 00:00:00';

        $subfiles = $this->subfiles();
        foreach($subfiles as $f) {
            $date = last_modification_date($f);
            if ($date > $newest) {
                $newest = $date;
            }
        }

        $subfolders = $this->subfolders();
        foreach($subfolders as $f) {
            $date = Folder::instance($f)->lastModificationDate();
            if ($date > $newest) {
                $newest = $date;
            }
        }

        return $newest;
    }

}