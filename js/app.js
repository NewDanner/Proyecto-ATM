// ═══════════════════════════════════════════════════════════════
// NexoATM — Lógica Principal del Cajero
// Cumple: RF-1 a RF-15, RNF-1 a RNF-15
// Integra: Teoría de Decisiones, Idioma dual (ES/EN)
// ═══════════════════════════════════════════════════════════════

const API = 'api/api.php';

// ─── ESTADO GLOBAL ───
const ATM = {
  currentView: 'idle',
  selectedBank: null,
  selectedCard: null,
  cardInfo: null,
  pin: '',
  amount: '',
  operation: '',
  holderName: '',
  pendingAction: null // Para confirmación RF-14
};

// ─── RF-11: TEMPORIZADOR DE INACTIVIDAD ───
let inactivityTimer = null;
let inactivityWarningTimer = null;
let inactivityCountdown = null;
const INACTIVITY_TIMEOUT = 120; // segundos
const INACTIVITY_WARNING = 30;  // últimos 30 seg = warning

function resetInactivity() {
  clearTimeout(inactivityTimer);
  clearTimeout(inactivityWarningTimer);
  clearInterval(inactivityCountdown);
  const bar = document.getElementById('inactivityBar');
  if (bar) bar.classList.remove('active');

  // Solo activar si hay sesión
  if (!ATM.selectedCard || ATM.currentView === 'idle' || ATM.currentView === 'bank-select') return;

  // Warning a los (TIMEOUT - WARNING) segundos
  inactivityWarningTimer = setTimeout(() => {
    const bar = document.getElementById('inactivityBar');
    if (bar) bar.classList.add('active');
    let remaining = INACTIVITY_WARNING;

    inactivityCountdown = setInterval(() => {
      remaining--;
      const bar = document.getElementById('inactivityBar');
      if (bar) bar.textContent = `${t('inactivity_warning')} ${remaining} ${t('seconds')}`;
      if (remaining <= 0) clearInterval(inactivityCountdown);
    }, 1000);
  }, (INACTIVITY_TIMEOUT - INACTIVITY_WARNING) * 1000);

  // Cierre automático
  inactivityTimer = setTimeout(() => {
    toast(t('session_closed'), 'info');
    ejectCard();
  }, INACTIVITY_TIMEOUT * 1000);
}

// Detectar actividad del usuario
['click', 'keydown', 'touchstart'].forEach(evt => {
  document.addEventListener(evt, resetInactivity);
});

// ─── RELOJ ───
setInterval(() => {
  const el = document.getElementById('clockDisplay');
  if (el) el.textContent = new Date().toLocaleTimeString('es-BO');
}, 1000);

// ─── NAVEGACIÓN ───
function goTo(viewId) {
  document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
  const view = document.getElementById('view-' + viewId);
  if (view) { view.classList.add('active'); ATM.currentView = viewId; }
  if (viewId === 'bank-select') loadBanks();
  resetInactivity();
}

// ─── TOAST ───
function toast(msg, type = 'info') {
  const box = document.getElementById('toastBox');
  const el = document.createElement('div');
  el.className = 'toast ' + type;
  const icons = { success: '✅', error: '❌', info: 'ℹ️' };
  el.innerHTML = `<span>${icons[type] || 'ℹ️'}</span> ${msg}`;
  box.appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.3s'; setTimeout(() => el.remove(), 300); }, 3000);
}

// ─── API ───
async function api(action, params = {}, method = 'GET') {
  try {
    let url = `${API}?action=${action}`;
    let opts = { method };
    if (method === 'GET') {
      Object.entries(params).forEach(([k, v]) => url += `&${k}=${encodeURIComponent(v)}`);
    } else {
      opts.headers = { 'Content-Type': 'application/json' };
      opts.body = JSON.stringify(params);
    }
    const resp = await fetch(url, opts);
    return await resp.json();
  } catch (err) {
    toast(t('error_connection'), 'error');
    return { success: false, error: err.message };
  }
}

// ─── CARGAR BANCOS ───
async function loadBanks() {
  const data = await api('get_banks');
  if (!data.success) return;

  const colors = {
    bmsc: 'rgba(0,56,118,0.12)',
    bnb: 'rgba(0,77,37,0.12)',
    bu: 'rgba(196,18,48,0.12)'
  };

  document.getElementById('bankList').innerHTML = data.banks.map(bank => `
    <div class="bank-card" style="background:${colors[bank.code] || 'rgba(255,255,255,0.03)'}" onclick="selectBank(${bank.id},'${bank.code}')">
      <div class="side-bar" style="background:${bank.primary_color}"></div>
      <div class="bank-row">
        <div class="bank-logo-box" style="background:linear-gradient(135deg,${bank.primary_color},${bank.secondary_color})">${bank.logo_text}</div>
        <div class="bank-info"><h3>${bank.name}</h3><p>${bank.slogan || ''}</p></div>
        <div class="arrow">›</div>
      </div>
    </div>`).join('');
}

// ─── SELECCIONAR BANCO ───
async function selectBank(bankId, bankCode) {
  ATM.selectedBank = { id: bankId, code: bankCode };
  document.getElementById('atmMachine').className = 'atm-machine theme-' + bankCode;

  const data = await api('get_cards_by_bank', { bank_id: bankId });
  if (!data.success) { toast(data.error, 'error'); return; }

  const names = { bmsc: 'Banco Mercantil Santa Cruz', bnb: 'Banco Nacional de Bolivia', bu: 'Banco Unión' };
  setBankHeaders(names[bankCode] || 'Banco', bankCode);

  const list = document.getElementById('cardList');
  if (data.cards.length === 0) {
    list.innerHTML = `<div class="small-text" style="padding:24px 0;">Sin tarjetas registradas</div>`;
  } else {
    list.innerHTML = data.cards.map(card => {
      const lock = card.card_status !== 'active' ? '🔒 ' : '';
      return `
        <div class="card-item" onclick="selectCard(${card.id})">
          <div class="chip-mini"></div>
          <div class="card-details">
            <div class="name">${lock}${card.holder_name}</div>
            <div class="number">•••• ${card.card_number.slice(-4)} | ${card.account_number}</div>
          </div>
          <span class="net-badge net-${card.network_code}">${card.network_code}</span>
        </div>`;
    }).join('');
  }
  goTo('card-select');
}

function setBankHeaders(bankName, bankCode) {
  const logos = { bmsc: 'BMSc', bnb: 'BNB', bu: 'BU' };
  const html = `<div class="bank-title">${logos[bankCode] || '◆'} ${bankName}</div><div class="bank-subtitle">${t('atm_label')}</div>`;
  document.querySelectorAll('.bank-header').forEach(el => el.innerHTML = html);
}

// ─── SELECCIONAR TARJETA → PIN ───
function selectCard(cardId) {
  ATM.selectedCard = cardId;
  ATM.pin = '';
  updatePinDots();
  document.getElementById('pinStatus').textContent = '';
  document.getElementById('pinStatus').style.color = '#5a6280';
  goTo('pin');
}

function updatePinDots() {
  const dots = document.getElementById('pinDots');
  if (!dots) return;
  dots.innerHTML = [0,1,2,3].map(i => `<div class="pin-dot ${i < ATM.pin.length ? 'filled' : ''}"></div>`).join('');
}

// ─── RF-1, RF-2: VERIFICAR PIN ───
async function submitPin() {
  if (ATM.pin.length !== 4) return;
  document.getElementById('pinStatus').textContent = t('verifying');

  const data = await api('verify_pin', { card_id: ATM.selectedCard, pin: ATM.pin }, 'POST');

  if (data.success) {
    ATM.holderName = data.holder_name;
    const info = await api('get_card_info', { card_id: ATM.selectedCard });
    if (info.success) ATM.cardInfo = info.card;
    document.getElementById('welcomeUser').textContent = `${t('welcome')}, ${ATM.holderName}`;
    toast(t('pin_correct'), 'success');
    goTo('menu');
  } else {
    ATM.pin = '';
    updatePinDots();
    document.getElementById('pinStatus').textContent = data.error;
    document.getElementById('pinStatus').style.color = '#ff4466';
    toast(data.error, 'error');
    if (data.blocked) setTimeout(() => ejectCard(), 2000);
  }
}

// ─── OPERACIONES ───
function goToOperation(op) {
  ATM.operation = op;
  ATM.amount = '';

  switch (op) {
    case 'withdrawal':
      document.getElementById('wAmount').textContent = '0';
      goTo('withdrawal');
      break;
    case 'deposit':
      document.getElementById('dAmount').textContent = '0';
      goTo('deposit');
      break;
    case 'transfer':
      document.getElementById('tAmount').textContent = '0';
      loadTransferTargets();
      goTo('transfer');
      break;
    case 'balance':
      queryBalance();
      break;
    case 'pin_change':
      goTo('pin-change');
      break;
    case 'history':
      loadHistory();
      break;
  }
}

function setQuick(amt) {
  ATM.amount = amt.toString();
  updateAmountDisplay();
}

function updateAmountDisplay() {
  const str = ATM.amount ? parseFloat(ATM.amount).toLocaleString('es-BO') : '0';
  const targets = { withdrawal: 'wAmount', deposit: 'dAmount', transfer: 'tAmount' };
  const el = document.getElementById(targets[ATM.operation]);
  if (el) el.textContent = str;
}

// ─── RF-14: CONFIRMACIÓN ───
function showConfirmation(type, amount, callback) {
  const overlay = document.getElementById('confirmOverlay');
  const msgs = { withdrawal: t('confirm_withdrawal'), deposit: t('confirm_deposit'), transfer: t('confirm_transfer') };
  const cur = ATM.cardInfo?.currency === 'USD' ? '$' : 'Bs';

  document.getElementById('confirmDetail').textContent = `${cur} ${parseFloat(amount).toLocaleString('es-BO', { minimumFractionDigits: 2 })}`;
  document.getElementById('confirmMessage').textContent = msgs[type] || t('confirm_title');
  ATM.pendingAction = callback;
  overlay.classList.add('active');
}

function confirmYes() {
  document.getElementById('confirmOverlay').classList.remove('active');
  if (ATM.pendingAction) { ATM.pendingAction(); ATM.pendingAction = null; }
}

function confirmNo() {
  document.getElementById('confirmOverlay').classList.remove('active');
  ATM.pendingAction = null;
}

// ─── RF-4: RETIRO ───
function submitWithdrawal() {
  const amount = parseFloat(ATM.amount);
  if (!amount || amount <= 0) { toast(t('error_invalid_amount'), 'error'); return; }
  showConfirmation('withdrawal', amount, async () => {
    goTo('processing');
    const data = await api('withdrawal', { card_id: ATM.selectedCard, amount }, 'POST');
    setTimeout(() => {
      if (data.success) showReceipt(data.transaction, t('type_withdrawal'));
      else { toast(data.error, 'error'); goTo('withdrawal'); }
    }, 1600);
  });
}

// ─── RF-5: DEPÓSITO ───
function submitDeposit() {
  const amount = parseFloat(ATM.amount);
  if (!amount || amount <= 0) { toast(t('error_invalid_amount'), 'error'); return; }
  showConfirmation('deposit', amount, async () => {
    goTo('processing');
    const data = await api('deposit', { card_id: ATM.selectedCard, amount }, 'POST');
    setTimeout(() => {
      if (data.success) showReceipt(data.transaction, t('type_deposit'));
      else { toast(data.error, 'error'); goTo('deposit'); }
    }, 1600);
  });
}

// ─── RF-6: TRANSFERENCIA ───
async function loadTransferTargets() {
  const data = await api('get_transfer_targets', { card_id: ATM.selectedCard });
  if (!data.success) return;
  const sel = document.getElementById('transferTarget');
  sel.innerHTML = `<option value="">${t('select_target')}</option>` +
    data.accounts.map(a => `<option value="${a.id}">${a.first_name} ${a.last_name} — ${a.bank_name} (${a.account_number})</option>`).join('');
}

function submitTransfer() {
  const amount = parseFloat(ATM.amount);
  const targetId = document.getElementById('transferTarget').value;
  if (!targetId) { toast(t('error_select_target'), 'error'); return; }
  if (!amount || amount <= 0) { toast(t('error_invalid_amount'), 'error'); return; }
  showConfirmation('transfer', amount, async () => {
    goTo('processing');
    const data = await api('transfer', { card_id: ATM.selectedCard, target_account_id: parseInt(targetId), amount }, 'POST');
    setTimeout(() => {
      if (data.success) showReceipt(data.transaction, t('type_transfer'));
      else { toast(data.error, 'error'); goTo('transfer'); }
    }, 2000);
  });
}

// ─── RF-3: SALDO ───
async function queryBalance() {
  goTo('processing');
  const data = await api('balance_inquiry', { card_id: ATM.selectedCard });
  setTimeout(() => {
    if (data.success) {
      const b = data.balance;
      const cur = b.currency === 'USD' ? '$' : 'Bs';
      document.getElementById('balanceAmount').textContent = `${cur} ${parseFloat(b.amount).toLocaleString('es-BO', { minimumFractionDigits: 2 })}`;
      document.getElementById('balanceExtra').innerHTML = `${b.holder_name} | •••• ${b.card_last4}<br>${t('available_today')}: ${cur} ${parseFloat(b.available_today).toLocaleString('es-BO', { minimumFractionDigits: 2 })}`;
      goTo('balance');
    } else { toast(data.error, 'error'); goTo('menu'); }
  }, 1000);
}

// ─── RF-7: CAMBIO DE PIN ───
async function submitPinChange() {
  const currentPin = document.getElementById('currentPinInput').value;
  const newPin = document.getElementById('newPinInput').value;
  const confirmPin = document.getElementById('confirmPinInput').value;

  if (currentPin.length !== 4 || newPin.length !== 4) { toast(t('error_pin_4'), 'error'); return; }
  if (newPin !== confirmPin) { toast(t('pins_no_match'), 'error'); return; }
  if (currentPin === newPin) { toast(t('pin_different'), 'error'); return; }

  const data = await api('change_pin', { card_id: ATM.selectedCard, current_pin: currentPin, new_pin: newPin }, 'POST');

  if (data.success) {
    toast(t('pin_changed'), 'success');
    document.getElementById('currentPinInput').value = '';
    document.getElementById('newPinInput').value = '';
    document.getElementById('confirmPinInput').value = '';
    goTo('menu');
  } else {
    toast(data.error, 'error');
  }
}

// ─── RF-8: HISTORIAL ───
async function loadHistory() {
  goTo('history');
  const data = await api('get_transactions', { card_id: ATM.selectedCard, limit: 10 });
  const list = document.getElementById('historyList');

  if (!data.success || data.transactions.length === 0) {
    list.innerHTML = `<div class="small-text" style="padding:20px 0;">${t('no_transactions')}</div>`;
    return;
  }

  const icons = { withdrawal: '💸', deposit: '💰', transfer: '🔄', balance_inquiry: '👁️', pin_change: '🔑' };
  const typeNames = { withdrawal: t('type_withdrawal'), deposit: t('type_deposit'), transfer: t('type_transfer'), balance_inquiry: t('type_balance'), pin_change: t('type_pin_change') };

  list.innerHTML = data.transactions.map(tx => {
    const isNeg = tx.tx_type === 'withdrawal' || tx.tx_type === 'transfer';
    const amtStr = tx.amount ? `${isNeg ? '-' : '+'}Bs ${parseFloat(tx.amount).toLocaleString('es-BO', { minimumFractionDigits: 2 })}` : '--';
    return `
      <div class="history-item">
        <span class="h-icon">${icons[tx.tx_type] || '📝'}</span>
        <div class="h-info">
          <div class="h-type">${typeNames[tx.tx_type] || tx.tx_type}</div>
          <div class="h-date">${tx.created_at}</div>
        </div>
        <div class="h-amount ${isNeg ? 'h-neg' : 'h-pos'}">${amtStr}</div>
      </div>`;
  }).join('');
}

// ─── RF-10: RECIBO ───
function showReceipt(tx, label) {
  const bankNames = { bmsc: 'Banco Mercantil Santa Cruz', bnb: 'Banco Nacional de Bolivia', bu: 'Banco Unión' };
  const bankName = bankNames[ATM.selectedBank?.code] || 'NexoATM';
  const cur = tx.currency === 'USD' ? '$' : 'Bs';

  let rows = `
    <div class="r-row"><span>${t('date')}:</span><span>${tx.timestamp}</span></div>
    <div class="r-row"><span>${t('ref')}:</span><span>${tx.tx_code}</span></div>
    <div class="r-row"><span>${t('card')}:</span><span>•••• ${tx.card_last4}</span></div>
    <div class="r-divider"></div>
    <div class="r-row"><span>${t('operation')}:</span><span>${label}</span></div>`;

  if (tx.amount) {
    rows += `<div class="r-row r-total"><span>${t('amount')}:</span><span>${cur} ${parseFloat(tx.amount).toLocaleString('es-BO', { minimumFractionDigits: 2 })}</span></div>`;
  }
  if (tx.target_account) {
    rows += `<div class="r-row"><span>${t('target')}:</span><span>${tx.target_account}</span></div>`;
  }
  rows += `<div class="r-divider"></div><div class="r-row"><span>${t('balance_label')}:</span><span style="font-weight:700">${cur} ${parseFloat(tx.balance_after).toLocaleString('es-BO', { minimumFractionDigits: 2 })}</span></div>`;

  document.getElementById('receiptContent').innerHTML = `
    <div class="r-header"><h4>${bankName}</h4><p>NexoATM — ${t('subtitle')}</p></div>
    ${rows}
    <div style="text-align:center;margin-top:8px;font-size:8px;color:#999;">${t('receipt_thanks')}</div>`;

  toast(t('success'), 'success');
  goTo('receipt');
}

// RF-10: Imprimir recibo
function printReceipt() {
  const content = document.getElementById('receiptContent').innerHTML;
  const win = window.open('', '_blank', 'width=400,height=600');
  win.document.write(`<html><head><title>NexoATM - Recibo</title><style>body{font-family:monospace;font-size:12px;padding:20px;} .r-row{display:flex;justify-content:space-between;padding:3px 0;} .r-divider{border-top:1px dashed #999;margin:6px 0;} .r-header{text-align:center;border-bottom:1px dashed #999;padding-bottom:8px;margin-bottom:8px;} .r-total{font-weight:bold;font-size:14px;}</style></head><body>${content}<script>window.print();window.close();</script></body></html>`);
}

// ─── EXPULSAR TARJETA ───
function ejectCard() {
  ATM.selectedCard = null;
  ATM.cardInfo = null;
  ATM.selectedBank = null;
  ATM.pin = '';
  ATM.amount = '';
  ATM.operation = '';
  ATM.holderName = '';
  ATM.pendingAction = null;

  clearTimeout(inactivityTimer);
  clearTimeout(inactivityWarningTimer);
  clearInterval(inactivityCountdown);
  const bar = document.getElementById('inactivityBar');
  if (bar) bar.classList.remove('active');

  document.getElementById('atmMachine').className = 'atm-machine';
  toast(t('eject_card') + '. ' + t('receipt_thanks'), 'info');
  goTo('idle');
}

// ─── RF-15: REGISTRO ───
async function submitRegister() {
  const ci = document.getElementById('regCI').value.trim();
  const firstName = document.getElementById('regFirstName').value.trim();
  const lastName = document.getElementById('regLastName').value.trim();
  const bankId = document.getElementById('regBank').value;
  const pin = document.getElementById('regPin').value.trim();

  if (!ci || !firstName || !lastName || !bankId || pin.length !== 4) {
    toast(t('error_required'), 'error');
    return;
  }

  const data = await api('register_customer', { ci, first_name: firstName, last_name: lastName, bank_id: parseInt(bankId), pin }, 'POST');

  if (data.success) {
    toast(`${data.message} — ${t('card')}: ${data.card_number}`, 'success');
    goTo('idle');
  } else {
    toast(data.error, 'error');
  }
}

async function loadRegisterBanks() {
  const data = await api('get_banks');
  if (!data.success) return;
  const sel = document.getElementById('regBank');
  sel.innerHTML = `<option value="">${t('select_bank_reg')}</option>` +
    data.banks.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
}

// ─── TECLADO FÍSICO ───
function keyPress(digit) {
  const view = ATM.currentView;
  if (view === 'pin' && ATM.pin.length < 4) {
    ATM.pin += digit;
    updatePinDots();
    if (ATM.pin.length === 4) setTimeout(submitPin, 300);
  } else if (['withdrawal', 'deposit', 'transfer'].includes(view)) {
    if (ATM.amount.length < 8) { ATM.amount += digit; updateAmountDisplay(); }
  }
}

function keyCancel() {
  const view = ATM.currentView;
  if (view === 'pin') { ATM.pin = ''; updatePinDots(); }
  else if (['withdrawal', 'deposit', 'transfer'].includes(view)) { ATM.amount = ''; updateAmountDisplay(); }
  else if (view === 'menu') ejectCard();
  else goTo('menu');
}

function keyEnter() {
  const view = ATM.currentView;
  if (view === 'pin') submitPin();
  else if (view === 'withdrawal') submitWithdrawal();
  else if (view === 'deposit') submitDeposit();
  else if (view === 'transfer') submitTransfer();
}

// Teclado físico del PC
document.addEventListener('keydown', (e) => {
  if (e.key >= '0' && e.key <= '9') keyPress(e.key);
  else if (e.key === 'Enter') keyEnter();
  else if (e.key === 'Escape') keyCancel();
  else if (e.key === 'Backspace') {
    if (ATM.currentView === 'pin') { ATM.pin = ATM.pin.slice(0, -1); updatePinDots(); }
    else if (['withdrawal', 'deposit', 'transfer'].includes(ATM.currentView)) { ATM.amount = ATM.amount.slice(0, -1); updateAmountDisplay(); }
  }
});

// ─── INIT ───
document.getElementById('clockDisplay').textContent = new Date().toLocaleTimeString('es-BO');
updateAllTexts();
