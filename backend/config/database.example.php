<?php
// backend/config/database.example.php
// Copy file ini menjadi database.php dan sesuaikan kredensial

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
            // GANTI DENGAN KREDENSIAL DATABASE HOSTING ANDA
            $this->host = 'localhost';
            $this->db_name = 'YOUR_DATABASE_NAME';
            $this->username = 'YOUR_DATABASE_USER';
            $this->password = 'YOUR_DATABASE_PASSWORD';
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
            error_log("Database Connection Error: " . $e->getMessage());
            error_log("Host: " . $this->host . ", DB: " . $this->db_name . ", User: " . $this->username);
            
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Database connection failed'
            ]);
            exit();
        }

        return $this->conn;
    }
}
