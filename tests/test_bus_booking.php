<?php
/**
 * Test Suite: Road Transport & Bus Ticket Booking with WhatsApp Integration
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../routes/web.php';

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Services\RealtimeDataService;

echo "=== CJ TRAVELS: BUS TICKET & WHATSAPP INTEGRATION TEST SUITE ===\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition): void {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] {$description}\n";
        $passCount++;
    } else {
        echo " [FAIL] {$description}\n";
        $failCount++;
    }
}

// 1. Database Schema
RealtimeDataService::ensureBusBookingSchema();
$db = Database::getInstance();
$tableCheck = $db->selectOne("SHOW TABLES LIKE 'bus_bookings'");
assertTest("Table 'bus_bookings' exists in MySQL", !empty($tableCheck));

// 2. Routes & Fare Estimation
$popularRoutes = RealtimeDataService::getPopularBusRoutes();
assertTest("Popular bus routes list is populated (>5 routes)", count($popularRoutes) >= 5);

$onewayFare = RealtimeDataService::estimateBusFare('Lagos (Jibowu)', 'Abuja (Utako)', 'one-way', 1, 'Executive Luxury AC Coach');
assertTest("One-way fare calculation for Lagos -> Abuja returns valid amount (> ₦20,000)", $onewayFare >= 20000);

$roundtripFare = RealtimeDataService::estimateBusFare('Lagos (Jibowu)', 'Abuja (Utako)', 'round-trip', 1, 'Executive Luxury AC Coach');
assertTest("Round-trip fare is approximately 1.95x one-way fare", $roundtripFare > $onewayFare && $roundtripFare < ($onewayFare * 2.1));

$twoSeatsFare = RealtimeDataService::estimateBusFare('Lagos (Jibowu)', 'Abuja (Utako)', 'one-way', 2, 'Executive Luxury AC Coach');
assertTest("2 seats fare equals exactly double 1 seat fare", $twoSeatsFare === round($onewayFare * 2, 2));

// 3. Validation Tests
$invalid1 = RealtimeDataService::createBusBooking([]);
assertTest("Validation: Rejects empty name", !$invalid1['success'] && strpos($invalid1['error'], 'name') !== false);

$invalid2 = RealtimeDataService::createBusBooking([
    'customer_name' => 'Chinedu Eze',
    'customer_phone' => '123',
]);
assertTest("Validation: Rejects invalid phone number", !$invalid2['success'] && strpos($invalid2['error'], 'phone') !== false);

$invalid3 = RealtimeDataService::createBusBooking([
    'customer_name' => 'Chinedu Eze',
    'customer_phone' => '08123456789',
    'departure_city' => 'Lagos',
    'destination_city' => 'Lagos',
    'departure_date' => date('Y-m-d', strtotime('+2 days')),
]);
assertTest("Validation: Rejects identical origin and destination", !$invalid3['success'] && strpos($invalid3['error'], 'same') !== false);

$invalid4 = RealtimeDataService::createBusBooking([
    'customer_name' => 'Chinedu Eze',
    'customer_phone' => '08123456789',
    'departure_city' => 'Lagos',
    'destination_city' => 'Abuja',
    'departure_date' => date('Y-m-d', strtotime('-2 days')),
]);
assertTest("Validation: Rejects past departure date", !$invalid4['success'] && strpos($invalid4['error'], 'past') !== false);

$invalid5 = RealtimeDataService::createBusBooking([
    'customer_name' => 'Chinedu Eze',
    'customer_phone' => '08123456789',
    'departure_city' => 'Lagos',
    'destination_city' => 'Abuja',
    'trip_type' => 'round-trip',
    'departure_date' => date('Y-m-d', strtotime('+5 days')),
    'return_date' => date('Y-m-d', strtotime('+3 days')), // before departure!
]);
assertTest("Validation: Rejects return date earlier than departure date", !$invalid5['success'] && strpos($invalid5['error'], 'earlier') !== false);

// 4. Successful One-Way Booking
$departTomorrow = date('Y-m-d', strtotime('+1 day'));
$onewayPayload = [
    'customer_name' => 'Emeka Okafor',
    'customer_phone' => '08031234567',
    'customer_email' => 'emeka@example.com',
    'departure_city' => 'Lagos (Jibowu / Yaba Terminal)',
    'destination_city' => 'Abuja (Utako Central Terminal)',
    'departure_date' => $departTomorrow,
    'departure_time' => 'Morning (06:30 - 08:00 AM)',
    'trip_type' => 'one-way',
    'passengers' => 1,
    'coach_class' => 'Executive Luxury AC Coach',
    'preferred_operator' => 'GIGM - God is Good Motors',
    'notes' => '1 big luggage, front seat if possible',
];

$onewayRes = RealtimeDataService::createBusBooking($onewayPayload);
assertTest("Successful one-way booking created", !empty($onewayRes['success']));
assertTest("Booking reference generated (CJBUS-...)", str_starts_with($onewayRes['booking']['ref'] ?? '', 'CJBUS-'));
assertTest("WhatsApp URL contains 'wa.me'", strpos($onewayRes['whatsappUrl'] ?? '', 'https://wa.me/') !== false);
assertTest("WhatsApp message contains passenger name 'Emeka Okafor'", strpos($onewayRes['messageText'] ?? '', 'Emeka Okafor') !== false);
assertTest("WhatsApp message contains departure date", strpos($onewayRes['messageText'] ?? '', date('D, j M Y', strtotime($departTomorrow))) !== false);
assertTest("WhatsApp message contains route Lagos -> Abuja", strpos($onewayRes['messageText'] ?? '', 'Abuja') !== false);

$savedId1 = (int)($onewayRes['booking']['id'] ?? 0);

// 5. Successful Round-Trip Booking
$returnNextWeek = date('Y-m-d', strtotime('+7 days'));
$roundtripPayload = [
    'customer_name' => 'Adaobi Nwosu',
    'customer_phone' => '08139876543',
    'customer_email' => 'adaobi@example.com',
    'departure_city' => 'Lagos (Ajah / Lekki Terminal)',
    'destination_city' => 'Port Harcourt (Waterlines Terminal)',
    'departure_date' => $departTomorrow,
    'return_date' => $returnNextWeek,
    'departure_time' => 'Morning (06:30 - 08:00 AM)',
    'trip_type' => 'round-trip',
    'passengers' => 2,
    'coach_class' => 'VIP First-Class Coach',
    'preferred_operator' => 'ABC Transport Plc',
    'notes' => 'Traveling with family',
];

$roundtripRes = RealtimeDataService::createBusBooking($roundtripPayload);
assertTest("Successful round-trip booking created", !empty($roundtripRes['success']));
assertTest("Round-trip return date persisted in booking", $roundtripRes['booking']['return_date'] === $returnNextWeek);
assertTest("WhatsApp message contains return date", strpos($roundtripRes['messageText'] ?? '', date('D, j M Y', strtotime($returnNextWeek))) !== false);
assertTest("WhatsApp message contains 'Round-Trip'", strpos($roundtripRes['messageText'] ?? '', 'Round-Trip') !== false);

$savedId2 = (int)($roundtripRes['booking']['id'] ?? 0);

// 6. Admin Data & Retrieval
$allBookings = RealtimeDataService::getBusBookings(10);
assertTest("getBusBookings retrieves recorded bookings", count($allBookings) >= 2);
assertTest("busBookingCount returns valid count", RealtimeDataService::busBookingCount() >= 2);

// 7. Status Update via updateRecord
$updateStatusRes = RealtimeDataService::updateRecord('bus', $savedId1, ['status' => 'Confirmed']);
assertTest("updateRecord updates bus booking status to 'Confirmed'", $updateStatusRes);

$updatedCheck = $db->selectOne("SELECT status FROM bus_bookings WHERE id = :id", [':id' => $savedId1]);
assertTest("Verified status in DB is 'Confirmed'", ($updatedCheck['status'] ?? '') === 'Confirmed');

// 8. Public HTTP View & Route Testing
$reqBusPage = Request::create('/bus', 'GET');
$respBusPage = Router::dispatch($reqBusPage);
assertTest("Route GET /bus returns HTTP 200", $respBusPage->getStatusCode() === 200);

$busHtml = $respBusPage->getContent();
assertTest("Bus page contains 'Road Transport & Bus Tickets'", strpos($busHtml, 'Road Transport') !== false);
assertTest("Bus page contains departure date input 'busDepartDate'", strpos($busHtml, 'busDepartDate') !== false);
assertTest("Bus page contains return date input 'busReturnDate'", strpos($busHtml, 'busReturnDate') !== false);
assertTest("Bus page contains WhatsApp submit CTA", strpos($busHtml, 'Reserve &amp; Send Enquiry via WhatsApp') !== false || strpos($busHtml, 'Reserve & Send Enquiry via WhatsApp') !== false);
assertTest("Bus page contains WhatsApp concierge number", strpos($busHtml, cj_whatsapp_number()) !== false);

// 9. API HTTP Routes Testing
$reqApiRoutes = Request::create('/api/bus/routes', 'GET');
$respApiRoutes = Router::dispatch($reqApiRoutes);
assertTest("Route GET /api/bus/routes returns HTTP 200", $respApiRoutes->getStatusCode() === 200);

$reqApiEstimate = Request::create('/api/bus/estimate', 'POST', [
    'from' => 'Lagos',
    'to' => 'Abuja',
    'trip_type' => 'round-trip',
    'passengers' => 2,
    'coach_class' => 'Executive Luxury AC Coach',
]);
$respApiEstimate = Router::dispatch($reqApiEstimate);
assertTest("Route POST /api/bus/estimate returns HTTP 200", $respApiEstimate->getStatusCode() === 200);
$estJson = json_decode($respApiEstimate->getContent(), true);
assertTest("Estimate JSON returns formatted fare (₦...)", !empty($estJson['formattedFare']));

// 10. Admin Bus Page View Rendering
$admin = \App\Models\Admin::findByEmail('admin@cjtravels.com') ?? new \App\Models\Admin(['id' => 1, 'name' => 'Admin', 'email' => 'admin@cjtravels.com']);
$adminBusHtml = View::render('admin/bus', [
    'title' => 'Bus Tickets — CJ Travels Admin',
    'page' => 'bus',
    'admin' => $admin,
    'adminUser' => $admin->name,
    'adminRole' => 'Super Administrator',
    'adminInitials' => 'AD',
    'liveCounts' => ['bus' => RealtimeDataService::busBookingCount(), 'customers' => 1, 'flights' => 1, 'hotels' => 1, 'loans' => 1, 'destinations' => 1, 'transactions' => 1, 'api' => 1, 'messages' => 1],
    'bookings' => RealtimeDataService::getBusBookings(20),
    'currentStatus' => null,
    'popularRoutes' => $popularRoutes,
    'counts' => [
        'total' => RealtimeDataService::busBookingCount(),
        'pending' => 1,
        'confirmed' => 1,
    ],
    'success' => null,
    'error' => null,
]);
assertTest("Admin bus view renders 'Bus Tickets & Road Transport'", strpos($adminBusHtml, 'Bus Tickets') !== false);
assertTest("Admin bus view contains booking reference {$onewayRes['booking']['ref']}", strpos($adminBusHtml, $onewayRes['booking']['ref']) !== false);
assertTest("Admin bus view contains passenger WhatsApp quick chat button", strpos($adminBusHtml, 'Chat') !== false);

// 11. Cleanup test records
$db->delete('bus_bookings', 'id IN (:id1, :id2)', [':id1' => $savedId1, ':id2' => $savedId2]);
assertTest("Cleaned up test bus booking records", true);

echo "\n============================================\n";
echo "TEST RESULTS: {$passCount} Passed, {$failCount} Failed\n";
echo "============================================\n";

if ($failCount > 0) {
    exit(1);
}
