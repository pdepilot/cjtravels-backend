<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\App;
use App\Core\Router;
use App\Core\Request;

echo "=== TESTING COOKIE BANNER & LEGAL PAGES ===\n\n";

$testsPassed = 0;
$testsTotal = 0;

function assertCondition(bool $condition, string $message): void {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    if ($condition) {
        echo " [PASS] $message\n";
        $testsPassed++;
    } else {
        echo " [FAIL] $message\n";
    }
}

function makeRequest(string $method, string $uri): Request {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_GET = [];
    $_POST = [];
    return new Request();
}

// 1. Check Asset Files Exist
assertCondition(file_exists(__DIR__ . '/../css/cookie-banner.css'), "css/cookie-banner.css exists");
assertCondition(file_exists(__DIR__ . '/../public/css/cookie-banner.css'), "public/css/cookie-banner.css exists");
assertCondition(file_exists(__DIR__ . '/../js/cookie-banner.js'), "js/cookie-banner.js exists");
assertCondition(file_exists(__DIR__ . '/../public/js/cookie-banner.js'), "public/js/cookie-banner.js exists");
assertCondition(file_exists(__DIR__ . '/../public/terms.php'), "public/terms.php exists");
assertCondition(file_exists(__DIR__ . '/../public/privacy.php'), "public/privacy.php exists");
assertCondition(file_exists(__DIR__ . '/../terms.php'), "root terms.php exists");
assertCondition(file_exists(__DIR__ . '/../privacy.php'), "root privacy.php exists");

// Ensure routes are loaded
require_once __DIR__ . '/../routes/web.php';

// 2. Test Route Resolution for /terms
$request = makeRequest('GET', '/terms');
$response = Router::dispatch($request);

assertCondition($response->getStatusCode() === 200, "Route /terms returns HTTP 200");
$termsBody = $response->getContent();
assertCondition(str_contains($termsBody, 'Terms of Use &amp; Booking Conditions') || str_contains($termsBody, 'Terms of Use'), "/terms contains Terms of Use heading");
assertCondition(str_contains($termsBody, 'cookie-banner.css'), "/terms includes cookie-banner.css");
assertCondition(str_contains($termsBody, 'cookie-banner.js'), "/terms includes cookie-banner.js");
assertCondition(str_contains($termsBody, 'cj-cookie-banner'), "/terms includes cookie consent markup");

// 3. Test Route Resolution for /privacy
$request = makeRequest('GET', '/privacy');
$response = Router::dispatch($request);

assertCondition($response->getStatusCode() === 200, "Route /privacy returns HTTP 200");
$privacyBody = $response->getContent();
assertCondition(str_contains($privacyBody, 'Privacy Policy &amp; Cookie Notice') || str_contains($privacyBody, 'Privacy Policy'), "/privacy contains Privacy Policy heading");
assertCondition(str_contains($privacyBody, 'Manage Your Cookie Preferences') || str_contains($privacyBody, 'Cookie Policy'), "/privacy contains Cookie Policy section");
assertCondition(str_contains($privacyBody, 'data-open-cookie-settings'), "/privacy has interactive cookie settings button");
assertCondition(str_contains($privacyBody, 'cookie-banner.js'), "/privacy includes cookie-banner.js");

// 4. Test Home Page (/) for Cookie Banner & Footer Links
$request = makeRequest('GET', '/');
$response = Router::dispatch($request);
assertCondition($response->getStatusCode() === 200, "Route / returns HTTP 200");
$homeBody = $response->getContent();
assertCondition(str_contains($homeBody, 'cookie-banner.css'), "Home page includes cookie-banner.css");
assertCondition(str_contains($homeBody, 'cookie-banner.js'), "Home page includes cookie-banner.js");
assertCondition(str_contains($homeBody, 'href="/privacy"') || str_contains($homeBody, 'privacy.php'), "Home page footer links to privacy");
assertCondition(str_contains($homeBody, 'href="/terms"') || str_contains($homeBody, 'terms.php'), "Home page footer links to terms");
assertCondition(str_contains($homeBody, 'data-open-cookie-settings') || str_contains($homeBody, 'Cookie Settings'), "Home page footer has Cookie Settings trigger");

// 5. Test Legacy Redirects
$request = makeRequest('GET', '/terms.html');
$response = Router::dispatch($request);
assertCondition($response->getStatusCode() === 302, "/terms.html redirects with HTTP 302");
assertCondition(str_contains($response->getHeader('Location') ?? '', '/terms'), "/terms.html redirects to /terms");

$request = makeRequest('GET', '/privacy.html');
$response = Router::dispatch($request);
assertCondition($response->getStatusCode() === 302, "/privacy.html redirects with HTTP 302");
assertCondition(str_contains($response->getHeader('Location') ?? '', '/privacy'), "/privacy.html redirects to /privacy");

echo "\nSummary: $testsPassed / $testsTotal tests passed.\n";

if ($testsPassed === $testsTotal) {
    echo "SUCCESS: All cookie banner and legal compliance checks passed!\n";
    exit(0);
} else {
    echo "ERROR: Some tests failed.\n";
    exit(1);
}
