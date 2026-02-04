<?php
// Simple session test
header('Content-Type: application/json');

// Configure session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

$action = $_GET['action'] ?? 'check';

if ($action === 'set') {
    $_SESSION['test'] = 'Session is working!';
    $_SESSION['timestamp'] = time();
    echo json_encode([
        'status' => 'success',
        'message' => 'Session set',
        'session_id' => session_id(),
        'data' => $_SESSION
    ]);
} elseif ($action === 'check') {
    echo json_encode([
        'status' => 'success',
        'message' => 'Session check',
        'session_id' => session_id(),
        'has_test' => isset($_SESSION['test']),
        'data' => $_SESSION ?? [],
        'cookie_params' => session_get_cookie_params(),
        'server_info' => [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
            'HTTPS' => $_SERVER['HTTPS'] ?? 'off',
            'session_save_path' => session_save_path()
        ]
    ]);
} elseif ($action === 'clear') {
    session_destroy();
    echo json_encode([
        'status' => 'success',
        'message' => 'Session cleared'
    ]);
}
?>
