<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Auth;
use App\Core\Database;
use App\Models\Admin;

echo "=== STARTING SETTINGS & CREDENTIAL UPDATE TEST ===\n\n";

// 1. Initial State Check
$admin = Admin::findById(1);
if (!$admin) {
    echo "FAIL: Super admin with ID 1 not found.\n";
    exit(1);
}

echo "Current Admin Record:\n";
echo "  ID: {$admin->id}\n";
echo "  Name: {$admin->name}\n";
echo "  Email: {$admin->email}\n";
echo "  Phone: " . ($admin->phone ?? '(null)') . "\n";
echo "  Username: {$admin->username}\n\n";

// 2. Test verifyPassword
echo "Checking password verification...\n";
if (!$admin->verifyPassword('SuperSecretPassword123!')) {
    echo "FAIL: Existing password failed verification.\n";
    exit(1);
}
if ($admin->verifyPassword('WrongPassword123!')) {
    echo "FAIL: Wrong password passed verification.\n";
    exit(1);
}
echo "PASS: verifyPassword works correctly.\n\n";

// 3. Test Profile Update (Email & Phone)
echo "Testing updating profile with new email and phone...\n";
$testEmail = 'director@cjtravels.com';
$testPhone = '+234 809 999 8877';
$testName = 'CJ Travels Chief Director';

$admin->updateProfile([
    'name' => $testName,
    'email' => $testEmail,
    'phone' => $testPhone,
]);

// Verify database reflects new email & phone
$refreshed = Admin::findById(1);
if ($refreshed->email !== $testEmail) {
    echo "FAIL: Email was not updated in database. Got: {$refreshed->email}\n";
    exit(1);
}
if ($refreshed->phone !== $testPhone) {
    echo "FAIL: Phone was not updated in database. Got: {$refreshed->phone}\n";
    exit(1);
}
if ($refreshed->name !== $testName) {
    echo "FAIL: Name was not updated in database. Got: {$refreshed->name}\n";
    exit(1);
}
echo "PASS: updateProfile successfully updated email, phone, and name in MySQL.\n\n";

// 4. Test Login with Updated Phone
echo "Testing login attempt using updated PHONE NUMBER...\n";
$phoneLoginResult = Auth::attemptAdmin($testPhone, 'SuperSecretPassword123!');
if (!$phoneLoginResult['success']) {
    echo "FAIL: Login using updated phone failed: " . ($phoneLoginResult['error'] ?? '') . "\n";
    exit(1);
}
echo "PASS: Successfully authenticated using the updated phone number ({$testPhone}).\n\n";

// 5. Test Login with Updated Email
echo "Testing login attempt using updated EMAIL ADDRESS...\n";
$emailLoginResult = Auth::attemptAdmin($testEmail, 'SuperSecretPassword123!');
if (!$emailLoginResult['success']) {
    echo "FAIL: Login using updated email failed: " . ($emailLoginResult['error'] ?? '') . "\n";
    exit(1);
}
echo "PASS: Successfully authenticated using the updated email address ({$testEmail}).\n\n";

// 6. Test Uniqueness Checks
echo "Testing uniqueness checks...\n";
$isTakenEmail = Admin::isEmailTakenByOther($testEmail, 999); // another admin ID
if (!$isTakenEmail) {
    echo "FAIL: isEmailTakenByOther returned false for existing email.\n";
    exit(1);
}
$isNotTakenEmail = Admin::isEmailTakenByOther($testEmail, 1); // same admin ID
if ($isNotTakenEmail) {
    echo "FAIL: isEmailTakenByOther returned true when excluding owner ID.\n";
    exit(1);
}

$isTakenPhone = Admin::isPhoneTakenByOther($testPhone, 999);
if (!$isTakenPhone) {
    echo "FAIL: isPhoneTakenByOther returned false for existing phone.\n";
    exit(1);
}
$isNotTakenPhone = Admin::isPhoneTakenByOther($testPhone, 1);
if ($isNotTakenPhone) {
    echo "FAIL: isPhoneTakenByOther returned true when excluding owner ID.\n";
    exit(1);
}
echo "PASS: Uniqueness checks for email and phone operate properly.\n\n";

// 7. Reset back to clean standard admin details
echo "Resetting admin back to standard administrator details...\n";
$admin->updateProfile([
    'name' => 'CJ Travels Director',
    'email' => 'admin@cjtravels.com',
    'phone' => '+234 812 345 6789',
    'username' => 'cjadmin',
]);

$finalCheck = Admin::findById(1);
echo "Final Verified Admin Record:\n";
echo "  ID: {$finalCheck->id}\n";
echo "  Name: {$finalCheck->name}\n";
echo "  Email: {$finalCheck->email}\n";
echo "  Phone: {$finalCheck->phone}\n";
echo "  Username: {$finalCheck->username}\n\n";

echo "=== ALL UNIT AND INTEGRATION TESTS PASSED! ===\n";

