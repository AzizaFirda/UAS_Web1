<?php
require_once __DIR__ . '/backend/config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'db_host' => 'localhost',
        'db_name' => 'rdyaazzw_db_finance',
        'db_user' => 'rdyaazzw_firda'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
}
?>
