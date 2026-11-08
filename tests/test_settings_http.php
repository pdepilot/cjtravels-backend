<?php

$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

$baseUrl = 'http://localhost/TravelBooking';

function httpGet($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $response];
}

function httpPost($url, $data, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $response];
}

function extractCsrf($html) {
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    return '';
}

echo "=== HTTP INTEGRATION TEST FOR ADMIN SETTINGS ===\n\n";

// 1. Visit Login Page and extract CSRF
echo "1. Fetching login page...\n";
$res = httpGet($baseUrl . '/admin/login', $cookieFile);
$csrf = extractCsrf($res['body']);
if (empty($csrf)) {
    echo "FAIL: CSRF token not found on login page.\n";
    exit(1);
}
echo "PASS: Login page loaded, CSRF token extracted.\n";

// 2. Perform Login as Super Admin
echo "2. Signing in as Super Admin...\n";
$loginRes = httpPost($baseUrl . '/admin/login', [
    '_token' => $csrf,
    'identifier' => 'admin@cjtravels.com',
    'password' => 'SuperSecretPassword123!',
], $cookieFile);

if (!str_contains($loginRes['body'], 'Admin dashboard') && !str_contains($loginRes['body'], 'Dashboard')) {
    echo "FAIL: Could not log in. Response:\n" . substr($loginRes['body'], 0, 500) . "\n";
    exit(1);
}
echo "PASS: Successfully logged in.\n";

// 3. Visit /admin/settings
echo "3. Accessing /admin/settings...\n";
$settingsRes = httpGet($baseUrl . '/admin/settings', $cookieFile);
if ($settingsRes['status'] !== 200 || !str_contains($settingsRes['body'], 'Administrator Settings')) {
    echo "FAIL: /admin/settings did not load properly (Status: {$settingsRes['status']})\n";
    exit(1);
}
echo "PASS: /admin/settings loaded successfully.\n";

$settingsCsrf = extractCsrf($settingsRes['body']);
if (empty($settingsCsrf)) {
    echo "FAIL: CSRF token not found on settings page.\n";
    exit(1);
}

// 4. Test submitting profile update with WRONG current password
echo "4. Testing profile update with WRONG current password...\n";
$wrongPwdRes = httpPost($baseUrl . '/admin/settings/profile', [
    '_token' => $settingsCsrf,
    'name' => 'CJ Travels Director',
    'email' => 'newemail@cjtravels.com',
    'phone' => '+234 809 111 2222',
    'username' => 'cjadmin',
    'current_password' => 'IncorrectPassword!',
], $cookieFile);

if (!str_contains($wrongPwdRes['body'], 'current password you entered is incorrect')) {
    echo "FAIL: Expected incorrect password alert. Response:\n" . substr($wrongPwdRes['body'], 0, 500) . "\n";
    exit(1);
}
echo "PASS: Protected against unauthorized update when current password is wrong.\n";

// 5. Test submitting profile update with CORRECT current password
echo "5. Testing profile update with CORRECT current password (updating Email & Phone)...\n";
$updatedEmail = 'admin.updated@cjtravels.com';
$updatedPhone = '+234 818 777 6655';

// Refresh CSRF from page
$settingsCsrf = extractCsrf($wrongPwdRes['body']);

$updateRes = httpPost($baseUrl . '/admin/settings/profile', [
    '_token' => $settingsCsrf,
    'name' => 'CJ Travels Director',
    'email' => $updatedEmail,
    'phone' => $updatedPhone,
    'username' => 'cjadmin',
    'current_password' => 'SuperSecretPassword123!',
], $cookieFile);

if (!str_contains($updateRes['body'], 'Administrator login details updated successfully')) {
    echo "FAIL: Profile update failed. Response:\n" . substr($updateRes['body'], 0, 600) . "\n";
    exit(1);
}
if (!str_contains($updateRes['body'], $updatedEmail) || !str_contains($updateRes['body'], $updatedPhone)) {
    echo "FAIL: Updated email or phone not reflected in page HTML.\n";
    exit(1);
}
echo "PASS: Successfully updated login details (Email: {$updatedEmail}, Phone: {$updatedPhone}).\n";

// 6. Sign out
echo "6. Signing out...\n";
$logoutCsrf = extractCsrf($updateRes['body']);
httpPost($baseUrl . '/admin/logout', ['_token' => $logoutCsrf], $cookieFile);

// 7. Verify we can log in using the newly updated PHONE NUMBER
echo "7. Logging in using the newly configured PHONE NUMBER ({$updatedPhone})...\n";
$resPhoneLogin = httpGet($baseUrl . '/admin/login', $cookieFile);
$csrfPhone = extractCsrf($resPhoneLogin['body']);

$phoneAuthRes = httpPost($baseUrl . '/admin/login', [
    '_token' => $csrfPhone,
    'identifier' => $updatedPhone,
    'password' => 'SuperSecretPassword123!',
], $cookieFile);

if (!str_contains($phoneAuthRes['body'], 'Admin dashboard') && !str_contains($phoneAuthRes['body'], 'Dashboard')) {
    echo "FAIL: Login with new phone number failed.\n";
    exit(1);
}
echo "PASS: Logged in successfully using the updated PHONE NUMBER!\n";

// 8. Sign out again
$dashCsrf = extractCsrf($phoneAuthRes['body']);
httpPost($baseUrl . '/admin/logout', ['_token' => $dashCsrf], $cookieFile);

// 9. Verify we can log in using the newly updated EMAIL ADDRESS
echo "8. Logging in using the newly configured EMAIL ADDRESS ({$updatedEmail})...\n";
$resEmailLogin = httpGet($baseUrl . '/admin/login', $cookieFile);
$csrfEmail = extractCsrf($resEmailLogin['body']);

$emailAuthRes = httpPost($baseUrl . '/admin/login', [
    '_token' => $csrfEmail,
    'identifier' => $updatedEmail,
    'password' => 'SuperSecretPassword123!',
], $cookieFile);

if (!str_contains($emailAuthRes['body'], 'Admin dashboard') && !str_contains($emailAuthRes['body'], 'Dashboard')) {
    echo "FAIL: Login with new email address failed.\n";
    exit(1);
}
echo "PASS: Logged in successfully using the updated EMAIL ADDRESS!\n";

// 10. Revert credentials back to original standard values
echo "9. Reverting credentials back to admin@cjtravels.com via Settings...\n";
$settingsResRevert = httpGet($baseUrl . '/admin/settings', $cookieFile);
$revertCsrf = extractCsrf($settingsResRevert['body']);

$revertRes = httpPost($baseUrl . '/admin/settings/profile', [
    '_token' => $revertCsrf,
    'name' => 'CJ Travels Director',
    'email' => 'admin@cjtravels.com',
    'phone' => '+234 812 345 6789',
    'username' => 'cjadmin',
    'current_password' => 'SuperSecretPassword123!',
], $cookieFile);

if (!str_contains($revertRes['body'], 'Administrator login details updated successfully')) {
    echo "FAIL: Revert failed.\n";
    exit(1);
}
echo "PASS: Credentials successfully reverted to admin@cjtravels.com and +234 812 345 6789.\n";

if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\n=== ALL HTTP INTEGRATION TESTS PASSED 100%! ===\n";

