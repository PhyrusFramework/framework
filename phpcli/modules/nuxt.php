<?php

class CLI_Nuxt extends CLI_Module {

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
#<?= $name ?> {

}<?php
        $content = ob_get_clean();
        file_put_contents($scss, $content);

        // vue
        ob_start();?>
<template>
    <div id="<?= $name ?>">

    </div>
</template>

<script lang="ts" src="./<?= $name ?>.ts"></script>
<style lang="scss" src="./<?= $name ?>.scss"></style><?php

        $content = ob_get_clean();
        file_put_contents($vue, $content);

        // ts
        ob_start();?>
import { <?php echo ($page ? 'AppPage' : 'AppComponent') ?> } from 'phyrus-nuxt';

export default <?php echo ($page ? 'AppPage()' : 'AppComponent') ?>.extend({

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

    public function command_run() {
        cmd('npm --prefix ./front-end/ run dev');
    }

    public function help() {?>

        The Nuxt command allows you to create project
        content in a moment: pages, components, classes, etc.

        - page <name>
        Create a nuxt page.

        - component <name>
        Create a nuxt component

        - run 
        Run your front-end project at localhost:3000 

        - sync
        Publish your front-end changes to /public

    <?php }

}