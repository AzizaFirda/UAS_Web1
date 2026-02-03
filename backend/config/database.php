<?php
// backend/config/database.php

// Helper functions - MUST be defined BEFORE class to ensure they load
function sendJSON($data, $statusCode = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function sendError($message, $code = 400) {
    http_response_code($code);
    sendJSON(['error' => true, 'message' => $message], $code);
}

function sendSuccess($data = null, $message = 'Success') {
    sendJSON(['error' => false, 'message' => $message, 'data' => $data]);
}

function getDB() {
    $database = new Database();
    return $database->connect();
}

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn = null;

    public function __construct() {
        // Detect environment
        $is_localhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:80', 'localhost:3000']);
        
        if ($is_localhost) {
            // Development (localhost)
            $this->host = 'localhost';
            $this->db_name = 'finance_manager';
            $this->username = 'root';
            $this->password = '';
        } else {
            // Production (hosting)
            $this->host = 'localhost';
            $this->db_name = 'rdyaazzw_db_finance';
            $this->username = 'rdyaazzw_firda';
            $this->password = 'n4QnME&-c2X^fSu{';
        }
    }

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            // Log error to file
            error_log("Database Connection Error: " . $e->getMessage());
            error_log("Host: " . $this->host . ", DB: " . $this->db_name . ", User: " . $this->username);
            
            // Don't exit here - let helper functions be defined
            http_response_code(500);
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
        
        return $this->conn;
    }

    public function close() {
        $this->conn = null;
    }
}
?>
