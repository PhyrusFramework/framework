<?php

class CLI_ClearCache extends CLI_Module {

    public function run() {

        // Cache folder
        $fold = Folder::instance(Path::src() . '/cache');
        if ($fold->exists()) {
            $fold->delete();
        }

        // Generated folder
        $fold = Folder::instance(Path::generated());
        if ($fold->exists()) {
            $fold->delete();
        }

        // .cch files
        $this->clearCCH(Folder::instance(Path::root()));

        // SCSS Modules
        $this->clearSCSS(Folder::instance(Path::root()));

        echo "\nAll caches cleared!\n";
    }

    private function clearCCH($folder) {

        $files = $folder->subfiles("cch");
        foreach($files as $file) {
            $f = new File($file);
            $f->delete();
        }

        $sub = $folder->subfolders();
        foreach($sub as $fold) {
            $fol = new Folder($fold);
            $this->clearCCH($fol);
        }

    }

    private function clearSCSS($folder) {

        $sub = $folder->subfolders();
        foreach($sub as $fold) {
            $name = basename($fold);

            if ($name == "scss") {
                $compiled = $fold . "/compiled";
                if (is_dir($compiled)) {
                    $compiled = new Folder($compiled);
                    $compiled->delete();
                }
            } else {
                $fol = new Folder($fold);
                $this->clearSCSS($fol);
            }
        }

    }

    public function help() { ?>

        The clear-caches command will clear
        all kind of cache in your project.

    <?php }

}