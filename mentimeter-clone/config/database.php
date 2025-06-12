<?php
// Database configuration
define('DB_HOST', '127.0.1'); // XAMPP default host
define('DB_NAME', 'mentimeter_db');
define('DB_USER', 'root');
define('DB_PASS', 'prem141045'); // XAMPP default มีรหัสผ่านเป็นค่าว่าง

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Global database connection
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}
?>