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
        $id = (int)$request->input('id');
        $status = trim((string)$request->input('status'));

        if ($id <= 0 || !in_array($status, ['Pending', 'Confirmed', 'Processing', 'Cancelled'], true)) {
            return Response::json(['success' => false, 'error' => 'Invalid booking ID or status.'], 422);
        }

        $updated = RealtimeDataService::updateRecord('bus', $id, ['status' => $status]);
        return Response::json([
            'success' => $updated,
            'message' => $updated ? "Booking #{$id} status updated to {$status}." : 'Failed to update booking status.',
        ]);
    }

    public function destroy(Request $request): Response
    {
        $id = (int)$request->input('id');
        if ($id <= 0) {
            return Response::json(['success' => false, 'error' => 'Invalid booking ID.'], 422);
        }

        $deleted = RealtimeDataService::deleteRecord('bus', $id);
        return Response::json([
            'success' => $deleted,
            'message' => $deleted ? 'Booking enquiry removed successfully.' : 'Failed to delete booking enquiry.',
        ]);
    }
}
