<?php
// backend/middleware/AuthMiddleware.php

class AuthMiddleware {
    
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure session for better compatibility
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Lax');
            
            // Set session cookie path to root
            session_set_cookie_params([
                'lifetime' => 86400, // 24 hours
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
            
            // Regenerate session ID for security (but not on every request)
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }
    
    public static function check() {
        self::init();
        $isValid = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        
        // Debug logging
        error_log("Session check - user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . ", valid: " . ($isValid ? 'YES' : 'NO'));
        
        return $isValid;
    }
    
    public static function getUserId() {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUser() {
        self::init();
        return $_SESSION['user'] ?? null;
    }
    
    public static function login($user) {
        self::init();
        
        // Clear any previous session data
        $_SESSION = array();
        
        // Set new session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'profile_photo' => $user['profile_photo'] ?? '',
            'currency' => $user['currency'] ?? 'IDR',
            'theme' => $user['theme'] ?? 'light'
        ];
        $_SESSION['login_time'] = time();
        
        // Force session write
        session_write_close();
        session_start();
        
        // Debug logging
        error_log("User logged in - ID: " . $user['id'] . ", Session ID: " . session_id());
        
        // Set cookie untuk remember me (optional, 30 hari)
        @setcookie('user_token', base64_encode($user['id']), [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    public static function logout() {
        self::init();
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Hapus cookie
        setcookie('user_token', '', time() - 3600, '/');
        
        session_destroy();
    }
    
    public static function requireAuth() {
        if (!self::check()) {
            error_log("Auth required but session invalid. Session ID: " . session_id() . ", Session data: " . json_encode($_SESSION));
            http_response_code(401);
            echo json_encode([
                'error' => true, 
                'message' => 'Unauthorized. Please login first.',
                'debug' => [
                    'session_id' => session_id(),
                    'has_user_id' => isset($_SESSION['user_id']),
                    'cookies' => array_keys($_COOKIE)
                ]
            ]);
            exit();
        }
    }
    
    public static function requireGuest() {
        if (self::check()) {
            header('Location: /frontend/pages/dashboard.html');
            exit();
        }
    }
}
?>