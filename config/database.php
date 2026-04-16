<?php
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> origin/master
define('DB_HOST','localhost');define('DB_NAME','nexoatm_db');define('DB_USER','root');define('DB_PASS','');define('DB_CHARSET','utf8mb4');
class Database{private static $i=null;private $pdo;
private function __construct(){$this->pdo=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}
public static function getInstance(){if(!self::$i)self::$i=new self();return self::$i;}
public function getConnection(){return $this->pdo;}}
function db(){return Database::getInstance()->getConnection();}
function jsonResponse($d,$c=200){http_response_code($c);header('Content-Type:application/json;charset=utf-8');echo json_encode($d,JSON_UNESCAPED_UNICODE);exit;}
function auditLog($l,$m,$cid=null){try{db()->prepare("INSERT INTO audit_log(level,message,ip_address,card_id)VALUES(?,?,?,?)")->execute([$l,$m,$_SERVER['REMOTE_ADDR']??'127.0.0.1',$cid]);}catch(Exception $e){}}
function generateTxCode(){return 'TX'.strtoupper(base_convert(time(),10,36)).strtoupper(substr(bin2hex(random_bytes(3)),0,4));}
<<<<<<< HEAD
=======
=======
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
>>>>>>> b7c1a06386a779af52f4d1b04e9e4731c2491363
>>>>>>> origin/master
