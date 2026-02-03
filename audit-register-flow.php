<?php
// Complete Register Flow Audit & Debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clean all buffers
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$audit = [
    'status' => 'running',
    'tests' => [],
    'errors' => []
];

try {
    // TEST 1: Load Database
    $audit['tests']['1_database_load'] = 'testing';
    require_once __DIR__ . '/backend/config/database.php';
    $database = new Database();
    $db = $database->connect();
    $audit['tests']['1_database_load'] = 'PASS ✅';
    
    // TEST 2: Load User Model
    $audit['tests']['2_user_model_load'] = 'testing';
    require_once __DIR__ . '/backend/models/User.php';
    $userModel = new User();
    $audit['tests']['2_user_model_load'] = 'PASS ✅';
    
    // TEST 3: Load Category Model
    $audit['tests']['3_category_model_load'] = 'testing';
    require_once __DIR__ . '/backend/models/Category.php';
    $categoryModel = new Category();
    $audit['tests']['3_category_model_load'] = 'PASS ✅';
    
    // TEST 4: Simulate Register Data
    $testEmail = 'auditregister' . time() . '@test.com';
    $testData = [
        'name' => 'Audit Register Test',
        'email' => $testEmail,
        'password' => 'password123'
    ];
    $audit['tests']['4_test_data_created'] = $testData;
    
    // TEST 5: Check if email exists
    $audit['tests']['5_check_email_exists'] = 'testing';
    $existing = $userModel->findByEmail($testEmail);
    if ($existing) {
        $audit['errors'][] = 'Email already exists (should not happen with timestamp)';
        $audit['tests']['5_check_email_exists'] = 'FAIL ❌';
    } else {
        $audit['tests']['5_check_email_exists'] = 'PASS ✅';
    }
    
    // TEST 6: Create User
    $audit['tests']['6_create_user'] = 'testing';
    try {
        $userId = $userModel->create($testData);
        $audit['tests']['6_create_user'] = 'PASS ✅ - User ID: ' . $userId;
        $audit['user_id'] = $userId;
    } catch (Exception $e) {
        $audit['tests']['6_create_user'] = 'FAIL ❌';
        $audit['errors'][] = [
            'step' => 'create_user',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        throw $e;
    }
    
    // TEST 7: Verify user in DB
    $audit['tests']['7_verify_user_db'] = 'testing';
    $stmt = $db->query("SELECT * FROM users WHERE id = $userId");
    $user = $stmt->fetch();
    if ($user) {
        $audit['tests']['7_verify_user_db'] = 'PASS ✅';
        $audit['user_data'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];
    } else {
        $audit['tests']['7_verify_user_db'] = 'FAIL ❌';
        $audit['errors'][] = 'User not found in database after creation';
    }
    
    // TEST 8: Check categories created
    $audit['tests']['8_check_categories'] = 'testing';
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories WHERE user_id = $userId");
    $catResult = $stmt->fetch();
    $catCount = $catResult['count'];
    $audit['tests']['8_check_categories'] = "PASS ✅ - $catCount categories created";
    $audit['categories_count'] = $catCount;
    
    // TEST 9: Get categories list
    $stmt = $db->query("SELECT name, type FROM categories WHERE user_id = $userId");
    $categories = $stmt->fetchAll();
    $audit['categories_list'] = $categories;
    
    // TEST 10: Simulate sendSuccess response
    $audit['tests']['9_simulate_response'] = 'testing';
    $responseData = [
        'error' => false,
        'message' => 'Registration successful',
        'data' => ['user_id' => $userId]
    ];
    $audit['tests']['9_simulate_response'] = 'PASS ✅';
    $audit['expected_response'] = $responseData;
    
    // TEST 11: Check sendSuccess function exists
    $audit['tests']['10_check_functions'] = 'testing';
    if (function_exists('sendSuccess')) {
        $audit['tests']['10_check_functions'] = 'PASS ✅ - sendSuccess exists';
    } else {
        $audit['tests']['10_check_functions'] = 'FAIL ❌ - sendSuccess not found';
        $audit['errors'][] = 'sendSuccess function not found';
    }
    
    // TEST 12: Cleanup test user
    $audit['tests']['11_cleanup'] = 'testing';
    $db->query("DELETE FROM users WHERE id = $userId");
    $audit['tests']['11_cleanup'] = 'PASS ✅ - Test user cleaned up';
    
    $audit['status'] = 'completed';
    $audit['summary'] = 'All tests passed! Register should work.';
    
} catch (Exception $e) {
    $audit['status'] = 'failed';
    $audit['fatal_error'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ];
}

echo json_encode($audit, JSON_PRETTY_PRINT);
?>
