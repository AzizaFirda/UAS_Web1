<?php
// Debug script untuk test categories API
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    // Check if categories table exists
    $result = $db->query("SHOW TABLES LIKE 'categories'")->fetch();
    
    if ($result) {
        echo json_encode([
            'message' => 'Categories table exists',
            'table_exists' => true
        ]);
    } else {
        echo json_encode([
            'message' => 'Categories table does NOT exist',
            'table_exists' => false,
            'action' => 'Run database.sql to initialize tables'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
