<<<<<<< HEAD
const LANG={
es:{title:'NexoATM',subtitle:'Cajero Automático Universal',insert_card:'Insertar Tarjeta',compatible:'Compatible con todas las redes bancarias',enter_account:'Ingrese su número de cuenta',enter_pin:'Ingrese su PIN de 4 dígitos',account_placeholder:'Ej: 1001-0001-2024',verifying:'Verificando...',select_currency:'Seleccione la moneda',bob_label:'Bolivianos',usd_label:'Dólares',welcome:'Bienvenido/a',withdrawal:'Retiro',deposit:'Depósito',transfer:'Transferencia',balance:'Saldo',pin_change:'Cambio PIN',history:'Historial',donation:'Donación',settings:'Ajustes',eject_card:'Retirar Tarjeta',withdrawal_title:'Retiro de Efectivo',deposit_title:'Depósito de Efectivo',transfer_title:'Transferencia',select_target:'— Seleccione cuenta destino —',transfer_btn:'Transferir',enter_amount:'Use el teclado para ingresar el monto',other_amount:'Otra cantidad',available_balance:'Saldo Disponible',main_menu:'Menú Principal',available_today:'Disponible hoy',pin_change_title:'Cambio de PIN',current_pin:'PIN Actual',new_pin:'Nuevo PIN',confirm_new_pin:'Confirmar Nuevo PIN',change_btn:'Cambiar PIN',pin_changed:'PIN cambiado exitosamente',pins_no_match:'Los PINs no coinciden',pin_different:'El nuevo PIN debe ser diferente',history_title:'Últimos Movimientos',no_transactions:'Sin transacciones',processing:'Procesando operación...',dont_remove:'No retire su tarjeta',success:'Operación Exitosa',receipt_thanks:'Gracias por usar nuestro servicio',another_op:'Otra Operación',print_receipt:'Imprimir',confirm_title:'¿Confirmar operación?',confirm_withdrawal:'¿Desea retirar',confirm_deposit:'¿Desea depositar',confirm_transfer:'¿Desea transferir',yes:'Sí, confirmar',no:'No, cancelar',confirm:'Confirmar',cancel:'Cancelar',menu:'← Menú',error_connection:'Error de conexión',error_invalid_amount:'Ingrese un monto válido',error_select_target:'Seleccione cuenta destino',error_pin_4:'PIN debe ser 4 dígitos',inactivity_warning:'Sesión cerrará en',seconds:'segundos',session_closed:'Sesión cerrada por inactividad',date:'Fecha',ref:'Ref',card:'Tarjeta',operation:'Operación',amount:'Monto',target:'Destino',balance_label:'Saldo',atm_label:'Cajero Automático',type_withdrawal:'RETIRO',type_deposit:'DEPÓSITO',type_transfer:'TRANSFERENCIA',type_balance:'CONSULTA',type_pin_change:'CAMBIO PIN',type_donation:'DONACIÓN',bob:'Bs',usd:'$',bal_bob:'Bolivianos',bal_usd:'Dólares',custom_amount:'Ingrese el monto deseado',security_title:'Verificación de Seguridad',security_desc:'Para montos grandes, responda su pregunta de seguridad',security_answer:'Su respuesta',donation_title:'Donación Solidaria',donation_select:'Seleccione una causa',donate_btn:'Donar',dark_mode:'Modo Oscuro',light_mode:'Modo Claro',accessibility:'Accesibilidad',sounds:'Sonidos',tutorial:'Tutorial',tutorial_welcome:'Bienvenido a NexoATM',tutorial_step1:'Inserte su tarjeta presionando el botón en pantalla',tutorial_step2:'Ingrese su número de cuenta y PIN de 4 dígitos',tutorial_step3:'Seleccione la moneda: Bolivianos o Dólares',tutorial_step4:'Elija la operación que desee realizar',tutorial_step5:'¡Listo! Su transacción será procesada de forma segura',next:'Siguiente',finish:'Finalizar',decision_title:'Análisis de Decisión',scan_qr:'Escanee el código QR',set_limit:'Configurar Límite',limit_label:'Nuevo límite diario',save:'Guardar',favorites:'Favoritos',add_fav:'Agregar a favoritos',
},
en:{title:'NexoATM',subtitle:'Universal ATM',insert_card:'Insert Card',compatible:'Compatible with all card networks',enter_account:'Enter your account number',enter_pin:'Enter your 4-digit PIN',account_placeholder:'Ex: 1001-0001-2024',verifying:'Verifying...',select_currency:'Select currency',bob_label:'Bolivianos',usd_label:'Dollars',welcome:'Welcome',withdrawal:'Withdrawal',deposit:'Deposit',transfer:'Transfer',balance:'Balance',pin_change:'Change PIN',history:'History',donation:'Donation',settings:'Settings',eject_card:'Eject Card',withdrawal_title:'Cash Withdrawal',deposit_title:'Cash Deposit',transfer_title:'Transfer',select_target:'— Select target account —',transfer_btn:'Transfer',enter_amount:'Use the keypad to enter amount',other_amount:'Other amount',available_balance:'Available Balance',main_menu:'Main Menu',available_today:'Available today',pin_change_title:'Change PIN',current_pin:'Current PIN',new_pin:'New PIN',confirm_new_pin:'Confirm New PIN',change_btn:'Change PIN',pin_changed:'PIN changed successfully',pins_no_match:'PINs do not match',pin_different:'New PIN must be different',history_title:'Recent Transactions',no_transactions:'No transactions',processing:'Processing...',dont_remove:'Do not remove your card',success:'Operation Successful',receipt_thanks:'Thank you for using our service',another_op:'Another Operation',print_receipt:'Print',confirm_title:'Confirm operation?',confirm_withdrawal:'Do you want to withdraw',confirm_deposit:'Do you want to deposit',confirm_transfer:'Do you want to transfer',yes:'Yes, confirm',no:'No, cancel',confirm:'Confirm',cancel:'Cancel',menu:'← Menu',error_connection:'Connection error',error_invalid_amount:'Enter a valid amount',error_select_target:'Select target account',error_pin_4:'PIN must be 4 digits',inactivity_warning:'Session will close in',seconds:'seconds',session_closed:'Session closed',date:'Date',ref:'Ref',card:'Card',operation:'Operation',amount:'Amount',target:'Target',balance_label:'Balance',atm_label:'Automatic Teller Machine',type_withdrawal:'WITHDRAWAL',type_deposit:'DEPOSIT',type_transfer:'TRANSFER',type_balance:'INQUIRY',type_pin_change:'PIN CHANGE',type_donation:'DONATION',bob:'Bs',usd:'$',bal_bob:'Bolivianos',bal_usd:'Dollars',custom_amount:'Enter desired amount',security_title:'Security Verification',security_desc:'For large amounts, answer your security question',security_answer:'Your answer',donation_title:'Solidarity Donation',donation_select:'Select a cause',donate_btn:'Donate',dark_mode:'Dark Mode',light_mode:'Light Mode',accessibility:'Accessibility',sounds:'Sounds',tutorial:'Tutorial',tutorial_welcome:'Welcome to NexoATM',tutorial_step1:'Insert your card by pressing the button on screen',tutorial_step2:'Enter your account number and 4-digit PIN',tutorial_step3:'Select the currency: Bolivianos or Dollars',tutorial_step4:'Choose the operation you want to perform',tutorial_step5:'Done! Your transaction will be processed securely',next:'Next',finish:'Finish',decision_title:'Decision Analysis',scan_qr:'Scan QR code',set_limit:'Set Limit',limit_label:'New daily limit',save:'Save',favorites:'Favorites',add_fav:'Add to favorites',
}};
let currentLang='es';
function setLang(l){currentLang=l;document.documentElement.lang=l;updateAllTexts()}
function t(k){return LANG[currentLang]?.[k]||LANG.es?.[k]||k}
function updateAllTexts(){document.querySelectorAll('[data-lang]').forEach(e=>e.textContent=t(e.getAttribute('data-lang')));document.querySelectorAll('[data-lang-ph]').forEach(e=>e.placeholder=t(e.getAttribute('data-lang-ph')))}
=======
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
>>>>>>> b7c1a06386a779af52f4d1b04e9e4731c2491363
