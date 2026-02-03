<?php
// backend/middleware/AuthMiddleware.php

class AuthMiddleware {
    
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function check() {
        self::init();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'profile_photo' => $user['profile_photo'] ?? '',
            'currency' => $user['currency'] ?? 'IDR',
            'theme' => $user['theme'] ?? 'light'
        ];
        
        // Set cookie untuk remember me (optional, 30 hari) - suppress warning jika sudah ada output
        @setcookie('user_token', base64_encode($user['id']), time() + (86400 * 30), '/');
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
            http_response_code(401);
            echo json_encode(['error' => true, 'message' => 'Unauthorized. Please login first.']);
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