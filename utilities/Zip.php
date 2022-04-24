<?php

if (!in_array('zip', get_loaded_extensions())) {
    return;
}

class Zip {

    /**
     * Path to zip file.
     * 
     * @var string $path
     */
    public string $path;

    /**
     * Files to include in the zip
     * 
     * @var string[]
     */
    private array $_files = [];

    /**
     * Data to convert into files.
     * 
     * @var string[]
     */
    private array $_data = [];

    public function __construct($path = null) {
        $this->path = $path;
    }

    /**
     * Get Zip object instance.
     * 
     * @param string $path [Default null, new Zip]
     * 
     * @return Zip
     */
    public static function instance(string $path = null) : Zip {
        return new Zip($path);
    }

    /**
     * Save Zip into file
     * 
     * @param string $path
     * 
     * @return Zip self
     */
    public function save(string $path) : Zip {
        if (is_dir($path)) return $this;

        $this->path = $path;
        $zip = new ZipArchive();

        if ($zip->open($path, file_exists($path) ? ZipArchive::OVERWRITE : ZipArchive::CREATE) === TRUE)
        {
            foreach($this->_files as $file) {
                $basename = isset($file['newname']) ? $file['newname'] : basename($file['file']);
                $folder = $file['folder'] . "/$basename";
                $zip->addFile($file['file'], $folder);
            }

            foreach($this->_data as $name => $data) {
                $zip->addFromString($name, $data);
            }

            // All files are added, so close the zip file.
            $zip->close();
        }
        return $this;
    }

    /**
     * Add file to Zip.
     * 
     * @param string $filepath
     * @param string $folder inside Zip [Default none]
     * 
     * @return Zip self
     */
    public function add(string $filepath, string $folder = '', string $newname = null) : Zip {
        $this->_files[] = [
            'file' => $filepath,
            'folder' => $folder,
            'newname' => $newname
        ];
        return $this;
    }

    /**
     * Add file to Zip from data.
     * 
     * @param string $name File name
     * @param string $data
     * 
     * @return Zip self
     */
    public function addData(string $name, string $data) : Zip {
        $this->_data[$name] = $data;
        return $this;
    }

    /**
     * Add full directory to the Zip
     * 
     * @param string $dir
     * @param string $filter [Default none] File extension.
     * @param string $base [Default none]
     * 
     * @return Zip self
     */
    public function addDirectory(string $dir, string $filter = '*', string $base = null) : Zip {
        if (!is_dir($dir)) return $this;

        if ($base == null) {
            $this->addDirectory($dir, $filter, $dir);
        }
        else {

            $folder = Folder::instance($dir);
            $files = $folder->subfiles($filter);
            foreach($files as $file) {
                $diff = str_replace($base, '', dirname($file));

                $this->addFile($file, $diff);
            }

            $sub = $folder->subfolders();
            foreach($sub as $folder) {
                $this->addDirectory($folder, $filter, $base);
            }

        }
        return $this;
    }

    /**
     * Extract zip to path.
     * 
     * @param string $path
     * 
     * @return Zip self
     */
    public function extract(string $path) : Zip {
        $zip = new ZipArchive;
        if ($zip->open($this->path) === TRUE) {
            $zip->extractTo($path);
            $zip->close();
        }
        return $this;
    }

}