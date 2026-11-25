<?php
require_once __DIR__ . '/../bootstrap/app.php';
$routes = \App\Services\RealtimeDataService::getPopularBusRoutes();
include __DIR__ . '/../resources/views/pages/bus.php';
