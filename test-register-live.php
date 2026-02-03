<?php
// Quick register test with full error output
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== LIVE REGISTER TEST ===\n\n";

// Prepare test data
$testEmail = 'livetest' . time() . '@test.com';
$testData = [
    'name' => 'Live Test User',
    'email' => $testEmail,
    'password' => 'password123'
];

echo "Test data:\n";
print_r($testData);
echo "\n";

// Make actual curl request to API
$url = 'https://pipil.my.id/backend/api/auth.php?action=register';
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

echo "Sending request to: $url\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n\n";

if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
}

rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "cURL Verbose Log:\n$verboseLog\n";

curl_close($ch);

// Try to decode response
echo "\nDecoded Response:\n";
$decoded = json_decode($response, true);
if ($decoded) {
    print_r($decoded);
} else {
    echo "Failed to decode JSON. Raw response:\n";
    echo htmlspecialchars($response);
}
?>
