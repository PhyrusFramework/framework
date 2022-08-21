<?php

class Uploader {

    private $file;
    private $path;
    private $name;
    private $sizeFiles = [];

    function __construct($file) {
        $this->file = $file;
    }

    public function extension() {
        return $this->file->extension;
    }

    public function name() {
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


    private function saveFile($dir, $filename, $overwrite = false) {

        $total = "$dir/$filename";
        $folder = Folder::instance(dirname($total));

        if (!$folder->exists()) {
            $folder->create();
        }
        
        $this->name = basename($filename);

        $dir .= ($filename[0] == '/' ? $filename : "$filename");

        if (file_exists($dir)) {
            if ($overwrite) {
                File::instance($dir)->delete();
            } else {
                return;
            }
        }

        return File::instance($this->file->tmp)->copyTo($dir, $overwrite)->path;

    }

    public function savePublic(string $filename, bool $overwrite = false) {
        $dir = Path::public() . '/' . Config::get('project.uploads.publicDir');
        $this->path = $this->saveFile($dir, $filename, $overwrite);
        return $this;
    }

    public function savePrivate(string $filename, bool $overwrite = false) {
        $dir = Path::root() . '/' . Config::get('project.uploads.privateDir');
        $this->path = $this->saveFile($dir, $filename, $overwrite);
        return $this;
    }

    public function relativePath() {
        $path = empty($this->path) ? '' : Path::toRelative($this->path, true);
        return $path; 
    }

    public function absolutePath() {
        return empty($this->path) ? '' : $this->path;
    }

    public function filename() {
        return $this->name;
    }

    public function saveImage() {

        $dir = Path::public() . '/' . Config::get('project.uploads.publicDir');;
        if (!file_exists($dir)) {
            mkdir($dir);
        }

        $dir .= '/' . Config::get('project.uploads.images.dir');
        if (!file_exists($dir)) {
            mkdir($dir);
        }

        $ext = $this->file->extension;
        $ext = $ext == 'png' ? 'png' : 'jpg';

        $img = Image::instance($this->file->tmp, $ext);
        $name = GUID();

        $this->name = "$name.$ext";

        $sizes = Config::get('project.uploads.images.sizes');
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

    public function getSize($name) {
        return $this->sizeFiles[$name] ?? '';
    }

}