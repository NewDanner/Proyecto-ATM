<?php
/**
 * setup_users.php
 * Ejecuta este script UNA VEZ para crear la base de datos e insertar usuarios con PIN hasheado.
 * Uso: php setup_users.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'atm_db');

try {
    // Conectar sin base de datos para crearla
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);

    // Crear tablas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            tarjeta VARCHAR(16) UNIQUE NOT NULL,
            pin_hash VARCHAR(255) NOT NULL,
            saldo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            intentos_fallidos INT NOT NULL DEFAULT 0,
            bloqueado_hasta DATETIME DEFAULT NULL
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS movimientos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            tipo ENUM('retiro', 'deposito') NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT DEFAULT NULL,
            accion VARCHAR(100) NOT NULL,
            detalle TEXT,
            ip VARCHAR(45),
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");

    // Insertar usuarios con PIN hasheado
    $usuarios = [
        ['Juan Pérez',  '1234567890123456', '1234', 1500.50],
        ['María Gómez', '6543210987654321', '5678', 3200.00],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO usuarios (nombre, tarjeta, pin_hash, saldo) VALUES (?, ?, ?, ?)");

    foreach ($usuarios as $u) {
        $hash = password_hash($u[2], PASSWORD_DEFAULT);
        $stmt->execute([$u[0], $u[1], $hash, $u[3]]);
        echo "Usuario '{$u[0]}' insertado (tarjeta: {$u[1]}, PIN: {$u[2]})\n";
    }

    echo "\n✅ Base de datos configurada correctamente.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
