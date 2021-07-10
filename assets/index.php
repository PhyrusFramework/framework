<?php
require_once(__DIR__.'/Assets.php');

// Framework assets
// JS
$importJS = function() {

    $frameworkPath = Path::framework(true) . '/assets/javascript';

    foreach(Phyrus::frameworkScripts() as $script) {
        Assets::include_js("$frameworkPath/$script.js");
    }

    foreach([
        'dom'
    ] as $script) {
        Assets::include_js("$frameworkPath/$script.js", true);
    }

};
$importJS();

// CSS
Assets::css_in(__DIR__ . '/css', false);

// Modal ajax function
Ajax::add('_modal_get_component', function($req) {

    component($req->component, $req->has('parameters') ? $req->parameters : []);

});