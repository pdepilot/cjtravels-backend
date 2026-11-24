<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;
use App\Services\RealtimeDataService;

echo "=== TRAVEL FINANCING (PAY SMALL SMALL) INTEGRATION TESTS ===\n\n";

$db = Database::getInstance();

// -------------------------------------------------------------
// Test 1: Validation rejection of empty or invalid data
// -------------------------------------------------------------
echo "1. Testing validation of invalid loan enquiries...\n";

$bad1 = RealtimeDataService::applyLoanFromFinancing([
    'fullName' => '',
    'email' => 'invalid-email',
    'amount' => 0,
    'period' => '',
    'purpose' => ''
]);

if ($bad1['success'] !== false) {
    echo "FAIL: Expected validation failure for empty payload.\n";
    exit(1);
}
echo "  [OK] Successfully rejected empty/invalid payload: {$bad1['error']}\n";

$bad2 = RealtimeDataService::applyLoanFromFinancing([
    'fullName' => 'Test User',
    'email' => 'test@example.com',
    'amount' => -5000,
    'period' => '6 months',
    'purpose' => 'Vacation'
]);

if ($bad2['success'] !== false) {
    echo "FAIL: Expected validation failure for negative amount.\n";
    exit(1);
}
echo "  [OK] Successfully rejected negative budget amount.\n\n";

// -------------------------------------------------------------
// Test 2: Valid loan application & indicative calculation
// -------------------------------------------------------------
echo "2. Submitting valid travel financing enquiry...\n";

$testEmail = 'financing.applicant.' . time() . '@example.com';
$testName = 'Ngozi Chimamanda';
$testAmount = 850000.0;
$testPeriod = '6 months';
$testPurpose = 'Pilgrimage';
$testNote = 'Trip to Rome and Jerusalem with spouse';

$applied = RealtimeDataService::applyLoanFromFinancing([
    'fullName' => $testName,
    'email' => $testEmail,
    'phone' => '+2348123456789',
    'country' => 'Nigeria',
    'amount' => $testAmount,
    'period' => $testPeriod,
    'purpose' => $testPurpose,
    'notes' => $testNote
]);

if (empty($applied['success']) || empty($applied['loan'])) {
    echo "FAIL: Loan application failed: " . ($applied['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

$loan = $applied['loan'];
$loanRef = $loan['ref'];
$loanDbId = (int)$loan['dbId'];

echo "  [OK] Financing application accepted! Ref: {$loanRef}, DB ID: {$loanDbId}\n";
echo "  [OK] Status: {$loan['status']}\n";
echo "  [OK] Monthly instalment: ₦" . number_format($loan['monthly_installment']) . "\n";
echo "  [OK] Total repayable: ₦" . number_format($loan['total_repayable']) . "\n";

// Verify MySQL persistence
$dbRow = $db->selectOne("SELECT * FROM loan_enquiries WHERE id = :id LIMIT 1", [':id' => $loanDbId]);
if (!$dbRow) {
    echo "FAIL: Loan record not found in loan_enquiries table.\n";
    exit(1);
}
if ((float)$dbRow['amount'] !== $testAmount || $dbRow['period'] !== $testPeriod) {
    echo "FAIL: Saved loan data mismatch in MySQL.\n";
    exit(1);
}
$applicantUserId = (int)$dbRow['user_id'];
echo "  [OK] Persisted in MySQL database (user_id: {$applicantUserId}).\n\n";

// -------------------------------------------------------------
// Test 3: Customer Dashboard Data Synchronization
// -------------------------------------------------------------
echo "3. Verifying Customer Portal real-time query...\n";

$custData = RealtimeDataService::getCustomerData($applicantUserId);
$foundInCust = false;
foreach ($custData['financing'] as $fin) {
    if ($fin['ref'] === $loanRef) {
        $foundInCust = true;
        if ((float)$fin['amount'] !== $testAmount || $fin['status'] !== 'In review') {
            echo "FAIL: Customer financing data fields corrupted.\n";
            exit(1);
        }
        break;
    }
}

if (!$foundInCust) {
    echo "FAIL: Loan enquiry {$loanRef} not visible in customer financing list.\n";
    exit(1);
}
echo "  [OK] Loan {$loanRef} immediately reflected in Customer Dashboard financing list.\n\n";

// -------------------------------------------------------------
// Test 4: Admin Console Data Synchronization
// -------------------------------------------------------------
echo "4. Verifying Admin Console real-time query...\n";

$adminData = RealtimeDataService::getAdminData();
$foundInAdmin = false;
foreach ($adminData['loans'] as $al) {
    if ($al['ref'] === $loanRef) {
        $foundInAdmin = true;
        if ($al['customerName'] !== $testName) {
            echo "FAIL: Expected admin loan customerName '{$testName}', got '{$al['customerName']}'.\n";
            exit(1);
        }
        break;
    }
}

if (!$foundInAdmin) {
    echo "FAIL: Loan enquiry {$loanRef} not visible in Admin loans list.\n";
    exit(1);
}
echo "  [OK] Loan {$loanRef} immediately visible in Admin Loans table with joined customer name.\n\n";

// -------------------------------------------------------------
// Test 5: Admin status update & real-time customer reflection
// -------------------------------------------------------------
echo "5. Testing Admin status update and sync back to Customer...\n";

$updateOk = RealtimeDataService::updateRecord('loan', $loanDbId, [
    'status' => 'Approved',
    'note' => 'Approved by Lending Partner Zenith/Sterling. First payment due in 30 days.'
]);

if (!$updateOk) {
    echo "FAIL: Admin updateRecord failed: " . RealtimeDataService::lastError() . "\n";
    exit(1);
}
echo "  [OK] Admin updated loan {$loanRef} status to 'Approved'.\n";

// Re-check customer data to verify status reflects 'Approved'
$custDataAfter = RealtimeDataService::getCustomerData($applicantUserId);
$approvedFound = false;
foreach ($custDataAfter['financing'] as $fin) {
    if ($fin['ref'] === $loanRef && $fin['status'] === 'Approved') {
        $approvedFound = true;
        break;
    }
}

if (!$approvedFound) {
    echo "FAIL: Customer financing did not reflect the Approved status.\n";
    exit(1);
}
echo "  [OK] Customer Dashboard financing reflects 'Approved' status in real time.\n\n";

// -------------------------------------------------------------
// Clean up test records
// -------------------------------------------------------------
echo "6. Cleaning up test data...\n";
$db->delete('loan_enquiries', 'id = :id', [':id' => $loanDbId]);
$db->delete('users', 'id = :id', [':id' => $applicantUserId]);
echo "  [OK] Test records cleaned up.\n\n";

echo "=== ALL FINANCING TESTS PASSED (100%) ===\n";

