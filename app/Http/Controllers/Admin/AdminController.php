<?php

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Response;
use App\Http\Controllers\Controller;
use App\Services\RealtimeDataService;

abstract class AdminController extends Controller
{
    protected function adminView(string $view, array $data = []): Response
    {
        $admin = Auth::admin();
        $realtimeData = RealtimeDataService::getAdminData();

        return $this->view('admin/' . $view, array_merge([
            'admin' => $admin,
            'adminUser' => $admin ? $admin->name : 'Administrator',
            'adminRole' => $admin ? $admin->role : 'Administrator',
            'adminInitials' => $admin ? $admin->getInitials() : 'AD',
            'notificationsCount' => 3,
            'realtimeData' => $realtimeData,
            'liveCounts' => $realtimeData['counts'] ?? [],
        ], $data));
    }
}
