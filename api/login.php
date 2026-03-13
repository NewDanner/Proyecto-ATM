<?php
// login.php - Inicio de sesión con límite de intentos
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$tarjeta = $data['tarjeta'] ?? '';
$pin     = $data['pin'] ?? '';

if (empty($tarjeta) || empty($pin)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tarjeta y PIN requeridos']);
    exit();
}

$pdo = getDB();

// Buscar usuario por número de tarjeta
$stmt = $pdo->prepare("SELECT id, nombre, saldo, pin_hash, intentos_fallidos, bloqueado_hasta FROM usuarios WHERE tarjeta = ?");
$stmt->execute([$tarjeta]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    registrarAuditoria($pdo, null, 'LOGIN_FALLIDO', "Tarjeta no encontrada: $tarjeta");
    http_response_code(401);
    echo json_encode(['error' => 'Tarjeta o PIN incorrectos']);
    exit();
}

// Verificar si la cuenta está bloqueada
if ($usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time()) {
    $restante = ceil((strtotime($usuario['bloqueado_hasta']) - time()) / 60);
    registrarAuditoria($pdo, $usuario['id'], 'LOGIN_BLOQUEADO', "Cuenta bloqueada, intento rechazado");
    http_response_code(403);
    echo json_encode([
        'error' => "Cuenta bloqueada. Intente en $restante minuto(s).",
        'bloqueado' => true
    ]);
    exit();
}

// Verificar PIN
if (password_verify($pin, $usuario['pin_hash'])) {
    // Login exitoso - resetear intentos
    $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
    $stmt->execute([$usuario['id']]);

    // Iniciar sesión
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];

    registrarAuditoria($pdo, $usuario['id'], 'LOGIN_EXITOSO', "Inicio de sesión correcto");

    echo json_encode([
        'success' => true,
        'usuario' => [
            'id'     => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'saldo'  => floatval($usuario['saldo'])
        ]
    ]);
} else {
    // PIN incorrecto - incrementar intentos
    $intentos = $usuario['intentos_fallidos'] + 1;
    $bloqueado = null;

    if ($intentos >= MAX_INTENTOS) {
        $bloqueado = date('Y-m-d H:i:s', strtotime('+' . BLOQUEO_MINUTOS . ' minutes'));
        $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?");
        $stmt->execute([$intentos, $bloqueado, $usuario['id']]);

        registrarAuditoria($pdo, $usuario['id'], 'CUENTA_BLOQUEADA', "Bloqueada por $intentos intentos fallidos");

        http_response_code(403);
        echo json_encode([
            'error' => "Cuenta bloqueada por " . BLOQUEO_MINUTOS . " minutos tras $intentos intentos fallidos.",
            'bloqueado' => true
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?");
        $stmt->execute([$intentos, $usuario['id']]);

        $restantes = MAX_INTENTOS - $intentos;
        registrarAuditoria($pdo, $usuario['id'], 'LOGIN_FALLIDO', "PIN incorrecto. Intento $intentos de " . MAX_INTENTOS);

        http_response_code(401);
        echo json_encode([
            'error' => "PIN incorrecto. Le quedan $restantes intento(s).",
            'intentos_restantes' => $restantes
        ]);
    }
}
?>
