<?php
/**
 * Test Suite: Promotional Flier Upload & Campaign Management
 */
require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;
use App\Services\RealtimeDataService;
use App\Core\Request;
use App\Core\Router;

echo "=== CJ TRAVELS: PROMOTIONAL FLIER UPLOAD TEST SUITE ===\n\n";

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

// 1. Ensure Schema
RealtimeDataService::ensurePromoCampaignSchema();
$db = Database::getInstance();
$tableCheck = $db->selectOne("SHOW TABLES LIKE 'promo_campaigns'");
assertTest("Table 'promo_campaigns' exists in MySQL", !empty($tableCheck));

// 2. Initial Campaign state
$initialCampaign = RealtimeDataService::getActivePromoCampaign();
assertTest("Initial active promo campaign retrieved successfully", !empty($initialCampaign['id']) && !empty($initialCampaign['title']));

// 3. Create a valid test image (100x100 PNG image)
$testImgDir = sys_get_temp_dir();
$testImgPath = $testImgDir . DIRECTORY_SEPARATOR . 'test_promo_flier_' . time() . '.png';

$im = imagecreatetruecolor(400, 250);
$bg = imagecolorallocate($im, 11, 30, 61); // CJ Navy
$gold = imagecolorallocate($im, 201, 165, 91); // CJ Gold
imagefill($im, 0, 0, $bg);
imagestring($im, 5, 50, 100, "CJ TRAVELS LUXURY FLIER", $gold);
imagepng($im, $testImgPath);
imagedestroy($im);

assertTest("Temporary test image generated at {$testImgPath}", file_exists($testImgPath) && filesize($testImgPath) > 0);

// 4. Test uploading promotional flier
$simulatedFile = [
    'name' => 'luxury_paris_flier.png',
    'type' => 'image/png',
    'tmp_name' => $testImgPath,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($testImgPath),
];

$updatePayload = [
    'title' => 'Fly to Paris & London in Pure Luxury',
    'subtitle' => 'Exclusive ₦75,000 summer flight discount voucher for distinguished guests.',
    'promo_code' => 'PARISLUXE75',
    'discount_amount' => '₦75,000',
    'is_active' => 1,
];

$updateResult = RealtimeDataService::updatePromoCampaign($updatePayload, $simulatedFile);
assertTest("Promo campaign update with flier returns success", !empty($updateResult['success']));

$updatedCampaign = RealtimeDataService::getActivePromoCampaign();
assertTest("Active campaign title matches 'Fly to Paris & London in Pure Luxury'", $updatedCampaign['title'] === 'Fly to Paris & London in Pure Luxury');
assertTest("Active campaign promo code matches 'PARISLUXE75'", $updatedCampaign['promo_code'] === 'PARISLUXE75');
assertTest("Active campaign discount amount matches '₦75,000'", $updatedCampaign['discount_amount'] === '₦75,000');
assertTest("Active campaign flier_image is populated", !empty($updatedCampaign['flier_image']));

// Verify file saved in both public/images/promo/ and images/promo/
$baseDir = dirname(__DIR__);
$publicSavedPath = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $updatedCampaign['flier_image']);
$rootSavedPath   = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $updatedCampaign['flier_image']);

assertTest("Flier file exists in public directory: {$publicSavedPath}", file_exists($publicSavedPath));
assertTest("Flier file synced to root directory: {$rootSavedPath}", file_exists($rootSavedPath));

// 5. Test Partial Promo Modal Rendering
ob_start();
include $baseDir . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'promo-modal.php';
$modalHtml = ob_get_clean();

assertTest("Modal HTML contains uploaded flier image path", strpos($modalHtml, $updatedCampaign['flier_image']) !== false);
assertTest("Modal HTML contains custom campaign title", strpos($modalHtml, htmlspecialchars('Fly to Paris & London in Pure Luxury')) !== false);
assertTest("Modal HTML contains custom promo code 'PARISLUXE75'", strpos($modalHtml, 'PARISLUXE75') !== false);
assertTest("Modal HTML contains '.cj-promo-flier-wrap'", strpos($modalHtml, 'cj-promo-flier-wrap') !== false);

// 6. Test Admin Settings View Rendering
$admin = \App\Models\Admin::findByEmail('admin@cjtravels.com') ?? new \App\Models\Admin(['id' => 1, 'name' => 'Admin', 'email' => 'admin@cjtravels.com']);
$settingsHtml = \App\Core\View::render('admin/settings', [
    'title' => 'Console Settings',
    'page' => 'settings',
    'admin' => $admin,
    'promoCampaign' => $updatedCampaign,
    'success' => 'Test success message',
    'error' => null,
    'environment' => ['engine' => 'MySQL', 'name' => 'cjtravels_db', 'host' => '127.0.0.1:3306', 'server' => 'PHP CLI'],
]);

assertTest("Admin settings HTML contains 'Promotional Flier &amp; Campaign Manager'", strpos($settingsHtml, 'Promotional Flier') !== false);
assertTest("Admin settings HTML contains flier preview image", strpos($settingsHtml, $updatedCampaign['flier_image']) !== false);
assertTest("Admin settings HTML contains file input 'flier_image'", strpos($settingsHtml, 'name="flier_image"') !== false);
assertTest("Admin settings HTML contains remove flier checkbox", strpos($settingsHtml, 'name="remove_flier"') !== false);

// 7. Test Removing the Promotional Flier
$removeResult = RealtimeDataService::updatePromoCampaign([
    'title' => 'Unlock ₦50,000 Off Your Next Luxury Getaway',
    'subtitle' => 'Fly gracefully to London, Dubai, Paris, or explore paradise in Zanzibar.',
    'promo_code' => 'CJLUXE50K',
    'discount_amount' => '₦50,000',
    'is_active' => 1,
    'remove_flier' => 1,
]);
assertTest("Removing promotional flier returns success", !empty($removeResult['success']));

$revertedCampaign = RealtimeDataService::getActivePromoCampaign();
assertTest("Reverted campaign flier_image is null", empty($revertedCampaign['flier_image']));

// 8. Test Modal rendering without flier
ob_start();
include $baseDir . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'promo-modal.php';
$revertedModalHtml = ob_get_clean();
assertTest("Reverted modal does not render '.cj-promo-flier-wrap' when flier is removed", strpos($revertedModalHtml, 'cj-promo-flier-wrap') === false);

// Cleanup temporary generated files
@unlink($testImgPath);
if (file_exists($publicSavedPath)) @unlink($publicSavedPath);
if (file_exists($rootSavedPath)) @unlink($rootSavedPath);

echo "\n============================================\n";
echo "TEST RESULTS: {$passCount} Passed, {$failCount} Failed\n";
echo "============================================\n";

if ($failCount > 0) {
    exit(1);
}
