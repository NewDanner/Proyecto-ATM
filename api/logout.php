<?php
// logout.php - Cierre de sesión
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$pdo = getDB();

if (isset($_SESSION['usuario_id'])) {
    registrarAuditoria($pdo, $_SESSION['usuario_id'], 'LOGOUT', 'Cierre de sesión');
}

session_destroy();
echo json_encode(['success' => true]);
?>
