<?php

class CLI_Files extends CLI_Module {

    public function command_create() {

        if (sizeof($this->params) < 1) {
            echo "You need to specify a path.";
            return;
        }

        $path = $this->params[0];
        if ($path[0] != "/")
            $path = "/$path";

        $last = explode("/", $path);
        $last = $last[sizeof($last) - 1];

        $path = Path::project() . $path;
        
        if (strpos($last, ".") === FALSE) {
            create_folder($path);
        } else {

            // Check first if folder exist
            $folder = dirname($path);
            if (!is_dir($folder)) {
                create_folder($folder);
            }
            
            $overwrite = isset($this->flags['overwrite']);
            if (file_exists($path) && !$overwrite) {
                echo "\nThe file already exists. To overwrite use the flag --overwrite.\n";
                return;
            }

            $content = sizeof($this->params) > 1 ? $this->params[1] : "";
            file_put_contents($path, $content);
        }

    }

    public function command_delete() {

        if (sizeof($this->params) < 1) {
            echo "\nYou need to specify a path.\n";
            return;
        }

        $path = $this->params[0];
        if ($path[0] != "/")
            $path = "/$path";

        $path = Path::project() . $path;

        if (is_dir($path)) {
            Folder::instance($path)->delete();
        }
        else if (file_exists($path)) {
            File::instance($path)->delete();
        }
        else {
            echo "\nThe path is not a file nor a directory.\n";
        }

    }

    public function command_move() {

        $this->command_copy();
        $o = Path::project() . $this->params[0];
        if (is_dir($o)) {
            Folder::instance($o)->delete();
        }
        else if (file_exists($o)) {
            File::instance($o)->delete();
        }
    }

    public function command_copy() {

        if (sizeof($this->params) < 1) {
            echo "\nYou need to specify a source path.\n";
            return;
        }

        if (sizeof($this->params) < 2) {
            echo "\nYou need to specify a destination path.\n";
            return;
        }

        $o = Path::project() . $this->params[0];
        $d = Path::project() . $this->params[1];

        if (is_dir($o)) {
            Folder::instance($o)->copyTo($d);
        }
        else if (file_exists($o)) {
            File::instance($o)->copyTo($d);
        }
        else {
            echo "\nThe source path is not a file nor a directory.\n";
        }

    }

    public function help() { ?>
    
        The f command is used to manage
        files and folders. You can easily
        create, delete, rename or move
        directories and files.

        Paths should be relative to the
        root folder. Example:

        /src/code/myfile.php

        - create <path> <content>
        The create command can be used both
        for directories and files.
        If it's a file, optionally you can use
        a second parameter for the content.

        - delete <path>
        Delete file or folder.

        - copy <oldPath> <newPath>
        Copy a file or a folder to another location.

        - move <oldPath> <newPath>
        With this command you can move or rename
        a folder o a file to a new location.

    <?php }

}