<?php

namespace App\Http\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\RealtimeDataService;

class BusController extends AdminController
{
    public function index(Request $request): Response
    {
        RealtimeDataService::ensureBusBookingSchema();
        $status = $request->query('status');
        $bookings = RealtimeDataService::getBusBookings(100, $status);

        $pendingCount = count(array_filter($bookings, fn($b) => strcasecmp($b['status'], 'Pending') === 0));
        $confirmedCount = count(array_filter($bookings, fn($b) => strcasecmp($b['status'], 'Confirmed') === 0));

        return $this->adminView('bus', [
            'title' => 'Bus Tickets & Road Transport — CJ Travels Admin',
            'page' => 'bus',
            'bookings' => $bookings,
            'currentStatus' => $status,
            'popularRoutes' => RealtimeDataService::getPopularBusRoutes(),
            'counts' => [
                'total' => RealtimeDataService::busBookingCount(),
                'pending' => $pendingCount,
                'confirmed' => $confirmedCount,
            ],
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ]);
    }

    public function updateStatus(Request $request): Response
    {
        $id = self::bookingId($request->input('id'));
        $status = trim((string)$request->input('status'));
        if ($id === null) {
            return Response::json(['success' => false, 'error' => 'Choose a valid booking.'], 422);
        }

        $result = RealtimeDataService::updateBusBooking($id, ['status' => $status]);
        $code = !empty($result['success']) ? 200 : (($result['code'] ?? '') === 'not_found' ? 404 : 422);
        return Response::json($result, $code);
    }

    public function destroy(Request $request): Response
    {
        $id = self::bookingId($request->input('id'));
        if ($id === null) {
            return Response::json(['success' => false, 'error' => 'Choose a valid booking.'], 422);
        }

        $result = RealtimeDataService::deleteBusBooking($id);
        $code = !empty($result['success']) ? 200 : (($result['code'] ?? '') === 'not_found' ? 404 : 422);
        return Response::json($result, $code);
    }

    protected static function bookingId(mixed $raw): ?int
    {
        if (is_int($raw) && $raw > 0) {
            return $raw;
        }
        if (is_string($raw) && preg_match('/^[1-9]\d*$/', $raw)) {
            return (int)$raw;
        }
        return null;
    }
}
