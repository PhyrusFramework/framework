<?php

class CLI_Zip extends CLI_Module {

    public function run() {

        if (!in_array("zip", get_loaded_extensions())) {
            echo "\nYou need the PHP Zip extension\n";
            return;
        }
        
        parent::run();
    }

    public function command_compress() {

        if (sizeof($this->params) == 0) {
            echo "\nYou need to add at least one file to the zip.\n";
            return;
        }

        $route = $this->params[0];
        if ($route[0] != "/")
            $route = "/$route";

        $route = Path::project() . $route;

        $zip = new Zip();
        
        if (is_dir($route)) {
            $name = basename($route);
            $zip->addDirectory($route);
        }
        else {
            $name = file_name($route, false);
            $zip->add($route);
        }

        $folder = dirname($route);
        
        if (sizeof($this->params) < 2)
            $zipfile = "$folder/$name.zip";
        else {
            $route = $this->params[1];
            if ($route[0] != "/")
                $route = "/$route";
            $zipfile = Path::project() . $route;
        }

        $zip->save($zipfile);

    }

    public function command_extract() {

        if (sizeof($this->params) == 0) {
            echo "\nYou need to pass the path to the zip.\n";
            return;
        }

        $route = $this->params[0];
        if ($route[0] != "/")
            $route = "/$route";

        $zipfile = Path::project() . $route;
        if (!file_exists($zipfile)) {
            echo "\nThis zip file does not exist.\n";
            return;
        }

        if (sizeof($this->params) < 2)
            $dpath = dirname($zipfile);
        else {
            $dpath = $this->params[1];
            if ($dpath[0] != "/")
                $dpath = "/$dpath";

            $dpath = Path::project() . $dpath;

            if (!is_dir($dpath)) {
                create_folder($dpath);
            }
        }

        Zip::instance($zipfile)->extract($dpath);

    }

    public function help() { ?>

        The Zip command lets you compress or unzip a
        zip file with just a line.

        - compress <folder/file> <zip name>
        Compress a specific file or a whole folder. By
        default the name of the zip will be the same as
        the folder or the file. But you can change it
        with a second optional parameter.

        Examples:
        compress /resources   ---> generates resources.zip

        compress /uploads/images /photos.zip
        Will compress the subfolder "images", but will create
        a zip in this folder.

        If you want to create the zip file in the same subfolder:
        compress /uploads/images /uploads/photos.zip

        - extract <zip path> <folder>
        extract file.zip

        You can specify the route where the zip will be
        extracted. If the folder does not exist, it will
        be created:

        extract subfolder/file.zip  /thefolder

    <?php }

}