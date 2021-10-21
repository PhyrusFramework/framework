<?php
require_once(__DIR__.'/Assets.php');

// Framework assets
// JS
$_importJS = function() {

    $frameworkPath = Path::framework(true) . '/assets/javascript';

    if (Config::get('assets.js.vue')) {
        Footer::add(function() use ($frameworkPath) {?>
<script src="<?= $frameworkPath ?>/vue/phyrus.js"></script>
<script src="<?= $frameworkPath ?><?= Config::get('development_mode') ? '/vue/vue.dev.js' : '/vue/vue.min.js'?>" async defer onload="_vueLoaded()"></script>
<?php 
        });
    }

    foreach(Phyrus::frameworkScripts() as $script) {
        Assets::include_js("$frameworkPath/$script.js");
    }

};
$_importJS();

// CSS
Assets::css_in(__DIR__ . '/css', false);