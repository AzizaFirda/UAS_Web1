<?php
// Debug summary calculation
require_once __DIR__ . '/backend/config/database.php';

$userId = 5; // User 5

$db = getDB();

// Test 1: Direct SQL with PDO::FETCH_ASSOC
echo "=== Test 1: Direct SQL (FETCH_ASSOC) ===\n";
$sql = "SELECT 
            SUM(CASE WHEN type IN ('cash', 'bank', 'ewallet') THEN current_balance ELSE 0 END) as total_assets,
            SUM(CASE WHEN type = 'debt' AND current_balance > 0 THEN current_balance ELSE 0 END) as total_liabilities
        FROM accounts 
        WHERE user_id = :user_id";
$stmt = $db->prepare($sql);
$stmt->execute([':user_id' => $userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Result: " . json_encode($result) . "\n";

// Test 2: Using default fetch (what getSummary uses)
echo "\n=== Test 2: Default fetch() ===\n";
$stmt = $db->prepare($sql);
$stmt->execute([':user_id' => $userId]);
$result = $stmt->fetch();
echo "Result: " . json_encode($result) . "\n";
var_dump($result);

// Test 3: Check Account model's getSummary directly
echo "\n=== Test 3: Account Model getSummary() ===\n";
require_once __DIR__ . '/backend/models/Account.php';
$accountModel = new Account();
$summary = $accountModel->getSummary($userId);
echo "Summary: " . json_encode($summary) . "\n";
var_dump($summary);

// Test 4: All accounts list
echo "\n=== Test 4: All Accounts (getAll) ===\n";
$accounts = $accountModel->getAll($userId);
echo "Accounts: " . json_encode($accounts) . "\n";
foreach ($accounts as $acc) {
    echo "ID {$acc['id']}: {$acc['name']} ({$acc['type']}) = {$acc['current_balance']}\n";
}
?>
