<?php

$cookieFile = __DIR__ . '/test_rt_cookie.txt';
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
    if (preg_match('/CJ_CSRF_TOKEN\s*=\s*"([^"]+)"/', $html, $matches)) {
        return $matches[1];
    }
    return '';
}

echo "=== HTTP INTEGRATION TEST FOR REAL-TIME DATA ARCHITECTURE ===\n\n";

// 1. Login as Super Admin
$res = httpGet($baseUrl . '/admin/login', $cookieFile);
$csrf = extractCsrf($res['body']);

$loginRes = httpPost($baseUrl . '/admin/login', [
    '_token' => $csrf,
    'identifier' => 'admin@cjtravels.com',
    'password' => 'SuperSecretPassword123!',
], $cookieFile);

if (!str_contains($loginRes['body'], 'Dashboard') && !str_contains($loginRes['body'], 'adminMain')) {
    echo "FAIL: Super admin login failed. Status: {$loginRes['status']}\n";
    if (preg_match('/class="alert[^"]*"[^>]*>(.*?)<\/div>/s', $loginRes['body'], $m)) {
        echo "Alert found: " . strip_tags($m[1]) . "\n";
    } else {
        echo substr($loginRes['body'], 0, 1000) . "\n";
    }
    exit(1);
}
echo "1. Super Admin authentication: OK\n";

// 2. Test Admin Pages for Real-time Data Injection
$pages = [
    '/admin/customers' => 'Adaeze Okonkwo',
    '/admin/flights' => 'TBF-2201',
    '/admin/hotels' => 'The Wheatbaker',
    '/admin/loans' => 'TBLN-0454',
    '/admin/destinations' => 'Cape Town',
    '/admin/transactions' => 'PAY-9005',
];

foreach ($pages as $endpoint => $expectedString) {
    $pageRes = httpGet($baseUrl . $endpoint, $cookieFile);
    if ($pageRes['status'] !== 200) {
        echo "FAIL: {$endpoint} returned HTTP {$pageRes['status']}\n";
        exit(1);
    }
    if (!str_contains($pageRes['body'], 'window.CJ_ADMIN_DATA')) {
        echo "FAIL: {$endpoint} does not contain window.CJ_ADMIN_DATA. Body preview:\n" . substr($pageRes['body'], 0, 400) . "\n";
        exit(1);
    }
    if (!str_contains($pageRes['body'], $expectedString)) {
        echo "FAIL: {$endpoint} does not contain real database data '{$expectedString}'\n";
        exit(1);
    }
    echo "  [OK] {$endpoint}: HTTP 200, real MySQL data payload injected.\n";
}
echo "PASS: All admin pages successfully injected with real-time MySQL data.\n\n";

// 3. Test /admin/api/live-data Endpoint
echo "2. Testing /admin/api/live-data endpoint...\n";
$apiRes = httpGet($baseUrl . '/admin/api/live-data', $cookieFile);
if ($apiRes['status'] !== 200) {
    echo "FAIL: /admin/api/live-data returned HTTP {$apiRes['status']}\n";
    exit(1);
}
$apiJson = json_decode($apiRes['body'], true);
if (empty($apiJson['success']) || count($apiJson['data']['customers']) < 10) {
    echo "FAIL: /admin/api/live-data returned invalid data:\n" . substr($apiRes['body'], 0, 300) . "\n";
    exit(1);
}
echo "  [OK] /admin/api/live-data returned: " . count($apiJson['data']['customers']) . " customers, " . count($apiJson['data']['flights']) . " flights, " . count($apiJson['data']['transactions']) . " transactions\n";
echo "PASS: Live MySQL API endpoint fully operational.\n\n";

// 4. Test /customer/dashboard
echo "3. Testing customer portal...\n";
$custRes = httpGet($baseUrl . '/customer/dashboard', $cookieFile);
if ($custRes['status'] !== 200 || !str_contains($custRes['body'], 'window.CJ_CUSTOMER_DATA')) {
    echo "FAIL: /customer/dashboard did not return real-time customer data\n";
    exit(1);
}
echo "  [OK] /customer/dashboard returned HTTP 200 with window.CJ_CUSTOMER_DATA\n";
echo "PASS: Customer portal real-time architecture verified.\n\n";

if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "=== ALL HTTP ENDPOINTS & REAL-TIME DATA ARCHITECTURE VERIFIED 100%! ===\n";
