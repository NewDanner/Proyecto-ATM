// ═══════════════════════════════════════════════════════════════
// NexoATM — Sistema de Idiomas (RF-9)
// Español / English
// ═══════════════════════════════════════════════════════════════

const LANG = {
  es: {
    // General
    title: 'NexoATM',
    subtitle: 'Cajero Automático Universal',
    language: 'Idioma',
    spanish: 'Español',
    english: 'English',

    // Idle
    insert_card: 'Toque para insertar su tarjeta',
    compatible: 'Compatible con todas las redes bancarias',

    // Bank select
    select_bank: 'Seleccione su banco',
    cancel: 'Cancelar',

    // Card select
    change_bank: '← Cambiar banco',
    select_card: 'Seleccione su tarjeta',

    // PIN
    enter_pin: 'Ingrese su PIN de 4 dígitos',
    verifying: 'Verificando...',
    pin_correct: 'PIN correcto',

    // Menu
    welcome: 'Bienvenido/a',
    withdrawal: 'Retiro',
    deposit: 'Depósito',
    transfer: 'Transferencia',
    balance: 'Saldo',
    pin_change: 'Cambio PIN',
    history: 'Historial',
    eject_card: 'Retirar Tarjeta',

    // Withdrawal
    withdrawal_title: 'Retiro de efectivo',
    use_keypad: 'O use el teclado para ingresar otro monto',
    confirm: 'Confirmar',
    menu: '← Menú',

    // Deposit
    deposit_title: 'Depósito de efectivo',

    // Transfer
    transfer_title: 'Transferencia',
    select_target: '— Seleccione cuenta destino —',
    transfer_btn: 'Transferir',
    enter_amount_keypad: 'Use el teclado para ingresar el monto',

    // Balance
    available_balance: 'Saldo Disponible',
    main_menu: 'Menú Principal',
    available_today: 'Disponible hoy',

    // PIN Change
    pin_change_title: 'Cambio de PIN',
    current_pin: 'PIN Actual',
    new_pin: 'Nuevo PIN',
    confirm_new_pin: 'Confirmar Nuevo PIN',
    change_btn: 'Cambiar PIN',
    pin_changed: 'PIN cambiado exitosamente',
    pins_no_match: 'Los PINs no coinciden',
    pin_different: 'El nuevo PIN debe ser diferente',

    // History
    history_title: 'Últimos Movimientos',
    no_transactions: 'Sin transacciones',

    // Processing
    processing: 'Procesando operación...',
    dont_remove: 'No retire su tarjeta',

    // Receipt
    success: 'Operación Exitosa',
    receipt_thanks: 'Gracias por usar nuestro servicio',
    another_op: 'Otra Operación',
    print_receipt: 'Imprimir',
    date: 'Fecha',
    ref: 'Ref',
    card: 'Tarjeta',
    operation: 'Operación',
    amount: 'Monto',
    target: 'Destino',
    balance_label: 'Saldo',

    // Confirmation (RF-14)
    confirm_title: '¿Confirmar operación?',
    confirm_withdrawal: '¿Desea retirar',
    confirm_deposit: '¿Desea depositar',
    confirm_transfer: '¿Desea transferir',
    yes: 'Sí, confirmar',
    no: 'No, cancelar',

    // Register (RF-15)
    register_title: 'Registro de Nueva Cuenta',
    ci_label: 'Carnet de Identidad',
    first_name: 'Nombre(s)',
    last_name: 'Apellido(s)',
    select_bank_reg: 'Banco',
    pin_label: 'PIN (4 dígitos)',
    register_btn: 'Registrar',
    back_login: '← Volver',

    // Errors (RF-12)
    error_connection: 'Error de conexión con el servidor',
    error_invalid_amount: 'Ingrese un monto válido',
    error_select_target: 'Seleccione cuenta destino',
    error_required: 'Todos los campos son requeridos',
    error_pin_4: 'El PIN debe ser de 4 dígitos',

    // Inactivity (RF-11)
    inactivity_warning: 'Sesión cerrará por inactividad en',
    seconds: 'segundos',
    session_closed: 'Sesión cerrada por inactividad',

    // Types
    type_withdrawal: 'RETIRO',
    type_deposit: 'DEPÓSITO',
    type_transfer: 'TRANSFERENCIA',
    type_balance: 'CONSULTA',
    type_pin_change: 'CAMBIO PIN',

    // ATM label
    atm_label: 'Cajero Automático',
    insert_label: '▸ INSERTAR TARJETA ◂',

    // Dashboard link
    go_dashboard: 'Panel Admin'
  },

  en: {
    title: 'NexoATM',
    subtitle: 'Universal ATM',
    language: 'Language',
    spanish: 'Español',
    english: 'English',

    insert_card: 'Tap to insert your card',
    compatible: 'Compatible with all card networks',

    select_bank: 'Select your bank',
    cancel: 'Cancel',

    change_bank: '← Change bank',
    select_card: 'Select your card',

    enter_pin: 'Enter your 4-digit PIN',
    verifying: 'Verifying...',
    pin_correct: 'PIN correct',

    welcome: 'Welcome',
    withdrawal: 'Withdrawal',
    deposit: 'Deposit',
    transfer: 'Transfer',
    balance: 'Balance',
    pin_change: 'Change PIN',
    history: 'History',
    eject_card: 'Eject Card',

    withdrawal_title: 'Cash Withdrawal',
    use_keypad: 'Or use the keypad to enter another amount',
    confirm: 'Confirm',
    menu: '← Menu',

    deposit_title: 'Cash Deposit',

    transfer_title: 'Transfer',
    select_target: '— Select target account —',
    transfer_btn: 'Transfer',
    enter_amount_keypad: 'Use the keypad to enter the amount',

    available_balance: 'Available Balance',
    main_menu: 'Main Menu',
    available_today: 'Available today',

    pin_change_title: 'Change PIN',
    current_pin: 'Current PIN',
    new_pin: 'New PIN',
    confirm_new_pin: 'Confirm New PIN',
    change_btn: 'Change PIN',
    pin_changed: 'PIN changed successfully',
    pins_no_match: 'PINs do not match',
    pin_different: 'New PIN must be different',

    history_title: 'Recent Transactions',
    no_transactions: 'No transactions',

    processing: 'Processing...',
    dont_remove: 'Do not remove your card',

    success: 'Operation Successful',
    receipt_thanks: 'Thank you for using our service',
    another_op: 'Another Operation',
    print_receipt: 'Print',
    date: 'Date',
    ref: 'Ref',
    card: 'Card',
    operation: 'Operation',
    amount: 'Amount',
    target: 'Target',
    balance_label: 'Balance',

    confirm_title: 'Confirm operation?',
    confirm_withdrawal: 'Do you want to withdraw',
    confirm_deposit: 'Do you want to deposit',
    confirm_transfer: 'Do you want to transfer',
    yes: 'Yes, confirm',
    no: 'No, cancel',

    register_title: 'New Account Registration',
    ci_label: 'ID Number',
    first_name: 'First Name',
    last_name: 'Last Name',
    select_bank_reg: 'Bank',
    pin_label: 'PIN (4 digits)',
    register_btn: 'Register',
    back_login: '← Back',

    error_connection: 'Server connection error',
    error_invalid_amount: 'Enter a valid amount',
    error_select_target: 'Select target account',
    error_required: 'All fields are required',
    error_pin_4: 'PIN must be 4 digits',

    inactivity_warning: 'Session will close due to inactivity in',
    seconds: 'seconds',
    session_closed: 'Session closed due to inactivity',

    type_withdrawal: 'WITHDRAWAL',
    type_deposit: 'DEPOSIT',
    type_transfer: 'TRANSFER',
    type_balance: 'INQUIRY',
    type_pin_change: 'PIN CHANGE',

    atm_label: 'Automatic Teller Machine',
    insert_label: '▸ INSERT CARD ◂',

    go_dashboard: 'Admin Panel'
  }
};

// Estado actual del idioma
let currentLang = 'es';

function setLang(lang) {
  currentLang = lang;
  document.documentElement.lang = lang;
  updateAllTexts();
}

function t(key) {
  return LANG[currentLang]?.[key] || LANG['es']?.[key] || key;
}

function updateAllTexts() {
  document.querySelectorAll('[data-lang]').forEach(el => {
    const key = el.getAttribute('data-lang');
    el.textContent = t(key);
  });
  document.querySelectorAll('[data-lang-placeholder]').forEach(el => {
    el.placeholder = t(el.getAttribute('data-lang-placeholder'));
  });
}
