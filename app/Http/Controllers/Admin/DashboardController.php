<?php

namespace App\Http\Controllers\Admin;

use App\Core\Database;
use App\Core\Response;
use App\Services\RealtimeDataService;

class DashboardController extends AdminController
{
    public function index(): Response
    {
        $payload = $this->buildDashboardData();

        return $this->adminView('dashboard', array_merge([
            'title' => 'Dashboard — CJ Travels Admin',
            'page' => 'dashboard',
        ], $payload));
    }

    public function statsApi(): Response
    {
        $payload = $this->buildDashboardData();
        return Response::json([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'formattedTime' => date('g:i:s A'),
            'data' => $payload,
        ]);
    }

    protected function buildDashboardData(): array
    {
        RealtimeDataService::ensureUserSchema();
        $db = Database::getInstance();

        // 1. KPI Counts
        $totalCustomers = (int)($db->selectOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0);
        $activeCustomers = (int)($db->selectOne("SELECT COUNT(*) as c FROM users WHERE status = 'Active' OR status IS NULL OR status = ''")['c'] ?? 0);
        $totalFlights = (int)($db->selectOne("SELECT COUNT(*) as c FROM flights")['c'] ?? 0);
        $pendingFlights = (int)($db->selectOne("SELECT COUNT(*) as c FROM flights WHERE status = 'Pending'")['c'] ?? 0);
        $totalHotels = (int)($db->selectOne("SELECT COUNT(*) as c FROM hotels")['c'] ?? 0);
        $pendingHotels = (int)($db->selectOne("SELECT COUNT(*) as c FROM hotels WHERE status = 'Pending'")['c'] ?? 0);
        $totalLoans = (int)($db->selectOne("SELECT COUNT(*) as c FROM loan_enquiries")['c'] ?? 0);

        // Transactions financial metrics
        $txnTotal = (float)($db->selectOne("SELECT COALESCE(SUM(amount), 0) as s FROM transactions")['s'] ?? 0);
        $txnPaid = (float)($db->selectOne("SELECT COALESCE(SUM(amount), 0) as s FROM transactions WHERE status = 'Paid'")['s'] ?? 0);
        $txnPending = (float)($db->selectOne("SELECT COALESCE(SUM(amount), 0) as s FROM transactions WHERE status = 'Pending'")['s'] ?? 0);
        $txnRefunded = (float)($db->selectOne("SELECT COALESCE(SUM(amount), 0) as s FROM transactions WHERE status = 'Refunded'")['s'] ?? 0);
        $txnFailed = (float)($db->selectOne("SELECT COALESCE(SUM(amount), 0) as s FROM transactions WHERE status = 'Failed'")['s'] ?? 0);

        // 2. Status Distribution (Donut Chart)
        $flightStatuses = $db->select("SELECT status, COUNT(*) as c FROM flights GROUP BY status");
        $hotelStatuses = $db->select("SELECT status, COUNT(*) as c FROM hotels GROUP BY status");

        $statusCounts = [
            'Confirmed' => 0,
            'Pending' => 0,
            'Completed' => 0,
            'Cancelled' => 0,
        ];

        foreach (array_merge($flightStatuses, $hotelStatuses) as $row) {
            $st = $row['status'] ?? '';
            if (isset($statusCounts[$st])) {
                $statusCounts[$st] += (int)$row['c'];
            } else {
                $statusCounts[$st] = (int)$row['c'];
            }
        }
        $totalBookings = array_sum($statusCounts);

        // 3. Monthly Booking Activity (Bar Chart)
        // Aggregate flight and hotel bookings by departure/checkin month
        $months = ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $monthlyFlights = array_fill_keys($months, 0);
        $monthlyHotels = array_fill_keys($months, 0);

        $flightMonths = $db->select("SELECT departure_date FROM flights");
        foreach ($flightMonths as $f) {
            if (!empty($f['departure_date'])) {
                $m = date('M', strtotime($f['departure_date']));
                if (isset($monthlyFlights[$m])) {
                    $monthlyFlights[$m]++;
                }
            }
        }

        $hotelMonths = $db->select("SELECT checkin FROM hotels");
        foreach ($hotelMonths as $h) {
            if (!empty($h['checkin'])) {
                $m = date('M', strtotime($h['checkin']));
                if (isset($monthlyHotels[$m])) {
                    $monthlyHotels[$m]++;
                }
            }
        }

        $bookingChart = [];
        $cursor = new \DateTimeImmutable('first day of this month');
        $cursor = $cursor->modify('-5 months');
        for ($i = 0; $i < 6; $i++) {
            $label = $cursor->format('M');
            $bookingChart[] = [
                'month' => $label,
                'flights' => $monthlyFlights[$label] ?? 0,
                'hotels' => $monthlyHotels[$label] ?? 0,
            ];
            $cursor = $cursor->modify('+1 month');
        }

        // 4. Recent Bookings (Combined Latest Flights & Hotels)
        $latestFlights = $db->select("SELECT f.id, f.ref, f.airline, f.flight_no, f.origin, f.destination, f.departure_date, f.price, f.status, 'flight' as type, f.created_at, u.name as customer_name FROM flights f LEFT JOIN users u ON f.user_id = u.id ORDER BY f.id DESC LIMIT 5");
        $latestHotels = $db->select("SELECT h.id, h.ref, h.property, h.location, h.checkin as departure_date, h.price, h.status, 'hotel' as type, h.created_at, u.name as customer_name FROM hotels h LEFT JOIN users u ON h.user_id = u.id ORDER BY h.id DESC LIMIT 5");

        $combined = array_merge($latestFlights, $latestHotels);
        usort($combined, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        $recentBookings = array_slice($combined, 0, 6);

        $formattedBookings = [];

        foreach ($recentBookings as $b) {
            $custName = trim((string)($b['customer_name'] ?? '')) ?: 'Unassigned';

            $isFlight = ($b['type'] ?? '') === 'flight';
            $title = $isFlight
                ? ($b['airline'] . ' · ' . $b['flight_no'])
                : ($b['property'] . ' · ' . $b['location']);
            $detail = $isFlight
                ? ($b['origin'] . ' → ' . $b['destination'])
                : ($b['property']);

            $formattedBookings[] = [
                'ref' => $b['ref'],
                'type' => $b['type'],
                'customer' => $custName,
                'title' => $title,
                'detail' => $detail,
                'date' => !empty($b['departure_date']) ? date('M j, Y', strtotime($b['departure_date'])) : '—',
                'amount' => (float)$b['price'],
                'formattedAmount' => '₦' . number_format((float)$b['price']),
                'status' => $b['status'],
            ];
        }

        // 5. Recent Activity Logs
        $recentTxns = $db->select("SELECT t.ref, t.amount, t.status, t.transacted_at, u.name as customer_name FROM transactions t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.id DESC LIMIT 3");
        $recentLoans = $db->select("SELECT l.ref, l.amount, l.purpose, l.submitted, u.name as customer_name FROM loan_enquiries l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT 2");

        $activity = [];
        foreach ($recentTxns as $t) {
            $activity[] = [
                'icon' => 'bi-credit-card-2-front-fill',
                'iconClass' => ($t['status'] === 'Paid' ? 'green' : 'gold'),
                'title' => ($t['status'] === 'Paid' ? 'Payment verified' : 'Payment pending'),
                'meta' => $t['ref'] . ' · ₦' . number_format((float)$t['amount']) . ' (' . ($t['customer_name'] ?? 'Client') . ')',
                'time' => date('M j', strtotime($t['transacted_at'] ?? 'now')),
            ];
        }
        foreach ($recentLoans as $l) {
            $activity[] = [
                'icon' => 'bi-cash-coin',
                'iconClass' => 'violet',
                'title' => 'Financing enquiry submitted',
                'meta' => $l['ref'] . ' · ₦' . number_format((float)$l['amount']) . ' (' . ($l['customer_name'] ?? 'Applicant') . ')',
                'time' => date('M j', strtotime($l['submitted'] ?? 'now')),
            ];
        }
        RealtimeDataService::ensureContactSchema();
        $recentMessages = $db->select('SELECT ref, name, department, created_at FROM contact_messages ORDER BY id DESC LIMIT 3');
        foreach ($recentMessages as $message) {
            $activity[] = [
                'icon' => 'bi-envelope-fill',
                'iconClass' => 'navy',
                'title' => 'Contact message received',
                'meta' => $message['ref'] . ' · ' . $message['name'] . ' (' . $message['department'] . ')',
                'time' => date('M j', strtotime($message['created_at'] ?? 'now')),
            ];
        }

        // 6. Return Clean Aggregated Data
        return [
            'stats' => [
                'customers' => $totalCustomers,
                'activeCustomers' => $activeCustomers,
                'flights' => $totalFlights,
                'pendingFlights' => $pendingFlights,
                'hotels' => $totalHotels,
                'pendingHotels' => $pendingHotels,
                'loans' => $totalLoans,
                'txnTotal' => $txnTotal,
                'txnPaid' => $txnPaid,
                'txnPending' => $txnPending,
                'txnRefunded' => $txnRefunded,
                'txnFailed' => $txnFailed,
                'formattedTxnTotal' => '₦' . number_format($txnTotal),
                'formattedTxnPaid' => '₦' . number_format($txnPaid),
                'formattedTxnPending' => '₦' . number_format($txnPending),
            ],
            'statusCounts' => $statusCounts,
            'totalBookings' => $totalBookings,
            'bookingChart' => $bookingChart,
            'recentBookings' => $formattedBookings,
            'activity' => $activity,
        ];
    }
}
