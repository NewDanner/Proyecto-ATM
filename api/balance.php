<?php
// balance.php - Consultar saldo
require_once 'config.php';

$usuario_id = requiereAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$saldo = $stmt->fetchColumn();

registrarAuditoria($pdo, $usuario_id, 'CONSULTA_SALDO', "Saldo consultado: $saldo");

echo json_encode(['saldo' => floatval($saldo)]);
?>
