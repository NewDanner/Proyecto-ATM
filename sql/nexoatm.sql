-- ═══════════════════════════════════════════════════════════════
-- NexoATM — Base de Datos MySQL (XAMPP)
-- Plataforma Web Universal para la Gestión Centralizada
-- de Transacciones Bancarias en Cajeros Automáticos
-- ═══════════════════════════════════════════════════════════════
-- TEORÍA DE DECISIONES APLICADA:
-- Se incluye la tabla 'decision_log' que registra cada proceso
-- de toma de decisión del sistema (árboles de decisión,
-- criterios evaluados, pesos y resultado final).
-- ═══════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS nexoatm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexoatm_db;

-- ─── BANCOS ───
CREATE TABLE IF NOT EXISTS banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    short_name VARCHAR(30) NOT NULL,
    primary_color VARCHAR(7) NOT NULL,
    secondary_color VARCHAR(7) NOT NULL,
    accent_color VARCHAR(7) NOT NULL,
    logo_text VARCHAR(10) NOT NULL,
    slogan VARCHAR(150) DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── REDES DE TARJETAS ───
CREATE TABLE IF NOT EXISTS card_networks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    prefix_pattern VARCHAR(10) DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB;

-- ─── CLIENTES ───
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ci VARCHAR(20) NOT NULL UNIQUE COMMENT 'Carnet de Identidad',
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    email VARCHAR(120) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    preferred_lang ENUM('es','en') DEFAULT 'es',
    status ENUM('active','blocked','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── CUENTAS ───
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    bank_id INT NOT NULL,
    account_type ENUM('savings','checking') DEFAULT 'savings',
    currency ENUM('BOB','USD') DEFAULT 'BOB',
    balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    daily_limit DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
    withdrawn_today DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active','blocked','closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (bank_id) REFERENCES banks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── TARJETAS ───
CREATE TABLE IF NOT EXISTS cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_number VARCHAR(19) NOT NULL UNIQUE,
    account_id INT NOT NULL,
    network_id INT NOT NULL,
    holder_name VARCHAR(100) NOT NULL,
    expiry_date VARCHAR(5) NOT NULL,
    pin_hash VARCHAR(255) NOT NULL,
    card_status ENUM('active','blocked','expired','lost') DEFAULT 'active',
    pin_attempts INT DEFAULT 0,
    max_pin_attempts INT DEFAULT 3,
    blocked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (network_id) REFERENCES card_networks(id)
) ENGINE=InnoDB;

-- ─── TRANSACCIONES ───
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tx_code VARCHAR(20) NOT NULL UNIQUE,
    card_id INT NOT NULL,
    account_id INT NOT NULL,
    bank_id INT NOT NULL,
    tx_type ENUM('withdrawal','deposit','transfer','balance_inquiry','pin_change') NOT NULL,
    amount DECIMAL(15,2) DEFAULT NULL,
    balance_before DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    target_account_id INT DEFAULT NULL COMMENT 'Para transferencias',
    description VARCHAR(255) DEFAULT NULL,
    status ENUM('completed','failed','cancelled','pending') DEFAULT 'completed',
    atm_id VARCHAR(30) DEFAULT 'ATM-NEXO-001',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (bank_id) REFERENCES banks(id),
    FOREIGN KEY (target_account_id) REFERENCES accounts(id)
) ENGINE=InnoDB;

-- ─── LOG DE AUDITORÍA (RNF-15) ───
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('info','success','warning','error') DEFAULT 'info',
    message TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    card_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════════════════
-- TABLA DE TEORÍA DE DECISIONES
-- Registra cada evaluación de decisión del sistema:
-- qué criterios se evaluaron, con qué peso, y el resultado.
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS decision_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    decision_type VARCHAR(50) NOT NULL COMMENT 'Tipo: pin_auth, withdrawal_auth, deposit_auth, transfer_auth',
    card_id INT DEFAULT NULL,
    criteria_evaluated JSON NOT NULL COMMENT 'Array de criterios [{name, value, weight, passed}]',
    total_score DECIMAL(5,2) NOT NULL COMMENT 'Puntuación total ponderada (0-100)',
    threshold DECIMAL(5,2) NOT NULL COMMENT 'Umbral mínimo para aprobar',
    decision_result ENUM('APPROVED','DENIED') NOT NULL,
    denial_reason VARCHAR(255) DEFAULT NULL,
    processing_time_ms INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─── CONFIGURACIÓN ATM ───
CREATE TABLE IF NOT EXISTS atm_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(50) NOT NULL UNIQUE,
    config_value VARCHAR(255) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════════════════
-- DATOS DE EJEMPLO
-- ═══════════════════════════════════════════════════════════════

-- 3 Bancos de Bolivia con diseños distintos
INSERT INTO banks (code, name, short_name, primary_color, secondary_color, accent_color, logo_text, slogan) VALUES
('bmsc', 'Banco Mercantil Santa Cruz', 'Mercantil', '#003876', '#005baa', '#00a3e0', 'BMSc', 'Tu banco de confianza'),
('bnb',  'Banco Nacional de Bolivia',  'BNB',       '#004d25', '#006633', '#00cc66', 'BNB',  'El banco de todos los bolivianos'),
('bu',   'Banco Unión',                'Unión',     '#c41230', '#e01540', '#ff6b35', 'BU',   'Unidos por Bolivia');

-- Redes de tarjetas
INSERT INTO card_networks (code, name, prefix_pattern) VALUES
('visa',       'Visa',             '4'),
('mastercard', 'Mastercard',       '5'),
('amex',       'American Express', '3');

-- Clientes
INSERT INTO customers (ci, first_name, last_name, email, phone, preferred_lang) VALUES
('7654321', 'Carlos',        'Mendoza Quispe',  'carlos.mendoza@email.com',  '+591 72345678', 'es'),
('8765432', 'Ana María',     'García López',    'ana.garcia@email.com',      '+591 71234567', 'es'),
('9876543', 'Roberto',       'Flores Mamani',   'roberto.flores@email.com',  '+591 76543210', 'es'),
('1234567', 'María Elena',   'Quispe Condori',  'maria.quispe@email.com',    '+591 70123456', 'es'),
('2345678', 'Luis Fernando', 'Huanca Choque',   'luis.huanca@email.com',     '+591 79876543', 'en'),
('3456789', 'Patricia',      'Morales Vargas',  'patricia.morales@email.com','+591 73456789', 'es');

-- Cuentas — Banco Mercantil Santa Cruz
INSERT INTO accounts (account_number, customer_id, bank_id, account_type, currency, balance, daily_limit) VALUES
('1001-0001-2024', 1, 1, 'savings',  'BOB', 15420.50, 5000.00),
('1001-0002-2024', 2, 1, 'checking', 'BOB', 8750.00,  3000.00);

-- Cuentas — Banco Nacional de Bolivia
INSERT INTO accounts (account_number, customer_id, bank_id, account_type, currency, balance, daily_limit) VALUES
('2001-0001-2024', 3, 2, 'savings', 'BOB', 52340.75, 10000.00),
('2001-0002-2024', 4, 2, 'savings', 'USD', 3200.00,  2000.00);

-- Cuentas — Banco Unión
INSERT INTO accounts (account_number, customer_id, bank_id, account_type, currency, balance, daily_limit) VALUES
('3001-0001-2024', 5, 3, 'savings',  'BOB', 24100.00, 8000.00),
('3001-0002-2024', 6, 3, 'checking', 'BOB', 6890.25,  4000.00);

-- Tarjetas (PIN en texto para demo; en producción usar password_hash)
-- Carlos: 1234 | Ana: 5678 | Roberto: 9012
-- María: 3456 | Luis: 7890 | Patricia: 2468
INSERT INTO cards (card_number, account_id, network_id, holder_name, expiry_date, pin_hash) VALUES
('4532 8912 3456 7890', 1, 1, 'CARLOS MENDOZA Q.',   '12/27', '1234'),
('5412 7534 9012 3456', 2, 2, 'ANA M. GARCIA L.',    '08/26', '5678'),
('3782 8224 6310 005',  3, 3, 'ROBERTO FLORES M.',   '03/28', '9012'),
('4916 3389 0145 6723', 4, 1, 'MARIA E. QUISPE C.',  '11/26', '3456'),
('5234 6789 0123 4567', 5, 2, 'LUIS F. HUANCA C.',   '06/27', '7890'),
('4111 2233 4455 6677', 6, 1, 'PATRICIA MORALES V.', '09/28', '2468');

-- Configuración ATM
INSERT INTO atm_config (config_key, config_value, description) VALUES
('atm_id',            'ATM-NEXO-001',           'Identificador del cajero'),
('location',          'La Paz - Sede Central',  'Ubicación física'),
('default_currency',  'BOB',                    'Moneda por defecto'),
('max_pin_attempts',  '3',                      'Intentos máximos de PIN'),
('lock_time_minutes', '30',                     'Tiempo de bloqueo en minutos'),
('inactivity_seconds','120',                    'Segundos de inactividad antes de cerrar sesión'),
('default_language',  'es',                     'Idioma por defecto (es/en)');
