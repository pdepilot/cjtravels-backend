<?php

define('LARAVEL_START', microtime(true));
$app = require_once dirname(__DIR__, 2) . '/bootstrap/app';
require_once dirname(__DIR__, 2) . '/routes/web';

$controller = new \App\Http\Controllers\Admin\AuthController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = $controller->login(new \App\Core\Request());
} else {
    $response = $controller->showLoginForm();
}
$response->send();
