<?php

class Uploader {

    private $file = '';
    private $path = '';
    private $name = '';
    private $sizeFiles = [];

    function __construct($file) {
        $this->file = $file;
    }

    /**
     * Get the file extension
     * 
     * @return string
     */
    public function extension() : string {
        return $this->file->extension;
    }

    /**
     * Get the file name
     * 
     * @return string
     */
    public function name() : string {
        return $this->file->name;
    }

    /**
     * Get an uploaded file.
     * 
     * @param string $name (optional)
     * 
     * @return null|UploaderFile
     */
    public static function getFile($name = '') : ?Uploader {
        $req = new RequestData();

        if (!$req->hasFiles()) {
            return null;
        }

        $file = null;
        if (!empty($name)) {
            $file = $req->hasFile($name) ? $req->getFile($name) : null;
        } else {
            $filename = '';
            foreach($_FILES as $k => $v) {
                $filename = $k;
                break;
            }

            $file = empty($filename) ? null : $req->getFile($filename);
        }

        if (!$file) return null;

        return new Uploader($file);
    }

    /**
     * Stores the file.
     * 
     * @param string Directory
     * @param string File name
     * @param bool Overwrite
     * 
     * @return File|null
     */
    private function saveFile($dir, $filename, $overwrite = false) {

        $total = "$dir/$filename";
        $folder = Folder::instance(dirname($total));

        if (!$folder->exists()) {
            $folder->create();
        }
        
        $this->name = basename($filename);

        $dir .= ($filename[0] == '/' ? $filename : "/$filename");

        if (file_exists($dir)) {
            if ($overwrite) {
                File::instance($dir)->delete();
            } else {
                return;
            }
        }

        return File::instance($this->file->tmp)->copyTo($dir, $overwrite)->path;

    }

    /**
     * Save the file as a public file.
     * 
     * @param string File name
     * @param bool Overwrite if exists
     * 
     * @return Uploader self
     */
    public function savePublic(string $filename, bool $overwrite = false) : Uploader {
        $dir = Path::public() . '/' . Config::get('project.uploads.publicDir');
        $this->path = $this->saveFile($dir, $filename, $overwrite);
        return $this;
    }

    /**
     * Save the file as a private file.
     * 
     * @param string File name
     * @param bool Overwrite if exists
     * 
     * @return Uploader self
     */
    public function savePrivate(string $filename, bool $overwrite = false) : Uploader {
        $dir = Path::root() . '/' . Config::get('project.uploads.privateDir');
        $this->path = $this->saveFile($dir, $filename, $overwrite);
        return $this;
    }

    /**
     * Get relative path to the file.
     * 
     * @return string
     */
    public function relativePath() : string {
        $path = empty($this->path) ? '' : Path::toRelative($this->path, true);
        return $path; 
    }

    /**
     * Get absolute path to the file.
     * 
     * @return string
     */
    public function absolutePath() : string {
        return empty($this->path) ? '' : $this->path;
    }

    /**
     * Get the name of the stored file.
     * 
     * @return string
     */
    public function filename() : string {
        return $this->name;
    }

    /**
     * Save this file as an image.
     * 
     * @param string|null File name. If not indicated, uses GUID()
     * 
     * @return Uploader self
     */
    public function saveImage($name = null) : Uploader {

        $dir = Path::public() . '/' . Config::get('project.uploads.publicDir');
        $dir .= '/' . Config::get('project.uploads.images.dir');
        if (!file_exists($dir)) {
            Folder::instance($dir)->create();
        }

        $ext = $this->file->extension;
        $ext = $ext == 'png' ? 'png' : 'jpg';

        $img = Image::instance($this->file->tmp, $ext);
        $name = $name ?? GUID();

        $this->name = "$name.$ext";

        $sizes = Config::get('project.uploads.images.sizes');
        if (empty($sizes)) return $this;

        $sizeFiles = [ ];

        foreach($sizes as $size => $max) {

            $sizeDir = $dir . "/$size";
            if (!file_exists($sizeDir)) {
                mkdir($sizeDir);
            }

            $fileRoute = $img->cap($max, $max, true)->save($sizeDir . "/$name.$ext", $ext, 100);
            $sizeFiles[$size] = Path::toRelative($fileRoute, true);
        }

        $this->sizeFiles = $sizeFiles;

        return $this;

    }

    /**
     * Get the path to the image in a specific size.
     * 
     * @param string Name of the size
     * 
     * @return string
     */
    public function getSize(string $name) : string {
        return $this->sizeFiles[$name] ?? '';
    }

}