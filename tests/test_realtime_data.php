<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;
use App\Services\RealtimeDataService;

echo "=== REAL-TIME DATA ARCHITECTURE VERIFICATION ===\n\n";

$db = Database::getInstance();

// 1. Verify RealtimeDataService extracts real data from MySQL
echo "1. Checking RealtimeDataService::getAdminData()...\n";
$data = RealtimeDataService::getAdminData();

$expectedCounts = [
    'customers' => 10,
    'flights' => 8,
    'hotels' => 6,
    'loans' => 4,
    'transactions' => 10,
    'destinations' => 9,
];

foreach ($expectedCounts as $key => $minCount) {
    $actualCount = count($data[$key]);
    if ($actualCount < $minCount) {
        echo "FAIL: Expected at least {$minCount} {$key}, got {$actualCount}\n";
        exit(1);
    }
    echo "  [OK] {$key}: {$actualCount} live records from MySQL\n";
}
echo "PASS: All collections successfully pulled from MySQL database.\n\n";

// 2. Verify record relational links
echo "2. Checking relational joins...\n";
if (empty($data['flights'][0]['customerName']) || $data['flights'][0]['customerName'] === 'Unknown') {
    echo "FAIL: Flight customerName join failed.\n";
    exit(1);
}
echo "  [OK] Flight {$data['flights'][0]['ref']} linked to customer: {$data['flights'][0]['customerName']}\n";

if (empty($data['hotels'][0]['customerName']) || $data['hotels'][0]['customerName'] === 'Unknown') {
    echo "FAIL: Hotel customerName join failed.\n";
    exit(1);
}
echo "  [OK] Hotel {$data['hotels'][0]['ref']} linked to customer: {$data['hotels'][0]['customerName']}\n";

if (empty($data['transactions'][0]['customerName']) || $data['transactions'][0]['customerName'] === 'Unknown') {
    echo "FAIL: Transaction customerName join failed.\n";
    exit(1);
}
echo "  [OK] Transaction {$data['transactions'][0]['ref']} linked to customer: {$data['transactions'][0]['customerName']}\n";
echo "PASS: Relational integrity verified.\n\n";

// 3. Test Real-time Update into MySQL
echo "3. Testing RealtimeDataService::updateRecord() against MySQL...\n";
$flightId = $data['flights'][0]['id']; // e.g. 'f8'
$cleanId = $data['flights'][0]['dbId']; // 8
$originalStatus = $data['flights'][0]['status'];

RealtimeDataService::updateRecord('flight', $flightId, ['status' => 'Pending']);
$rowAfter = $db->selectOne("SELECT status FROM flights WHERE id = :id", [':id' => $cleanId]);
if ($rowAfter['status'] !== 'Pending') {
    echo "FAIL: MySQL update did not reflect. Got: {$rowAfter['status']}\n";
    exit(1);
}
echo "  [OK] MySQL record updated to 'Pending'\n";

// Revert
RealtimeDataService::updateRecord('flight', $flightId, ['status' => $originalStatus]);
$rowRevert = $db->selectOne("SELECT status FROM flights WHERE id = :id", [':id' => $cleanId]);
if ($rowRevert['status'] !== $originalStatus) {
    echo "FAIL: Revert failed.\n";
    exit(1);
}
echo "  [OK] MySQL record reverted back to '{$originalStatus}'\n";
echo "PASS: Real-time database update operates correctly.\n\n";

// 4. Test Real-time Create and Delete
echo "4. Testing RealtimeDataService::createDestination() and deleteRecord()...\n";
$newDest = RealtimeDataService::createDestination([
    'city' => 'Nairobi Test',
    'country' => 'Kenya',
    'continent' => 'Africa',
    'categories' => ['Adventure', 'Safari'],
    'blurb' => 'Test capital of Kenya',
]);

$checkNew = $db->selectOne("SELECT * FROM destinations WHERE city = 'Nairobi Test'");
if (!$checkNew) {
    echo "FAIL: New destination not found in MySQL.\n";
    exit(1);
}
echo "  [OK] Created destination in MySQL: #{$checkNew['id']} ({$checkNew['city']})\n";

RealtimeDataService::deleteRecord('destination', $checkNew['id']);
$checkDeleted = $db->selectOne("SELECT * FROM destinations WHERE city = 'Nairobi Test'");
if ($checkDeleted) {
    echo "FAIL: Destination was not deleted from MySQL.\n";
    exit(1);
}
echo "  [OK] Deleted test destination from MySQL\n";
echo "PASS: Real-time create and delete operate correctly.\n\n";

// 5. Test Customer Data Service
echo "5. Checking RealtimeDataService::getCustomerData()...\n";
$custData = RealtimeDataService::getCustomerData(1);
if (empty($custData['customer']['name']) || count($custData['flights']) === 0) {
    echo "FAIL: Customer data extraction failed.\n";
    exit(1);
}
echo "  [OK] Customer: {$custData['customer']['name']} ({$custData['customer']['email']})\n";
echo "  [OK] Customer flights: " . count($custData['flights']) . "\n";
echo "  [OK] Customer hotels: " . count($custData['hotels']) . "\n";
echo "  [OK] Customer financing: " . count($custData['financing']) . "\n";
echo "  [OK] Customer payments: " . count($custData['payments']) . "\n";
echo "PASS: Customer real-time data service operating correctly.\n\n";

echo "=== ALL REAL-TIME UNIT & INTEGRATION TESTS PASSED 100%! ===\n";

