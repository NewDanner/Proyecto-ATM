<?php
// deposit.php - Realizar depósito
require_once 'config.php';

$usuario_id = requiereAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$monto = floatval($data['monto'] ?? 0);

if ($monto <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Monto inválido']);
    exit();
}

$pdo = getDB();
$pdo->beginTransaction();

try {
    // Actualizar saldo
    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
    $stmt->execute([$monto, $usuario_id]);

    // Registrar movimiento
    $stmt = $pdo->prepare("INSERT INTO movimientos (usuario_id, tipo, monto) VALUES (?, 'deposito', ?)");
    $stmt->execute([$usuario_id, $monto]);

    $pdo->commit();

    // Obtener nuevo saldo
    $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $nuevoSaldo = $stmt->fetchColumn();

    registrarAuditoria($pdo, $usuario_id, 'DEPOSITO_EXITOSO', "Monto: $monto. Nuevo saldo: $nuevoSaldo");

    echo json_encode([
        'success'     => true,
        'mensaje'     => 'Depósito exitoso',
        'nuevo_saldo' => floatval($nuevoSaldo)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar el depósito']);
}
?>
