<?php
/**
 * BBR Fragrance - Database Connection
 * Singleton PDO connection to MySQL
 */

class Database {
    private static $instance = null;
    private $connection;

    private $host = 'localhost';
    private $dbname = 'bbr_fragance';
    private $username = 'root';
    private $password = 'raffy1992';
    private $charset = 'utf8mb4';

    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error de conexion a la base de datos']);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Prevent cloning
    private function __clone() {}
}

function getDB() {
    return Database::getInstance()->getConnection();
}
