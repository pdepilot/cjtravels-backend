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
            'message' => $ok ? 'Record successfully synchronized with MySQL database.' : (RealtimeDataService::lastError() ?: 'Failed to update record.'),
            'error' => $ok ? null : (RealtimeDataService::lastError() ?: 'Failed to update record.'),
            'data' => RealtimeDataService::getAdminData(),
        ], $ok ? 200 : 422);
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

    public function createFlight(Request $request): Response
    {
        $created = RealtimeDataService::createFlight($request->all());

        return Response::json([
            'success' => (bool)($created['success'] ?? false),
            'message' => $created['message'] ?? ($created['error'] ?? 'Could not add the flight.'),
            'error' => $created['error'] ?? null,
            'flight' => $created['flight'] ?? null,
            'data' => RealtimeDataService::getAdminData(),
        ], !empty($created['success']) ? 200 : 422);
    }

    public function createTransaction(Request $request): Response
    {
        $created = RealtimeDataService::createTransaction($request->all());

        return Response::json([
            'success' => (bool)($created['success'] ?? false),
            'message' => $created['message'] ?? ($created['error'] ?? 'Could not add the transaction.'),
            'error' => $created['error'] ?? null,
            'transaction' => $created['transaction'] ?? null,
            'data' => RealtimeDataService::getAdminData(),
        ], !empty($created['success']) ? 200 : 422);
    }

    public function createLoan(Request $request): Response
    {
        $created = RealtimeDataService::createLoan($request->all());

        return Response::json([
            'success' => (bool)($created['success'] ?? false),
            'message' => $created['message'] ?? ($created['error'] ?? 'Could not add the enquiry.'),
            'error' => $created['error'] ?? null,
            'loan' => $created['loan'] ?? null,
            'data' => RealtimeDataService::getAdminData(),
        ], !empty($created['success']) ? 200 : 422);
    }

    public function createHotel(Request $request): Response
    {
        $created = RealtimeDataService::createHotel($request->all());

        return Response::json([
            'success' => (bool)($created['success'] ?? false),
            'message' => $created['message'] ?? ($created['error'] ?? 'Could not add the hotel.'),
            'error' => $created['error'] ?? null,
            'hotel' => $created['hotel'] ?? null,
            'data' => RealtimeDataService::getAdminData(),
        ], !empty($created['success']) ? 200 : 422);
    }

    public function createCustomer(Request $request): Response
    {
        $created = RealtimeDataService::createCustomer($request->all());

        return Response::json([
            'success' => (bool)($created['success'] ?? false),
            'message' => $created['message'] ?? ($created['error'] ?? 'Could not add the customer.'),
            'error' => $created['error'] ?? null,
            'customer' => $created['customer'] ?? null,
            'data' => RealtimeDataService::getAdminData(),
        ], !empty($created['success']) ? 200 : 422);
    }

    public function saveProvider(Request $request): Response
    {
        $id = $request->input('id');
        $saved = $id
            ? RealtimeDataService::updateApiProvider($id, $request->all())
            : RealtimeDataService::createApiProvider($request->all());

        return Response::json([
            'success' => (bool)($saved['success'] ?? false),
            'message' => $saved['message'] ?? ($saved['error'] ?? 'Could not save this provider.'),
            'error' => $saved['error'] ?? null,
            'provider' => $saved['provider'] ?? null,
            'data' => RealtimeDataService::getAdminData(),
        ], !empty($saved['success']) ? 200 : 422);
    }

    public function deleteProvider(Request $request): Response
    {
        $deleted = RealtimeDataService::deleteApiProvider($request->input('id', ''));

        return Response::json([
            'success' => (bool)($deleted['success'] ?? false),
            'message' => $deleted['message'] ?? ($deleted['error'] ?? 'Could not remove this provider.'),
            'error' => $deleted['error'] ?? null,
            'data' => RealtimeDataService::getAdminData(),
        ], !empty($deleted['success']) ? 200 : 422);
    }

    public function testProvider(Request $request): Response
    {
        $tested = RealtimeDataService::testApiProvider($request->input('id', ''));

        return Response::json([
            'success' => (bool)($tested['success'] ?? false),
            'connected' => (bool)($tested['connected'] ?? false),
            'message' => $tested['message'] ?? ($tested['error'] ?? 'Could not test this provider.'),
            'error' => $tested['error'] ?? null,
            'data' => RealtimeDataService::getAdminData(),
        ], !empty($tested['success']) ? 200 : 422);
    }

    public function createDestination(Request $request): Response
    {
        $created = RealtimeDataService::createDestination($request->all());

        return Response::json([
            'success' => (bool)($created['success'] ?? false),
            'message' => $created['message'] ?? ($created['error'] ?? 'Could not add the destination.'),
            'error' => $created['error'] ?? null,
            'destination' => $created['destination'] ?? null,
            'data' => RealtimeDataService::getAdminData(),
        ], !empty($created['success']) ? 200 : 422);
    }
}
