<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> origin/master
CREATE DATABASE IF NOT EXISTS nexoatm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexoatm_db;

CREATE TABLE banks (
    id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(20) NOT NULL UNIQUE, name VARCHAR(100) NOT NULL,
    short_name VARCHAR(30) NOT NULL, primary_color VARCHAR(7) NOT NULL, secondary_color VARCHAR(7) NOT NULL,
    accent_color VARCHAR(7) NOT NULL, logo_text VARCHAR(10) NOT NULL, slogan VARCHAR(150) DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE card_networks (
    id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(20) NOT NULL UNIQUE, name VARCHAR(50) NOT NULL,
    prefix_pattern VARCHAR(10) DEFAULT NULL, status ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY, ci VARCHAR(20) NOT NULL UNIQUE, first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL, email VARCHAR(120) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL,
    security_question VARCHAR(255) DEFAULT NULL, security_answer VARCHAR(255) DEFAULT NULL,
    preferred_lang ENUM('es','en') DEFAULT 'es', status ENUM('active','blocked','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY, account_number VARCHAR(20) NOT NULL UNIQUE, customer_id INT NOT NULL,
    bank_id INT NOT NULL, account_type ENUM('savings','checking') DEFAULT 'savings',
    balance_bob DECIMAL(15,2) NOT NULL DEFAULT 0.00, balance_usd DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    daily_limit DECIMAL(10,2) NOT NULL DEFAULT 5000.00, custom_limit DECIMAL(10,2) DEFAULT NULL,
    withdrawn_today DECIMAL(10,2) NOT NULL DEFAULT 0.00, status ENUM('active','blocked','closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
<<<<<<< HEAD
=======
=======
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
>>>>>>> b7c1a06386a779af52f4d1b04e9e4731c2491363
>>>>>>> origin/master
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (bank_id) REFERENCES banks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> origin/master
CREATE TABLE cards (
    id INT AUTO_INCREMENT PRIMARY KEY, card_number VARCHAR(19) NOT NULL UNIQUE, account_id INT NOT NULL,
    network_id INT NOT NULL, holder_name VARCHAR(100) NOT NULL, expiry_date VARCHAR(5) NOT NULL,
    pin_hash VARCHAR(255) NOT NULL, card_status ENUM('active','blocked','expired','lost') DEFAULT 'active',
    pin_attempts INT DEFAULT 0, max_pin_attempts INT DEFAULT 3, blocked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
<<<<<<< HEAD
=======
=======
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
>>>>>>> b7c1a06386a779af52f4d1b04e9e4731c2491363
>>>>>>> origin/master
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (network_id) REFERENCES card_networks(id)
) ENGINE=InnoDB;

<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> origin/master
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY, tx_code VARCHAR(20) NOT NULL UNIQUE, card_id INT NOT NULL,
    account_id INT NOT NULL, bank_id INT NOT NULL,
    tx_type ENUM('withdrawal','deposit','transfer','balance_inquiry','pin_change','donation') NOT NULL,
    currency ENUM('BOB','USD') DEFAULT 'BOB', amount DECIMAL(15,2) DEFAULT NULL,
    balance_before DECIMAL(15,2) NOT NULL, balance_after DECIMAL(15,2) NOT NULL,
    target_account_id INT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL,
    status ENUM('completed','failed','cancelled','pending') DEFAULT 'completed',
    atm_id VARCHAR(30) DEFAULT 'ATM-NEXO-001', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id), FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (bank_id) REFERENCES banks(id)
) ENGINE=InnoDB;

CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT NOT NULL, target_account_id INT NOT NULL,
    alias VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (target_account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    UNIQUE KEY (customer_id, target_account_id)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY, level ENUM('info','success','warning','error') DEFAULT 'info',
    message TEXT NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, card_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE decision_log (
    id INT AUTO_INCREMENT PRIMARY KEY, decision_type VARCHAR(50) NOT NULL, card_id INT DEFAULT NULL,
    criteria_evaluated JSON NOT NULL, total_score DECIMAL(5,2) NOT NULL, threshold DECIMAL(5,2) NOT NULL,
    decision_result ENUM('APPROVED','DENIED') NOT NULL, denial_reason VARCHAR(255) DEFAULT NULL,
    processing_time_ms INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE atm_config (
    id INT AUTO_INCREMENT PRIMARY KEY, config_key VARCHAR(50) NOT NULL UNIQUE,
    config_value VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- DATOS
INSERT INTO banks (code,name,short_name,primary_color,secondary_color,accent_color,logo_text,slogan) VALUES
('bmsc','Banco Mercantil Santa Cruz','Mercantil','#003876','#005baa','#00a3e0','BMSc','Tu banco de confianza'),
('bnb','Banco Nacional de Bolivia','BNB','#004d25','#006633','#00cc66','BNB','El banco de todos los bolivianos'),
('bu','Banco Unión','Unión','#c41230','#e01540','#ff6b35','BU','Unidos por Bolivia');

INSERT INTO card_networks (code,name,prefix_pattern) VALUES ('visa','Visa','4'),('mastercard','Mastercard','5'),('amex','American Express','3');

INSERT INTO customers (ci,first_name,last_name,security_question,security_answer,preferred_lang) VALUES
('7654321','Carlos','Mendoza Quispe','¿Nombre de su primera mascota?','firulais','es'),
('8765432','Ana María','García López','¿Ciudad donde nació?','cochabamba','es'),
('9876543','Roberto','Flores Mamani','¿Nombre de su madre?','carmen','es'),
('1234567','María Elena','Quispe Condori','¿Color favorito?','azul','es'),
('2345678','Luis Fernando','Huanca Choque','¿Nombre de su primera mascota?','rocky','en'),
('3456789','Patricia','Morales Vargas','¿Ciudad donde nació?','la paz','es');

INSERT INTO accounts (account_number,customer_id,bank_id,account_type,balance_bob,balance_usd,daily_limit) VALUES
('1001-0001-2024',1,1,'savings',15420.50,2200.00,5000.00),
('1001-0002-2024',2,1,'checking',8750.00,1250.00,3000.00),
('2001-0001-2024',3,2,'savings',52340.75,7500.00,10000.00),
('2001-0002-2024',4,2,'savings',18900.00,2700.00,2000.00),
('3001-0001-2024',5,3,'savings',24100.00,3450.00,8000.00),
('3001-0002-2024',6,3,'checking',6890.25,985.00,4000.00);

INSERT INTO cards (card_number,account_id,network_id,holder_name,expiry_date,pin_hash) VALUES
('4532 8912 3456 7890',1,1,'CARLOS MENDOZA Q.','12/27','1234'),
('5412 7534 9012 3456',2,2,'ANA M. GARCIA L.','08/26','5678'),
('3782 8224 6310 005',3,3,'ROBERTO FLORES M.','03/28','9012'),
('4916 3389 0145 6723',4,1,'MARIA E. QUISPE C.','11/26','3456'),
('5234 6789 0123 4567',5,2,'LUIS F. HUANCA C.','06/27','7890'),
('4111 2233 4455 6677',6,1,'PATRICIA MORALES V.','09/28','2468');

INSERT INTO favorites (customer_id,target_account_id,alias) VALUES (1,3,'Roberto BNB'),(1,5,'Luis Unión'),(3,1,'Carlos Mercantil');

INSERT INTO atm_config (config_key,config_value,description) VALUES
('atm_id','ATM-NEXO-001','Identificador del cajero'),('location','La Paz - Sede Central','Ubicación'),
('inactivity_seconds','120','Segundos inactividad'),('large_transfer_limit','2000','Monto que requiere pregunta de seguridad');
<<<<<<< HEAD

-- ═══ TEORÍA DE COLAS ═══
-- Registra cada sesión de usuario (llegada, inicio servicio, fin servicio)
-- para calcular métricas M/M/1 en tiempo real
CREATE TABLE queue_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT DEFAULT NULL,
    session_id VARCHAR(30) NOT NULL UNIQUE,
    arrival_time DATETIME NOT NULL COMMENT 'Momento que el usuario llega (inserta tarjeta)',
    service_start DATETIME DEFAULT NULL COMMENT 'Momento que el usuario es autenticado',
    service_end DATETIME DEFAULT NULL COMMENT 'Momento que retira la tarjeta',
    wait_time_sec INT DEFAULT 0 COMMENT 'Segundos esperando (arrival→service_start)',
    service_time_sec INT DEFAULT 0 COMMENT 'Segundos de servicio (service_start→service_end)',
    total_time_sec INT DEFAULT 0 COMMENT 'Tiempo total en sistema',
    transactions_count INT DEFAULT 0 COMMENT 'Cantidad de operaciones realizadas',
    status ENUM('waiting','in_service','completed','abandoned') DEFAULT 'waiting',
    atm_id VARCHAR(30) DEFAULT 'ATM-NEXO-001',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Datos históricos de ejemplo para que el dashboard muestre métricas desde el inicio
INSERT INTO queue_log (card_id, session_id, arrival_time, service_start, service_end, wait_time_sec, service_time_sec, total_time_sec, transactions_count, status) VALUES
(1, 'SES-001', '2026-03-31 08:05:00', '2026-03-31 08:05:12', '2026-03-31 08:08:45', 12, 213, 225, 2, 'completed'),
(2, 'SES-002', '2026-03-31 08:15:30', '2026-03-31 08:15:40', '2026-03-31 08:17:20', 10, 100, 110, 1, 'completed'),
(3, 'SES-003', '2026-03-31 09:02:00', '2026-03-31 09:02:08', '2026-03-31 09:06:50', 8, 282, 290, 3, 'completed'),
(4, 'SES-004', '2026-03-31 09:30:00', '2026-03-31 09:30:15', '2026-03-31 09:32:10', 15, 115, 130, 1, 'completed'),
(1, 'SES-005', '2026-03-31 10:10:00', '2026-03-31 10:10:10', '2026-03-31 10:14:30', 10, 260, 270, 2, 'completed'),
(5, 'SES-006', '2026-03-31 10:45:00', '2026-03-31 10:45:20', '2026-03-31 10:48:00', 20, 160, 180, 1, 'completed'),
(6, 'SES-007', '2026-03-31 11:20:00', '2026-03-31 11:20:05', '2026-03-31 11:25:15', 5, 310, 315, 4, 'completed'),
(2, 'SES-008', '2026-03-31 11:55:00', '2026-03-31 11:55:12', '2026-03-31 11:57:45', 12, 153, 165, 2, 'completed'),
(3, 'SES-009', '2026-03-31 13:05:00', '2026-03-31 13:05:08', '2026-03-31 13:07:30', 8, 142, 150, 1, 'completed'),
(4, 'SES-010', '2026-03-31 14:00:00', '2026-03-31 14:00:18', '2026-03-31 14:03:50', 18, 212, 230, 2, 'completed'),
(5, 'SES-011', '2026-03-31 15:30:00', '2026-03-31 15:30:10', '2026-03-31 15:33:20', 10, 190, 200, 1, 'completed'),
(1, 'SES-012', '2026-03-31 16:15:00', '2026-03-31 16:15:06', '2026-03-31 16:19:40', 6, 274, 280, 3, 'completed');
=======
=======
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
>>>>>>> b7c1a06386a779af52f4d1b04e9e4731c2491363
>>>>>>> origin/master
