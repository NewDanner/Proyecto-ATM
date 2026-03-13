-- database.sql
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS atm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atm_db;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tarjeta VARCHAR(16) UNIQUE NOT NULL,
    pin_hash VARCHAR(255) NOT NULL,
    saldo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    intentos_fallidos INT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- Tabla de movimientos
CREATE TABLE IF NOT EXISTS movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('retiro', 'deposito') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de logs de auditoría
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    accion VARCHAR(100) NOT NULL,
    detalle TEXT,
    ip VARCHAR(45),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insertar usuarios de ejemplo con PIN hasheado
-- PIN de Juan: 1234 | PIN de María: 5678
-- NOTA: Ejecuta el script setup_users.php para insertar usuarios con hash correcto.
--       Los valores aquí son placeholders.
INSERT INTO usuarios (nombre, tarjeta, pin_hash, saldo) VALUES
('Juan Pérez', '1234567890123456', '$2y$10$placeholder_hash_juan', 1500.50),
('María Gómez', '6543210987654321', '$2y$10$placeholder_hash_maria', 3200.00);
