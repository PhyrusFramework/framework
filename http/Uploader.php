<?php

class Uploader {

    /**
     * Get an uploaded file.
     * 
     * @param string $name (optional)
     * 
     * @return null|Generic
     */
    public static function getFile($name = '') {
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

        $g = new Generic([
            'file' => $file,
            'path' => '',
            'name' => ''
        ]);

        $saveFile = function($g, $dir, $filename, $overwrite = false) {
            if (!file_exists($dir)) {
                mkdir($dir);
            }

            $g->set('name', $filename);

            $dir .= "/$filename";

            if (file_exists($dir)) {
                if ($overwrite) {
                    File::instance($dir)->delete();
                } else {
                    return;
                }
            }

            return File::instance($g->file->tmp)->copyTo($dir, $overwrite)->path;
        };

        $g->set('savePublic', function(string $filename, bool $overwrite = false) 
            use($file, $saveFile, $g) {

            $dir = Path::public() . '/' . Config::get('project.uploads.publicDir');
            $g->path = $saveFile($g, $dir, $filename, $overwrite);
            return $g;
        });

        $g->set('savePrivate', function(string $filename, bool $overwrite = false) 
            use($file, $saveFile, $g) {

            $dir = Path::root() . '/' . Config::get('project.uploads.privateDir');
            $g->path = $saveFile($g, $dir, $filename, $overwrite);
            return $g;
        });

        $g->set('relativePath', function() use($g) {
            $path = empty($g->path) ? '' : Path::toRelative($g->path, true);
            return $path;
        });

        $g->set('absolutePath', function() use($g) {
            return empty($g->path) ? '' : $g->path;
        });

        $g->set('filename', function() use($g) {
            return $g->name;
        });

        $g->set('saveImage', function() use($g) {
            $dir = Path::public() . '/' . Config::get('project.uploads.publicDir');;
            if (!file_exists($dir)) {
                mkdir($dir);
            }

            $dir .= '/' . Config::get('project.uploads.images.dir');
            if (!file_exists($dir)) {
                mkdir($dir);
            }

            $ext = $g->file->extension;
            $ext = $ext == 'png' ? 'png' : 'jpg';

            $img = Image::instance($g->file->tmp, $ext);
            $name = GUID();

            $g->set('name', "$name.$ext");

            $sizes = Config::get('project.uploads.images.sizes');
            $sizeFiles = [

            ];

            foreach($sizes as $size => $max) {

                $sizeDir = $dir . "/$size";
                if (!file_exists($sizeDir)) {
                    mkdir($sizeDir);
                }

                $fileRoute = $img->cap($max, $max, true)->save($sizeDir . "/$name.$ext", $ext, 100);
                $sizeFiles[$size] = Path::toRelative($fileRoute, true);
            }

            $g->set('getSize', function($name) use ($sizeFiles) {
                return $sizeFiles[$name] ?? '';
            });

            return $g;
        });

        return $g;
    }

}