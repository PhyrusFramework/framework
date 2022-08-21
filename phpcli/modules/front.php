<?php

class CLI_Front extends CLI_Module {

    public function command_page() {
        $this->comm_create(true);
    }

    public function command_component() {
        $this->comm_create(false);
    }

    private function comm_create(bool $page) {
        if (sizeof($this->params) < 1) {
            echo "\n" . ($page ? 'Page' : 'Component') . " name not specified\n";
            return;
        }

        $route = Path::front() . '/' . ($page ? 'pages' : 'components');

        $parts = explode('/', $this->params[0]);
        $last = '';
        foreach($parts as $p) {
            if ($p == '') continue;

            $route = "$route/$p";

            create_folder($route);
            $last = $p;
        }

        if ($last == '') return;

        $this->create_component($route, $last, $page);
    }

    private function create_component($route, $name, $page = false) {

        $vue = "$route/" . ($page ? 'index' : $name) . ".vue";
        $ts = "$route/$name.ts";
        $scss = "$route/$name.scss";

        // scss
        ob_start();?>
<?= ($page ? '#' : '.') ?><?= $name ?> {

}<?php
        $content = ob_get_clean();
        file_put_contents($scss, $content);

        // vue
        ob_start();?>
<template>
    <div <?= ($page ? 'id' : 'class') ?>="<?= $name ?>">

    </div>
</template>

<script lang="ts" src="./<?= $name ?>.ts"></script>
<style lang="scss" src="./<?= $name ?>.scss"></style><?php

        $content = ob_get_clean();
        file_put_contents($vue, $content);

        // ts
        ob_start();?>
import { AppComponent } from 'phyrus-nuxt';

export default AppComponent().extend({

    data() {
        const data : {
            // Type definition
        } = {
            // Values
        }

        return data;
    },

    created() {},

    methods: {}

})
<?php

        $content = ob_get_clean();
        file_put_contents($ts, $content);


        echo "\n" . ($page ? 'Page' : 'Component') . " created at $route\n";
    }

    public function command_sync() {

        cmd('npm --prefix ./front-end/ run generate');

        $path = Path::root() . '/www';

        Folder::instance($path)
        ->copyContentsTo(Path::root() . '/public')
        ->delete();

        echo "Output files moved to /public\n";
    }

    private function npm_install() {
        cmd('cd ./front-end && npm install');
    }

    public function command_run() {
        $modules = Path::front() . '/node_modules';

        if (!file_exists($modules)) {
            $this->npm_install();
        }

        cmd('npm --prefix ./front-end/ run dev');
    }

    public function command_install() {
        $modules = Path::front() . '/node_modules';

        if (file_exists($modules)) {
            Folder::instance($modules)->delete();
            echo "\nPrevious node_modules folder deleted.\n";
        }

        $this->npm_install();
    }

    public function command_remove() {

        Folder::instance(Path::front())->delete();

        $files = Folder::instance(Path::public())->subfiles();
        foreach($files as $file) {
            $n = basename($file);

            if (!in_array($n, [
                'index.php',
                '.htaccess'
            ])) {
                File::instance($file)->delete();
            }
        }

        $dirs = Folder::instance(Path::public())->subfolders();
        foreach($dirs as $dir) {
            Folder::instance($dir)->delete();
        }

        Config::save('project.only_API', true);

    }

    public function help() {?>

        The Nuxt command allows you to create project
        content in a moment: pages, components, classes, etc.

        -remove
        Remove front support and convert this project into a simple API.

        - page <name>
        Create a nuxt page.

        - component <name>
        Create a nuxt component

        - run 
        Run your front-end project at localhost:3000 

        - sync
        Publish your front-end changes to /public

        - install
        Install npm dependencies.

    <?php }

}