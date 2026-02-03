<?php
// Test users.php API directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/backend/config/database.php';

echo "Testing Users API Update...\n\n";

$db = getDB();

// Test data
$data = [
    'theme' => 'dark',
    'language' => 'id',
    'currency' => 'IDR',
    'date_format' => 'DD/MM/YYYY'
];

echo "Update Data: " . json_encode($data) . "\n";
echo "User ID: 5\n\n";

// Build the update query
$fields = [];
$params = [':user_id' => 5];

$allowedFields = ['name', 'email', 'currency', 'language', 'date_format', 'theme'];

foreach ($allowedFields as $field) {
    if (isset($data[$field])) {
        $fields[] = "$field = :$field";
        $params[":$field"] = $data[$field];
    }
}

if (empty($fields)) {
    echo "Error: No fields to update\n";
} else {
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :user_id";
    echo "SQL: $sql\n";
    echo "Params: " . json_encode($params) . "\n\n";
    
    try {
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        echo "Update Result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
        echo "Rows Affected: " . $stmt->rowCount() . "\n\n";
        
        // Verify
        echo "=== After Update ===\n";
        $verify = $db->prepare("SELECT id, name, theme, language, currency, date_format FROM users WHERE id = 5");
        $verify->execute();
        $user = $verify->fetch();
        echo json_encode($user, JSON_PRETTY_PRINT) . "\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
