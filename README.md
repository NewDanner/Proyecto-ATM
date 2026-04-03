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
