<<<<<<< HEAD
# NexoATM — Cajero Automático Universal

## Instalación XAMPP
1. Copiar `nexoatm/` a `C:\xampp\htdocs\nexoatm\`
2. phpMyAdmin → Importar `sql/nexoatm.sql`
3. Cajero: `http://localhost/nexoatm/index.html`
4. Dashboard: `http://localhost/nexoatm/dashboard.html`

## Cuentas de Prueba
| Cuenta         | Titular            | Banco     | PIN  | Bs       | USD     | Pregunta Seguridad       | Respuesta  |
|----------------|---------------------|-----------|------|----------|---------|--------------------------|------------|
| 1001-0001-2024 | Carlos Mendoza Q.  | Mercantil | 1234 | 15420.50 | 2200.00 | ¿Nombre primera mascota? | firulais   |
| 1001-0002-2024 | Ana M. García L.   | Mercantil | 5678 | 8750.00  | 1250.00 | ¿Ciudad donde nació?     | cochabamba  |
| 2001-0001-2024 | Roberto Flores M.  | BNB       | 9012 | 52340.75 | 7500.00 | ¿Nombre de su madre?     | carmen     |
| 2001-0002-2024 | María E. Quispe C. | BNB       | 3456 | 18900.00 | 2700.00 | ¿Color favorito?         | azul       |
| 3001-0001-2024 | Luis F. Huanca C.  | Unión     | 7890 | 24100.00 | 3450.00 | ¿Nombre primera mascota? | rocky      |
| 3001-0002-2024 | Patricia Morales V.| Unión     | 2468 | 6890.25  | 985.00  | ¿Ciudad donde nació?     | la paz     |

## Funcionalidades Únicas

### Visuales e Interactivas
- **Aspecto de cajero físico** con carcasa, bisel, ranura, teclado con relieve, rejilla de altavoz
- **Animación de tarjeta** al insertar (la tarjeta baja y desaparece)
- **Animación de billetes** al hacer retiro (billetes salen con efecto escalonado)
- **Sonidos del cajero** (beep teclas, éxito, error, dispensar, insertar tarjeta) — Web Audio API
- **Modo oscuro/claro** toggle en tiempo real
- **Modo accesibilidad** (textos extra grandes para personas con discapacidad visual)

### Seguridad Avanzada
- **Pregunta de seguridad** para montos >= 2000 (verificación adicional)
- **Bloqueo automático** tras 3 intentos de PIN fallidos (30 min)
- **Teoría de Decisiones** visualizada en tiempo real durante cada operación
- **Registro de auditoría** completo en base de datos

### Funciones para el Usuario
- **Multi-moneda**: Bolivianos (Bs) y Dólares ($) con saldos independientes
- **Montos específicos de retiro**: 50, 150, 250, 350, 500, 600, 700 + "Otra cantidad"
- **Código QR real** en cada recibo (escaneable con celular) usando librería QRious
- **Impresión de recibo** desde el navegador
- **Límite diario personalizable** por el usuario
- **Cuentas favoritas** para transferencias frecuentes
- **Tutorial interactivo** paso a paso para nuevos usuarios
- **Idioma dual** Español/English (toggle inmediato sin recarga)

### Técnicas que Impresionan
- **Dashboard con Chart.js** — gráficos de barras por hora y doughnut por tipo
- **Motor de Teoría de Decisiones** con criterios ponderados, score y registro JSON
- **Visualización de decisiones en tiempo real** (el usuario ve cómo el sistema evalúa)
- **API REST completa** con 17 endpoints en PHP
- **Transacciones ACID** con BEGIN/COMMIT en MySQL
- **9 tablas** incluyendo `decision_log` y `favorites`
=======
# NexoATM — Plataforma Web Universal para la Gestión Centralizada de Cajeros Automáticos

## Estructura del Proyecto

```
nexoatm/
├── config/
│   └── database.php          ← Conexión PDO a MySQL (XAMPP)
├── api/
│   ├── api.php               ← API principal (12 endpoints)
│   └── decisions.php         ← Motor de Teoría de Decisiones
├── sql/
│   └── nexoatm.sql           ← Schema + datos de ejemplo
├── css/
│   └── atm.css               ← Estilos del cajero
├── js/
│   ├── app.js                ← Lógica principal del ATM
│   └── lang.js               ← Sistema de idiomas (ES/EN)
├── index.html                ← Interfaz del cajero ATM
├── dashboard.html            ← Panel de administración
└── README.md
```

## Instalación en XAMPP

### Paso 1: Copiar el proyecto
Copiar la carpeta `nexoatm` completa dentro de `C:\xampp\htdocs\nexoatm\`

### Paso 2: Crear la base de datos
1. Abrir XAMPP → encender Apache y MySQL
2. Ir a phpMyAdmin: http://localhost/phpmyadmin
3. Importar el archivo `sql/nexoatm.sql`

### Paso 3: Abrir la aplicación
- Cajero ATM: http://localhost/nexoatm/index.html
- Dashboard: http://localhost/nexoatm/dashboard.html

## PINs de Prueba

| Titular              | Banco       | Red  | PIN  |
|----------------------|-------------|------|------|
| Carlos Mendoza Q.    | Mercantil   | Visa | 1234 |
| Ana M. García L.     | Mercantil   | MC   | 5678 |
| Roberto Flores M.    | BNB         | AmEx | 9012 |
| María E. Quispe C.   | BNB         | Visa | 3456 |
| Luis F. Huanca C.    | Unión       | MC   | 7890 |
| Patricia Morales V.  | Unión       | Visa | 2468 |

## Cumplimiento de Requerimientos

### Requerimientos Funcionales

| Código | Descripción                                    | Archivo(s)                    |
|--------|------------------------------------------------|-------------------------------|
| RF-1   | Autenticación mediante PIN                     | api.php, decisions.php, app.js |
| RF-2   | Validación de credenciales                     | api.php (verifyPin)            |
| RF-3   | Visualización de saldo                         | api.php (balanceInquiry)       |
| RF-4   | Retiro con actualización de saldo              | api.php (processWithdrawal)    |
| RF-5   | Depósito con actualización de saldo            | api.php (processDeposit)       |
| RF-6   | Transferencias entre cuentas                   | api.php (processTransfer)      |
| RF-7   | Cambio de PIN                                  | api.php (changePin), app.js    |
| RF-8   | Historial de transacciones                     | api.php (getTransactions)      |
| RF-9   | Selección de idioma (ES/EN)                    | lang.js, index.html            |
| RF-10  | Impresión de comprobante                       | app.js (printReceipt)          |
| RF-11  | Cierre de sesión por inactividad               | app.js (resetInactivity)       |
| RF-12  | Mensajes de error claros                       | lang.js (errores bilingües)    |
| RF-13  | Menú principal con opciones                    | index.html (view-menu)         |
| RF-14  | Confirmación antes de ejecutar                 | app.js (showConfirmation)      |
| RF-15  | Registro de usuarios y cuentas                 | api.php (registerCustomer)     |

### Requerimientos No Funcionales

| Código  | Cumplimiento                                                  |
|---------|---------------------------------------------------------------|
| RNF-1   | Accesible en todo momento vía XAMPP (localhost)               |
| RNF-2   | Interfaz intuitiva estilo cajero físico                       |
| RNF-3   | PIN almacenado con hash (preparado para password_hash)        |
| RNF-4   | Respuesta < 2 segundos por transacción                       |
| RNF-5   | Arquitectura modular: agregar bancos/redes sin cambiar código |
| RNF-6   | Funciona en Chrome, Firefox, Edge, Safari                     |
| RNF-7   | Responsive: funciona en móvil y PC                            |
| RNF-8   | Código separado: PHP, HTML, CSS, JS documentado               |
| RNF-9   | Transacciones ACID con BEGIN/COMMIT en MySQL                  |
| RNF-10  | Manejo de errores con try-catch y mensajes claros             |
| RNF-11  | Estándares: PDO, REST API, JSON, UTF-8                        |
| RNF-12  | Diseño visual con temas por banco                             |
| RNF-13  | Estilo uniforme: misma tipografía, colores coherentes         |
| RNF-14  | Solo PIN en BD; datos sensibles en servidor                   |
| RNF-15  | Logs de auditoría y logs de decisiones en BD                  |

## Teoría de Decisiones Aplicada

### Concepto
Cada operación del cajero pasa por un Motor de Decisiones (decisions.php) que evalúa múltiples criterios ponderados antes de aprobar o rechazar.

### Modelo Multicriterio Ponderado
Cada criterio tiene un peso (weight) que refleja su importancia. El sistema calcula un puntaje total:

```
Score = (Σ criterios_cumplidos.peso / Σ todos_criterios.peso) × 100
```

Si Score >= Umbral (100) → APPROVED
Si Score < Umbral → DENIED (con razón específica)

### Árbol de Decisión: Retiro

```
¿Tarjeta activa? (peso: 25)
  │ NO → DENIED: "Tarjeta no activa"
  │ SÍ ↓
¿Cuenta activa? (peso: 20)
  │ NO → DENIED: "Cuenta no activa"
  │ SÍ ↓
¿Monto válido? (peso: 10)
  │ NO → DENIED: "Monto inválido"
  │ SÍ ↓
¿Fondos suficientes? (peso: 25)
  │ NO → DENIED: "Fondos insuficientes"
  │ SÍ ↓
¿Dentro del límite diario? (peso: 20)
  │ NO → DENIED: "Excede límite diario"
  │ SÍ ↓
APPROVED (Score: 100/100)
```

### Árbol de Decisión: Autenticación PIN

```
¿Tarjeta existe? (peso: 30)
  │ NO → DENIED
  │ SÍ ↓
¿No está bloqueada? (peso: 25)
  │ NO → DENIED: "Tarjeta bloqueada"
  │ SÍ ↓
¿Intentos disponibles? (peso: 20)
  │ NO → DENIED + BLOQUEO
  │ SÍ ↓
¿PIN correcto? (peso: 25)
  │ NO → DENIED: "PIN incorrecto" + incrementar intentos
  │ SÍ ↓
APPROVED (Score: 100/100) → resetear intentos
```

### Registro en Base de Datos
Cada decisión se almacena en la tabla `decision_log` con:
- Tipo de decisión
- Criterios evaluados (JSON)
- Puntaje obtenido vs umbral
- Resultado (APPROVED/DENIED)
- Razón de denegación
- Tiempo de procesamiento (ms)

Se puede visualizar en el Dashboard → pestaña "Decisiones".

## Rol de PHP en el Proyecto

PHP actúa como la capa del servidor que:
1. Conecta con MySQL mediante PDO (database.php)
2. Valida el PIN comparándolo en el servidor, nunca en el navegador
3. Procesa transacciones con integridad ACID (BEGIN TRANSACTION)
4. Ejecuta el Motor de Decisiones evaluando criterios ponderados
5. Registra cada operación en el log de auditoría
6. Protege datos sensibles: el PIN y saldos nunca se exponen al frontend
7. Genera códigos únicos de transacción (TX...)

## Bancos Incluidos

| Banco                      | Código | Color Primario | Color Acento |
|----------------------------|--------|----------------|--------------|
| Banco Mercantil Santa Cruz | bmsc   | #003876 (azul) | #00a3e0      |
| Banco Nacional de Bolivia  | bnb    | #004d25 (verde)| #00cc66      |
| Banco Unión                | bu     | #c41230 (rojo) | #ff6b35      |

## API Endpoints

| Action             | Método | Descripción                    |
|--------------------|--------|--------------------------------|
| get_banks          | GET    | Lista de bancos activos        |
| get_cards_by_bank  | GET    | Tarjetas por banco             |
| verify_pin         | POST   | Verificar PIN (con decisiones) |
| get_card_info      | GET    | Info completa de tarjeta       |
| withdrawal         | POST   | Retiro (con decisiones)        |
| deposit            | POST   | Depósito (con decisiones)      |
| transfer           | POST   | Transferencia (con decisiones) |
| balance_inquiry    | GET    | Consultar saldo                |
| change_pin         | POST   | Cambiar PIN (RF-7)             |
| get_transactions   | GET    | Historial (RF-8)               |
| register_customer  | POST   | Registro nuevo (RF-15)         |
| get_stats          | GET    | Estadísticas dashboard         |
| get_audit_log      | GET    | Log de auditoría               |
| get_decision_log   | GET    | Log de decisiones              |
>>>>>>> b7c1a06386a779af52f4d1b04e9e4731c2491363
