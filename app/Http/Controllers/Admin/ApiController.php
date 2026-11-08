<?php

namespace App\Http\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Services\RealtimeDataService;

class ApiController extends AdminController
{
    public function index(): Response
    {
        return $this->adminView('api', [
            'title' => 'API Integrations — Travelbookings Admin',
            'page' => 'api'
        ]);
    }

    public function liveData(): Response
    {
        return Response::json([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => RealtimeDataService::getAdminData(),
        ]);
    }

    public function updateRecord(Request $request): Response
    {
        $kind = (string)$request->input('kind', '');
        $id = $request->input('id', '');
        $fields = (array)$request->input('fields', []);

        if (empty($kind) || empty($id)) {
            return Response::json([
                'success' => false,
                'error' => 'Missing kind or record ID for update.',
            ], 400);
        }

        $ok = RealtimeDataService::updateRecord($kind, $id, $fields);

        return Response::json([
            'success' => $ok,
            'message' => $ok ? 'Record successfully synchronized with MySQL database.' : 'Failed to update record.',
            'data' => RealtimeDataService::getAdminData(),
        ]);
    }

    public function deleteRecord(Request $request): Response
    {
        $kind = (string)$request->input('kind', '');
        $id = $request->input('id', '');

        if (empty($kind) || empty($id)) {
            return Response::json([
                'success' => false,
                'error' => 'Missing kind or record ID for deletion.',
            ], 400);
        }

        $ok = RealtimeDataService::deleteRecord($kind, $id);

        return Response::json([
            'success' => $ok,
            'message' => $ok ? 'Record successfully deleted from MySQL database.' : 'Failed to delete record.',
            'data' => RealtimeDataService::getAdminData(),
        ]);
    }

    public function createDestination(Request $request): Response
    {
        $created = RealtimeDataService::createDestination($request->all());

        return Response::json([
            'success' => true,
            'message' => 'Destination successfully added to MySQL database.',
            'destination' => $created,
            'data' => RealtimeDataService::getAdminData(),
        ]);
    }
}
