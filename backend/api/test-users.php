<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM users LIMIT 5');
    $stmt->execute();
    $users = $stmt->fetchAll();
    echo "Users in database: " . count($users) . "\n";
    foreach ($users as $u) {
        echo "User ID: " . $u['id'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
