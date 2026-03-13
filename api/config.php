<?php
// config.php - Configuración general y conexión a BD

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'atm_db');

// Límite de intentos de login
define('MAX_INTENTOS', 3);
define('BLOQUEO_MINUTOS', 5);

// Iniciar sesión
session_start();

// Cabeceras para API JSON y CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexión a la base de datos
function getDB() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
        exit();
    }
}

// Verificar autenticación
function requiereAutenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado. Inicie sesión nuevamente.']);
        exit();
    }
    return $_SESSION['usuario_id'];
}

// Registrar acción en log de auditoría
function registrarAuditoria($pdo, $usuario_id, $accion, $detalle = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (usuario_id, accion, detalle, ip) VALUES (?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $accion, $detalle, $ip]);
}
?>
