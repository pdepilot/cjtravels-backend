<?php
require_once __DIR__ . '/bootstrap/app.php';
require_once __DIR__ . '/routes/web.php';

use App\Core\Request;
use App\Core\Router;

$request = new Request();
$response = Router::dispatch($request);
$response->send();
