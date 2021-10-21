<?php
ob_start();

// Get page
$req = new RequestData(true);
$req->require('ajaxActionName');

// Load page
WebLoader::router();

// Clear output
ob_clean();

// AJAX

if (!Ajax::run($req->ajaxActionName)) {
    ApiResponse::notFound('Ajax function not defined');
}

response_die('ok');