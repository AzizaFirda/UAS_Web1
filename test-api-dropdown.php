<?php
// Test script to check API responses

// Check if we're in a web context
if (php_sapi_name() === 'cli') {
    echo "Running CLI test...\n";
    define('SCRIPT_PATH', __DIR__);
} else {
    define('SCRIPT_PATH', __DIR__);
}

// Test categories API
echo "Testing Categories API...\n";
echo "============================\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/FinanceManagerWeb/backend/api/categories.php?action=list&type=income");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id());
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpcode\n";
echo "Response: $response\n\n";

// Test accounts API
echo "Testing Accounts API...\n";
echo "============================\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/FinanceManagerWeb/backend/api/accounts.php?action=list");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id());
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpcode\n";
echo "Response: $response\n";
?>
