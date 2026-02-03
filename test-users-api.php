<?php
// Test users.php API
require_once __DIR__ . '/backend/config/database.php';

echo "Testing Users API...\n";

// Test 1: Check if users table exists and has theme column
$db = getDB();
$sql = "DESCRIBE users";
$stmt = $db->query($sql);
echo "\n=== Users Table Structure ===\n";
while ($row = $stmt->fetch()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Test 2: Check user 5's current settings
echo "\n=== User 5 Current Settings ===\n";
$sql = "SELECT id, name, email, theme, language, currency, date_format FROM users WHERE id = 5";
$stmt = $db->prepare($sql);
$stmt->execute();
$result = $stmt->fetch();
if ($result) {
    echo "Theme: " . ($result['theme'] ?? 'NULL') . "\n";
    echo "Language: " . ($result['language'] ?? 'NULL') . "\n";
    echo "Currency: " . ($result['currency'] ?? 'NULL') . "\n";
    echo "Date Format: " . ($result['date_format'] ?? 'NULL') . "\n";
} else {
    echo "User not found\n";
}

// Test 3: Try updating
echo "\n=== Attempting Update ===\n";
$data = [
    'theme' => 'dark',
    'language' => 'id',
    'currency' => 'IDR',
    'date_format' => 'DD/MM/YYYY'
];

$fields = [];
$params = [':user_id' => 5];
foreach (['theme', 'language', 'currency', 'date_format'] as $field) {
    if (isset($data[$field])) {
        $fields[] = "$field = :$field";
        $params[":$field"] = $data[$field];
    }
}

$sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :user_id";
echo "SQL: $sql\n";
echo "Params: " . json_encode($params) . "\n";

try {
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($params);
    echo "Update successful: " . ($result ? "YES" : "NO") . "\n";
    echo "Rows affected: " . $stmt->rowCount() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 4: Verify update
echo "\n=== After Update ===\n";
$sql = "SELECT id, name, theme, language, currency, date_format FROM users WHERE id = 5";
$stmt = $db->prepare($sql);
$stmt->execute();
$result = $stmt->fetch();
if ($result) {
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>
