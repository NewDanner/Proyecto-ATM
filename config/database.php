<?php
// ═══════════════════════════════════════════════════════════════
// NexoATM — Configuración de Base de Datos (XAMPP)
// ═══════════════════════════════════════════════════════════════

define('DB_HOST', 'localhost');
define('DB_NAME', 'nexoatm_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
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
        return $this->pdo;
    }
}

function db() {
    return Database::getInstance()->getConnection();
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function auditLog($level, $message, $cardId = null) {
    try {
        $stmt = db()->prepare("INSERT INTO audit_log (level, message, ip_address, card_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$level, $message, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $cardId]);
    } catch (Exception $e) { /* silent */ }
}

function generateTxCode() {
    return 'TX' . strtoupper(base_convert(time(), 10, 36)) . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}
