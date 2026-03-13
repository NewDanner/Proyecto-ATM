<?php
// transactions.php - Historial de movimientos
require_once 'config.php';

$usuario_id = requiereAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT tipo, monto, fecha FROM movimientos WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 10");
$stmt->execute([$usuario_id]);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

registrarAuditoria($pdo, $usuario_id, 'CONSULTA_MOVIMIENTOS', "Consultó historial de movimientos");

echo json_encode($movimientos);
?>
