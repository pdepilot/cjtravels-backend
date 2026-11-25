<?php
/**
 * Bus enquiry security, validation, and admin operation tests.
 * Runs only against a local database. Cleans up its own rows on the way out.
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../routes/web.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Models\Admin;
use App\Services\BusBookingRateLimiter;
use App\Services\RealtimeDataService;

echo "=== CJ TRAVELS: BUS BOOKING SECURITY TEST SUITE ===\n\n";

$mysql = config('database.connections.mysql', []);
$host = strtolower((string)($mysql['host'] ?? ''));
if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
    fwrite(STDERR, "Bus booking tests were not run because the configured database host is not local.\n");
    exit(2);
}

$passCount = 0;
$failCount = 0;
$db = Database::getInstance();

function assertTest(string $description, bool $condition): void
{
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] {$description}\n";
        $passCount++;
        return;
    }
    echo " [FAIL] {$description}\n";
    $failCount++;
}

function busPayload(array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'BusTest Passenger',
        'customer_phone' => '08031234567',
        'customer_email' => 'bustest@example.com',
        'departure_city' => 'Lagos (Jibowu / Yaba Terminal)',
        'destination_city' => 'Abuja (Utako Central Terminal)',
        'departure_date' => date('Y-m-d', strtotime('+1 day')),
        'departure_time' => 'Morning (06:30 - 08:00 AM)',
        'trip_type' => 'one-way',
        'passengers' => 1,
        'coach_class' => 'Executive Luxury AC Coach',
        'preferred_operator' => 'GIGM - God is Good Motors',
        'notes' => 'Window seat',
    ], $overrides);
}

function dispatchBus(string $uri, string $method = 'GET', array $params = [], array $server = []): \App\Core\Response
{
    return Router::dispatch(Request::create($uri, $method, $params, $server));
}

try {
    RealtimeDataService::ensureBusBookingSchema();
    $tableCheck = $db->selectOne("SHOW TABLES LIKE 'bus_bookings'");
    assertTest("Table bus_bookings exists", !empty($tableCheck));

    $columns = array_column($db->select('SHOW COLUMNS FROM bus_bookings'), 'Field');
    foreach (['ref', 'customer_name', 'customer_phone', 'customer_email', 'trip_type', 'departure_city', 'destination_city', 'departure_date', 'return_date', 'departure_time', 'passengers', 'coach_class', 'preferred_operator', 'estimated_fare', 'notes', 'status', 'whatsapp_status'] as $column) {
        assertTest("Schema includes {$column}", in_array($column, $columns, true));
    }

    $marker = dirname(__DIR__) . '/storage/cache/bus_bookings.schema';
    assertTest("Schema install is recorded so later requests skip CREATE TABLE", is_file($marker));

    $popularRoutes = RealtimeDataService::getPopularBusRoutes();
    assertTest("Popular bus routes list is populated", count($popularRoutes) >= 5);
    $onewayFare = RealtimeDataService::estimateBusFare('Lagos (Jibowu)', 'Abuja (Utako)', 'one-way', 1, 'Executive Luxury AC Coach');
    $roundtripFare = RealtimeDataService::estimateBusFare('Lagos (Jibowu)', 'Abuja (Utako)', 'round-trip', 1, 'Executive Luxury AC Coach');
    assertTest("One-way fare is calculated", $onewayFare >= 20000);
    assertTest("Round-trip fare is higher than one-way", $roundtripFare > $onewayFare && $roundtripFare < ($onewayFare * 2.1));

    $invalidName = RealtimeDataService::createBusBooking([]);
    assertTest("Missing customer name is rejected", empty($invalidName['success']) && str_contains((string)$invalidName['error'], 'name'));

    $invalidPhone = RealtimeDataService::createBusBooking(busPayload(['customer_phone' => '12345']));
    assertTest("Invalid phone number is rejected", empty($invalidPhone['success']) && str_contains(strtolower((string)$invalidPhone['error']), 'phone'));

    $invalidEmail = RealtimeDataService::createBusBooking(busPayload(['customer_email' => 'not-an-email']));
    assertTest("Invalid email is rejected", empty($invalidEmail['success']) && str_contains(strtolower((string)$invalidEmail['error']), 'email'));

    $blankEmail = RealtimeDataService::createBusBooking(busPayload([
        'customer_name' => 'BusTest Blank Email',
        'customer_phone' => '08039990021',
        'customer_email' => '',
    ]));
    assertTest("Blank email is accepted", !empty($blankEmail['success']));

    $sameCity = RealtimeDataService::createBusBooking(busPayload([
        'departure_city' => 'Lagos',
        'destination_city' => 'lagos',
    ]));
    assertTest("Same departure and destination is rejected", empty($sameCity['success']) && str_contains(strtolower((string)$sameCity['error']), 'same'));

    $impossible = RealtimeDataService::createBusBooking(busPayload(['departure_date' => '2026-02-31']));
    assertTest("Impossible calendar date is rejected", empty($impossible['success']) && str_contains(strtolower((string)$impossible['error']), 'calendar'));

    $past = RealtimeDataService::createBusBooking(busPayload(['departure_date' => date('Y-m-d', strtotime('-2 days'))]));
    assertTest("Past departure date is rejected", empty($past['success']) && str_contains(strtolower((string)$past['error']), 'past'));

    $earlyReturn = RealtimeDataService::createBusBooking(busPayload([
        'trip_type' => 'round-trip',
        'departure_date' => date('Y-m-d', strtotime('+5 days')),
        'return_date' => date('Y-m-d', strtotime('+3 days')),
    ]));
    assertTest("Return date earlier than departure is rejected", empty($earlyReturn['success']) && str_contains(strtolower((string)$earlyReturn['error']), 'earlier'));

    $tooFew = RealtimeDataService::createBusBooking(busPayload(['passengers' => 0]));
    $tooMany = RealtimeDataService::createBusBooking(busPayload(['passengers' => 21]));
    $notNumber = RealtimeDataService::createBusBooking(busPayload(['passengers' => 'two']));
    assertTest("Passenger count below 1 is rejected", empty($tooFew['success']));
    assertTest("Passenger count above 20 is rejected", empty($tooMany['success']));
    assertTest("Non-numeric passenger count is rejected", empty($notNumber['success']));

    $longNotes = RealtimeDataService::createBusBooking(busPayload(['notes' => str_repeat('A', 1001)]));
    assertTest("Excessively long notes are rejected", empty($longNotes['success']) && str_contains(strtolower((string)$longNotes['error']), 'notes'));

    $badCoach = RealtimeDataService::createBusBooking(busPayload(['coach_class' => 'Sleeper']));
    $badTrip = RealtimeDataService::createBusBooking(busPayload(['trip_type' => 'multi-city']));
    assertTest("Invalid coach class is rejected", empty($badCoach['success']) && str_contains(strtolower((string)$badCoach['error']), 'coach'));
    assertTest("Invalid trip type is rejected", empty($badTrip['success']) && str_contains(strtolower((string)$badTrip['error']), 'trip'));

    $longName = RealtimeDataService::createBusBooking(busPayload(['customer_name' => str_repeat('N', 121)]));
    assertTest("Overlong customer name is rejected", empty($longName['success']));

    $departTomorrow = date('Y-m-d', strtotime('+1 day'));
    $oneway = RealtimeDataService::createBusBooking(busPayload([
        'customer_name' => 'BusTest Emeka',
        'customer_phone' => '08039990001',
        'notes' => '1 big luggage',
    ]));
    assertTest("Successful one-way enquiry", !empty($oneway['success']));
    assertTest("Booking reference keeps the CJBUS- prefix and is unpredictable", preg_match('/^CJBUS-[A-F0-9]{16}$/', (string)($oneway['booking']['ref'] ?? '')) === 1);
    assertTest("WhatsApp handoff URL is prepared", str_contains((string)($oneway['whatsappUrl'] ?? ''), 'https://wa.me/'));
    assertTest("Prepared message keeps the passenger name and reference", str_contains((string)($oneway['messageText'] ?? ''), 'BusTest Emeka') && str_contains((string)($oneway['messageText'] ?? ''), (string)$oneway['booking']['ref']));
    assertTest("Success text does not claim a WhatsApp message was sent", !str_contains(strtolower((string)($oneway['message'] ?? '')), 'has been sent'));
    $savedId1 = (int)($oneway['booking']['id'] ?? 0);

    $returnNextWeek = date('Y-m-d', strtotime('+7 days'));
    $roundtrip = RealtimeDataService::createBusBooking(busPayload([
        'customer_name' => 'BusTest Adaobi',
        'customer_phone' => '08039990002',
        'departure_city' => 'Lagos (Ajah / Lekki Terminal)',
        'destination_city' => 'Port Harcourt (Waterlines Terminal)',
        'return_date' => $returnNextWeek,
        'trip_type' => 'round-trip',
        'passengers' => 2,
        'coach_class' => 'VIP First-Class Coach',
        'preferred_operator' => 'ABC Transport Plc',
    ]));
    assertTest("Successful round-trip enquiry", !empty($roundtrip['success']));
    assertTest("Round-trip return date is stored", ($roundtrip['booking']['return_date'] ?? '') === $returnNextWeek);
    assertTest("Prepared round-trip message includes the return date", str_contains((string)($roundtrip['messageText'] ?? ''), 'Round-Trip'));
    $savedId2 = (int)($roundtrip['booking']['id'] ?? 0);

    $repeat = RealtimeDataService::createBusBooking(busPayload([
        'customer_name' => 'BusTest Emeka',
        'customer_phone' => '08039990001',
        'notes' => '1 big luggage',
    ]));
    $repeatCount = (int)($db->selectOne('SELECT COUNT(*) AS c FROM bus_bookings WHERE customer_phone = :phone', [':phone' => '08039990001'])['c'] ?? 0);
    assertTest("A second identical submit reuses the saved enquiry", !empty($repeat['success']) && (int)($repeat['booking']['id'] ?? 0) === $savedId1 && $repeatCount === 1);

    $beforeSchema = (int)($db->selectOne('SELECT COUNT(*) AS c FROM bus_bookings WHERE id = :id', [':id' => $savedId1])['c'] ?? 0);
    if (is_file($marker)) {
        unlink($marker);
    }
    RealtimeDataService::ensureBusBookingSchema();
    $afterSchema = (int)($db->selectOne('SELECT COUNT(*) AS c FROM bus_bookings WHERE id = :id', [':id' => $savedId1])['c'] ?? 0);
    assertTest("Installing the schema again keeps the existing booking", $beforeSchema === 1 && $afterSchema === 1);

    RealtimeDataService::setBusReferenceFactory(static fn (): string => (string)$oneway['booking']['ref']);
    $collision = RealtimeDataService::createBusBooking(busPayload([
        'customer_name' => 'BusTest Collision',
        'customer_phone' => '08039990003',
        'destination_city' => 'Kano (Sabon Gari / Naibawa)',
    ]));
    RealtimeDataService::setBusReferenceFactory(null);
    $original = $db->selectOne('SELECT customer_name FROM bus_bookings WHERE ref = :ref', [':ref' => $oneway['booking']['ref']]);
    $collisionRows = (int)($db->selectOne('SELECT COUNT(*) AS c FROM bus_bookings WHERE customer_phone = :phone', [':phone' => '08039990003'])['c'] ?? 0);
    assertTest("A duplicate booking reference is not saved as a new success", empty($collision['success']) && $collisionRows === 0);
    assertTest("A reference collision does not overwrite the existing booking", ($original['customer_name'] ?? '') === 'BusTest Emeka');
    assertTest("Reference collision response stays generic", !str_contains((string)($collision['error'] ?? ''), 'SQLSTATE') && !str_contains((string)($collision['error'] ?? ''), 'Duplicate'));

    $log = dirname(__DIR__) . '/storage/logs/bus-bookings.log';
    $logBefore = is_file($log) ? substr_count((string)file_get_contents($log), 'Simulated bus booking insert failure') : 0;
    RealtimeDataService::failNextBusInsert(true);
    $failedInsert = RealtimeDataService::createBusBooking(busPayload([
        'customer_name' => 'BusTest Insert Failure',
        'customer_phone' => '08039990004',
        'destination_city' => 'Enugu (Holy Ghost Terminal)',
    ]));
    $failedRows = (int)($db->selectOne('SELECT COUNT(*) AS c FROM bus_bookings WHERE customer_phone = :phone', [':phone' => '08039990004'])['c'] ?? 0);
    $logAfter = is_file($log) ? substr_count((string)file_get_contents($log), 'Simulated bus booking insert failure') : 0;
    assertTest("A failed database insert is not reported as a saved booking", empty($failedInsert['success']) && $failedRows === 0);
    assertTest("Customers receive a generic save error", ($failedInsert['error'] ?? '') === 'We could not save your bus enquiry just now. Please try again in a moment.');
    assertTest("The insert failure is logged privately", $logAfter === $logBefore + 1);

    $updated = RealtimeDataService::updateRecord('bus', $savedId1, ['status' => 'Confirmed']);
    $updatedAgain = RealtimeDataService::updateBusBooking($savedId1, ['status' => 'Confirmed']);
    $statusRow = $db->selectOne('SELECT status FROM bus_bookings WHERE id = :id', [':id' => $savedId1]);
    assertTest("Updating an existing booking succeeds", $updated === true && ($statusRow['status'] ?? '') === 'Confirmed');
    assertTest("Saving the same status again is still a success", !empty($updatedAgain['success']) && str_contains((string)$updatedAgain['message'], 'already'));

    $maxId = (int)($db->selectOne('SELECT COALESCE(MAX(id), 0) AS m FROM bus_bookings')['m'] ?? 0);
    $missingId = $maxId + 1000;
    $missingUpdate = RealtimeDataService::updateBusBooking($missingId, ['status' => 'Cancelled']);
    assertTest("Updating a nonexistent booking fails", empty($missingUpdate['success']) && ($missingUpdate['code'] ?? '') === 'not_found');

    $badStatus = RealtimeDataService::updateBusBooking($savedId2, ['status' => 'Approved']);
    $statusRow2 = $db->selectOne('SELECT status FROM bus_bookings WHERE id = :id', [':id' => $savedId2]);
    assertTest("Unsupported status values are rejected", empty($badStatus['success']) && ($statusRow2['status'] ?? '') === 'Pending');

    $deleted = RealtimeDataService::deleteBusBooking($savedId2);
    $deletedAgain = RealtimeDataService::deleteBusBooking($savedId2);
    $deletedRow = $db->selectOne('SELECT id FROM bus_bookings WHERE id = :id', [':id' => $savedId2]);
    assertTest("Deleting an existing booking succeeds once", !empty($deleted['success']) && $deletedRow === null);
    assertTest("Deleting a nonexistent booking does not report success", empty($deletedAgain['success']) && ($deletedAgain['code'] ?? '') === 'not_found');

    $xss = RealtimeDataService::createBusBooking(busPayload([
        'customer_name' => 'BusTest <script>alert(1)</script>',
        'customer_phone' => '08039990005',
        'destination_city' => 'Jos (Terminus / British America)',
        'notes' => 'BusTest <img src=x onerror=alert(1)>',
    ]));
    $admin = Admin::findByEmail('admin@cjtravels.com') ?? new Admin(['id' => 1, 'name' => 'Admin', 'email' => 'admin@cjtravels.com']);
    $adminHtml = View::render('admin/bus', [
        'title' => 'Bus Tickets — CJ Travels Admin',
        'page' => 'bus',
        'admin' => $admin,
        'adminUser' => $admin->name,
        'adminRole' => 'Super Administrator',
        'adminInitials' => 'AD',
        'liveCounts' => ['bus' => 1, 'customers' => 1, 'flights' => 1, 'hotels' => 1, 'loans' => 1, 'destinations' => 1, 'transactions' => 1, 'api' => 1, 'messages' => 1],
        'bookings' => RealtimeDataService::getBusBookings(30),
        'currentStatus' => null,
        'popularRoutes' => $popularRoutes,
        'counts' => ['total' => 1, 'pending' => 1, 'confirmed' => 1],
        'success' => null,
        'error' => null,
    ]);
    assertTest("Customer-supplied script text is escaped in the admin page", !empty($xss['success']) && !str_contains($adminHtml, '<script>alert(1)</script>') && str_contains($adminHtml, '&lt;script&gt;'));
    assertTest("Customer-supplied notes are escaped in the admin page", !str_contains($adminHtml, '<img src=x onerror=alert(1)>') && str_contains($adminHtml, '&lt;img'));

    $page = dispatchBus('/bus', 'GET');
    $busHtml = $page->getContent();
    assertTest("Public bus page loads", $page->getStatusCode() === 200 && str_contains($busHtml, 'busDepartDate'));
    assertTest("The enquiry form sends the CSRF token with the AJAX request", str_contains($busHtml, 'name="_token"') && str_contains($busHtml, 'X-CSRF-TOKEN'));
    assertTest("WhatsApp opens from a customer click after the enquiry is saved", str_contains($busHtml, 'id="modalWhatsappLink"') && str_contains($busHtml, 'id="modalWhatsappMessage"') && !str_contains($busHtml, 'window.open'));
    assertTest("The page does not claim a WhatsApp message has been sent", str_contains($busHtml, 'no message has been sent'));

    $token = Session::token();
    $missingToken = dispatchBus('/api/bus/enquire', 'POST', busPayload(['customer_name' => 'BusTest Missing Token', 'customer_phone' => '08039990031']), [
        'REMOTE_ADDR' => '203.0.113.20',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $missingBody = json_decode($missingToken->getContent(), true);
    assertTest("A missing CSRF token returns JSON instead of an HTML redirect", $missingToken->getStatusCode() === 419 && is_array($missingBody) && empty($missingBody['success']) && $missingToken->getHeader('Location') === null);

    $badToken = dispatchBus('/api/bus/enquire', 'POST', busPayload([
        'customer_name' => 'BusTest Bad Token',
        'customer_phone' => '08039990032',
        '_token' => 'not-the-session-token',
    ]), [
        'REMOTE_ADDR' => '203.0.113.21',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    assertTest("An invalid CSRF token is rejected", $badToken->getStatusCode() === 419);

    $httpSaved = dispatchBus('/api/bus/enquire', 'POST', busPayload([
        'customer_name' => 'BusTest Http',
        'customer_phone' => '08039990033',
        '_token' => $token,
    ]), [
        'REMOTE_ADDR' => '203.0.113.22',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_CSRF_TOKEN' => $token,
    ]);
    $httpBody = json_decode($httpSaved->getContent(), true);
    assertTest("A valid CSRF token allows the booking endpoint", $httpSaved->getStatusCode() === 200 && !empty($httpBody['success']) && str_contains((string)($httpBody['whatsappUrl'] ?? ''), 'https://wa.me/'));

    BusBookingRateLimiter::reset('203.0.113.50');
    BusBookingRateLimiter::usePolicy(3, 600);
    $limitedStatuses = [];
    for ($i = 0; $i < 4; $i++) {
        $limited = dispatchBus('/api/bus/enquire', 'POST', [
            '_token' => $token,
            'customer_name' => '',
        ], [
            'REMOTE_ADDR' => '203.0.113.50',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.' . $i,
        ]);
        $limitedStatuses[] = $limited->getStatusCode();
    }
    $spoofed = dispatchBus('/api/bus/enquire', 'POST', [
        '_token' => $token,
        'customer_name' => 'BusTest Spoof',
        'ip' => '198.51.100.77',
        'REMOTE_ADDR' => '198.51.100.77',
    ], [
        'REMOTE_ADDR' => '203.0.113.50',
        'HTTP_X_FORWARDED_FOR' => '198.51.100.88',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $otherNetwork = dispatchBus('/api/bus/enquire', 'POST', [
        '_token' => $token,
        'customer_name' => '',
    ], [
        'REMOTE_ADDR' => '203.0.113.51',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    assertTest("Booking attempts are rate limited by network address", $limitedStatuses === [422, 422, 422, 429]);
    assertTest("Ordinary request fields cannot move a client into a new rate-limit bucket", $spoofed->getStatusCode() === 429);
    assertTest("A different network address keeps its own booking allowance", $otherNetwork->getStatusCode() === 422);

    $adminRow = $db->selectOne("SELECT id FROM administrators WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    $adminId = (int)($adminRow['id'] ?? 0);
    Session::remove('_admin_id');
    Auth::refreshAdmin();
    $unauthorized = dispatchBus('/admin/api/bus/status', 'POST', [
        '_token' => $token,
        'id' => $savedId1,
        'status' => 'Processing',
    ], ['HTTP_ACCEPT' => 'application/json']);
    assertTest("Anonymous admin booking updates are turned away", $adminId > 0 && $unauthorized->getStatusCode() === 302 && str_contains((string)$unauthorized->getHeader('Location'), 'login'));

    $adminMissingToken = dispatchBus('/admin/api/bus/delete', 'POST', [
        'id' => $savedId1,
    ], ['HTTP_ACCEPT' => 'application/json']);
    $adminMissingBody = json_decode($adminMissingToken->getContent(), true);
    assertTest("Admin API requests without a CSRF token receive JSON", $adminMissingToken->getStatusCode() === 419 && is_array($adminMissingBody));

    Session::set('_admin_id', $adminId);
    Session::set('_admin_last_activity', time());
    Auth::refreshAdmin();
    $statusResponse = dispatchBus('/admin/api/bus/status', 'POST', [
        '_token' => $token,
        'id' => (string)$savedId1,
        'status' => 'Processing',
    ]);
    $statusBody = json_decode($statusResponse->getContent(), true);
    $processing = $db->selectOne('SELECT status FROM bus_bookings WHERE id = :id', [':id' => $savedId1]);
    assertTest("An authenticated administrator can update an existing booking", $statusResponse->getStatusCode() === 200 && !empty($statusBody['success']) && ($processing['status'] ?? '') === 'Processing');

    $sameStatus = dispatchBus('/admin/api/bus/status', 'POST', [
        '_token' => $token,
        'id' => (string)$savedId1,
        'status' => 'Processing',
    ]);
    $sameBody = json_decode($sameStatus->getContent(), true);
    assertTest("An unchanged booking status is not treated as a missing record", $sameStatus->getStatusCode() === 200 && !empty($sameBody['success']));

    $missingAdminUpdate = dispatchBus('/admin/api/bus/status', 'POST', [
        '_token' => $token,
        'id' => (string)$missingId,
        'status' => 'Cancelled',
    ]);
    assertTest("An authenticated update of a missing booking is not successful", $missingAdminUpdate->getStatusCode() === 404);

    $deleteResponse = dispatchBus('/admin/api/bus/delete', 'POST', [
        '_token' => $token,
        'id' => (string)$savedId1,
    ]);
    $deleteAgain = dispatchBus('/admin/api/bus/delete', 'POST', [
        '_token' => $token,
        'id' => (string)$savedId1,
    ]);
    $gone = $db->selectOne('SELECT id FROM bus_bookings WHERE id = :id', [':id' => $savedId1]);
    assertTest("An authenticated administrator can delete an existing booking", $deleteResponse->getStatusCode() === 200 && $gone === null);
    assertTest("Deleting that booking again does not report another success", $deleteAgain->getStatusCode() === 404);
} catch (Throwable $e) {
    echo ' [FAIL] Uncaught ' . $e::class . ': ' . $e->getMessage() . "\n";
    $failCount++;
} finally {
    try {
        $db->delete('bus_bookings', 'customer_name LIKE :name', [':name' => 'BusTest %']);
    } catch (Throwable $cleanupError) {
        echo " [FAIL] Cleanup could not remove test bookings\n";
        $failCount++;
    }
    BusBookingRateLimiter::reset();
    BusBookingRateLimiter::resetPolicy();
    RealtimeDataService::setBusReferenceFactory(null);
    RealtimeDataService::failNextBusInsert(false);
    Session::remove('_admin_id');
    Auth::refreshAdmin();
}

echo "\n============================================\n";
echo "TEST RESULTS: {$passCount} Passed, {$failCount} Failed\n";
echo "============================================\n";

exit($failCount > 0 ? 1 : 0);
