<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;
use App\Services\RealtimeDataService;

echo "=== WORLD-CLASS PROMO BANNER & REGISTRATION TESTS ===\n\n";

$db = Database::getInstance();

// 1. Validation testing
echo "1. Testing promo registration validation...\n";
$bad = RealtimeDataService::registerPromo([
    'fullName' => '',
    'email' => 'bad-email'
]);

if ($bad['success'] !== false) {
    echo "FAIL: Expected validation failure for empty name and bad email.\n";
    exit(1);
}
echo "  [OK] Successfully rejected invalid registration: {$bad['error']}\n\n";

// 2. Successful promo registration
echo "2. Testing successful promo registration...\n";
$testEmail = 'promo.tester.' . time() . '@example.com';
$testName = 'Zainab Danjuma';
$testDest = 'Maldives & Islands';

$res = RealtimeDataService::registerPromo([
    'fullName' => $testName,
    'email' => $testEmail,
    'phone' => '+2348098765432',
    'destination' => $testDest,
    'promoCode' => 'CJLUXE50K'
]);

if (empty($res['success']) || empty($res['promo'])) {
    echo "FAIL: Promo registration failed: " . ($res['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

$promo = $res['promo'];
$promoRef = $promo['ref'];
$promoId = (int)$promo['id'];

echo "  [OK] Promo registered! Ref: {$promoRef}, Code: {$promo['code']}\n";
echo "  [OK] Discount amount: {$promo['discount']}\n";
echo "  [OK] Expiration date: {$promo['validUntil']}\n";

// Verify MySQL table
$row = $db->selectOne("SELECT * FROM promo_registrations WHERE id = :id LIMIT 1", [':id' => $promoId]);
if (!$row) {
    echo "FAIL: Promo record not found in MySQL promo_registrations table.\n";
    exit(1);
}
if ($row['email'] !== strtolower($testEmail) || $row['promo_code'] !== 'CJLUXE50K') {
    echo "FAIL: Database promo record fields mismatch.\n";
    exit(1);
}
echo "  [OK] Verified MySQL persistence in promo_registrations table.\n\n";

// 3. HTTP Endpoint & Markup verification
echo "3. Testing HTTP API and Frontend markup integration...\n";

// Check home page returns 200 and includes promo assets
$homeHtml = file_get_contents('http://localhost/TravelBooking/');
if (strpos($homeHtml, 'promo-banner.css') === false) {
    echo "FAIL: Home page does not link promo-banner.css\n";
    exit(1);
}
if (strpos($homeHtml, 'promo-banner.js') === false) {
    echo "FAIL: Home page does not include promo-banner.js\n";
    exit(1);
}
if (strpos($homeHtml, 'cjPromoContainer') === false) {
    echo "FAIL: Home page does not contain #cjPromoContainer markup\n";
    exit(1);
}
if (strpos($homeHtml, 'CJLUXE50K') === false) {
    echo "FAIL: Home page does not contain promo code CJLUXE50K\n";
    exit(1);
}
echo "  [OK] Home page successfully serves promo CSS, JS, and modal markup.\n";

// Check register page with promo query parameter
$regHtml = file_get_contents('http://localhost/TravelBooking/register?promo=CJLUXE50K');
if (strpos($regHtml, 'Promo Code Active: CJLUXE50K') === false) {
    echo "FAIL: Register page does not reflect active promo parameter.\n";
    exit(1);
}
echo "  [OK] Register page reflects active promo parameter: 'Promo Code Active: CJLUXE50K'.\n\n";

// 4. Clean up test records
echo "4. Cleaning up test record...\n";
$db->delete('promo_registrations', 'id = :id', [':id' => $promoId]);
echo "  [OK] Cleaned test promo registration.\n\n";

echo "=== ALL PROMO BANNER TESTS PASSED (100%) ===\n";

