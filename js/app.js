const API='api/api.php';
const ATM={currentView:'idle',cardId:null,cardInfo:null,pin:'',amount:'',operation:'',holderName:'',bankCode:null,currency:'BOB',pendingAction:null,securityQuestion:null,soundsOn:true,customAmount:false,sessionId:null,txCount:0,firstOperation:true,wantsPrint:false};
let inactTimer,inactWarnTimer,inactCountdown;
const INACT_TIMEOUT=120,INACT_WARN=30;

// ═══ SOUNDS ═══
const AudioCtx=window.AudioContext||window.webkitAudioContext;
let audioCtx;
function playSound(type){
  if(!ATM.soundsOn)return;
  if(!audioCtx)audioCtx=new AudioCtx();
  const o=audioCtx.createOscillator(),g=audioCtx.createGain();
  o.connect(g);g.connect(audioCtx.destination);
  g.gain.value=0.08;
  if(type==='key'){o.frequency.value=800;o.type='sine';g.gain.value=0.05;o.start();o.stop(audioCtx.currentTime+0.05)}
  else if(type==='success'){o.frequency.value=523;o.type='sine';o.start();setTimeout(()=>o.frequency.value=659,100);setTimeout(()=>o.frequency.value=784,200);o.stop(audioCtx.currentTime+0.35)}
  else if(type==='error'){o.frequency.value=200;o.type='square';g.gain.value=0.04;o.start();o.stop(audioCtx.currentTime+0.3)}
  else if(type==='cash'){o.frequency.value=1200;o.type='sine';g.gain.value=0.04;o.start();setTimeout(()=>o.frequency.value=900,80);setTimeout(()=>o.frequency.value=600,160);o.stop(audioCtx.currentTime+0.4)}
  else if(type==='card'){o.frequency.value=440;o.type='sine';g.gain.value=0.03;o.start();setTimeout(()=>o.frequency.value=880,150);o.stop(audioCtx.currentTime+0.3)}
}

// ═══ DARK/LIGHT MODE ═══
function toggleTheme(){document.body.classList.toggle('light-mode');document.getElementById('themeBtn').textContent=document.body.classList.contains('light-mode')?'🌙':'☀️'}

// ═══ ACCESSIBILITY ═══
function toggleAccessibility(){document.body.classList.toggle('access-mode');const on=document.body.classList.contains('access-mode');document.getElementById('accessBtn').style.borderColor=on?'#00d4ff':'';toast(on?'Modo accesibilidad activado':'Modo normal','info')}

// ═══ SOUNDS TOGGLE ═══
function toggleSounds(){ATM.soundsOn=!ATM.soundsOn;document.getElementById('soundBtn').textContent=ATM.soundsOn?'🔊':'🔇';toast(ATM.soundsOn?'Sonidos activados':'Sonidos desactivados','info')}

// ═══ TUTORIAL ═══
let tutorialStep=0;
const tutorialData=[
  {icon:'💳',title:'tutorial_step1'},
  {icon:'🔐',title:'tutorial_step2'},
  {icon:'💱',title:'tutorial_step3'},
  {icon:'📋',title:'tutorial_step4'},
  {icon:'✅',title:'tutorial_step5'}
];
function startTutorial(){tutorialStep=0;showTutorialStep();document.getElementById('tutorialOverlay').classList.add('active')}
function showTutorialStep(){
  const d=tutorialData[tutorialStep];
  document.getElementById('tutIcon').textContent=d.icon;
  document.getElementById('tutStep').textContent=`${tutorialStep+1}/${tutorialData.length}`;
  document.getElementById('tutTitle').textContent=t(d.title);
  document.getElementById('tutNextBtn').textContent=tutorialStep<tutorialData.length-1?t('next'):t('finish');
}
function nextTutorial(){
  tutorialStep++;
  if(tutorialStep>=tutorialData.length){document.getElementById('tutorialOverlay').classList.remove('active');return}
  showTutorialStep();
}

// ═══ INACTIVITY ═══
function resetInactivity(){
  clearTimeout(inactTimer);clearTimeout(inactWarnTimer);clearInterval(inactCountdown);
  const bar=document.getElementById('inactivityBar');if(bar)bar.classList.remove('active');
  if(!ATM.cardId||ATM.currentView==='idle'||ATM.currentView==='login')return;
  inactWarnTimer=setTimeout(()=>{const bar=document.getElementById('inactivityBar');if(bar)bar.classList.add('active');let rem=INACT_WARN;inactCountdown=setInterval(()=>{rem--;if(bar)bar.textContent=`${t('inactivity_warning')} ${rem} ${t('seconds')}`;if(rem<=0)clearInterval(inactCountdown)},1000)},(INACT_TIMEOUT-INACT_WARN)*1000);
  inactTimer=setTimeout(()=>{toast(t('session_closed'),'info');ejectCard()},INACT_TIMEOUT*1000);
}
['click','keydown','touchstart'].forEach(e=>document.addEventListener(e,resetInactivity));

setInterval(()=>{const el=document.getElementById('clockDisplay');if(el)el.textContent=new Date().toLocaleTimeString('es-BO')},1000);

function goTo(v){document.querySelectorAll('.view').forEach(x=>x.classList.remove('active'));const el=document.getElementById('view-'+v);if(el){el.classList.add('active');ATM.currentView=v}
  // Cuando se entra a login, enfocar el campo de cuenta
  if(v==='login'){lastLoginField='account';setTimeout(()=>{const ai=document.getElementById('accountInput');if(ai)ai.focus()},100)}
  resetInactivity();
}
function toast(msg,type='info'){const box=document.getElementById('toastBox'),el=document.createElement('div');el.className='toast '+type;el.innerHTML=`<span>${{success:'✅',error:'❌',info:'ℹ️'}[type]||'ℹ️'}</span> ${msg}`;box.appendChild(el);setTimeout(()=>{el.style.opacity='0';el.style.transition='opacity 0.3s';setTimeout(()=>el.remove(),300)},3500)}
async function api(action,params={},method='GET'){try{let url=`${API}?action=${action}`,opts={method};if(method==='GET')Object.entries(params).forEach(([k,v])=>url+=`&${k}=${encodeURIComponent(v)}`);else{opts.headers={'Content-Type':'application/json'};opts.body=JSON.stringify(params)}return await(await fetch(url,opts)).json()}catch(e){toast(t('error_connection'),'error');return{success:false}}}

// ═══ CARD ANIMATION ═══
function showCardAnimation(){
  playSound('card');
  // TEORÍA DE COLAS: registrar llegada del usuario
  ATM.txCount=0;
  api('queue_start',{},'POST').then(data=>{
    if(data.success) ATM.sessionId=data.session_id;
  });
  const overlay=document.getElementById('cardAnimOverlay');overlay.classList.add('active');
  setTimeout(()=>{overlay.classList.remove('active');goTo('login')},2200);
}

// ═══ BILL ANIMATION ═══
function showBillAnimation(amount,currency){
  return new Promise(resolve=>{
    playSound('cash');
    const overlay=document.getElementById('billAnimOverlay');
    const container=document.getElementById('billsContainer');
    const sym=currency==='USD'?'$':'Bs';
    const cls=currency==='USD'?' bill-usd':'';
    container.innerHTML='';
    const billCount=Math.min(Math.ceil(amount/100),5);
    for(let i=0;i<billCount;i++){
      const bill=document.createElement('div');
      bill.className='bill'+cls;
      bill.textContent=`${sym} ${amount}`;
      bill.style.animationDelay=`${0.2+i*0.3}s`;
      container.appendChild(bill);
    }
    overlay.classList.add('active');
    setTimeout(()=>{overlay.classList.remove('active');resolve()},1500+billCount*300);
  });
}

// ═══ QR CODE ═══
function generateQR(text,canvasId){
  const canvas=document.getElementById(canvasId);if(!canvas)return;
  // Usa la librería QRious cargada desde CDN en index.html
  try{
    new QRious({element:canvas,value:text,size:130,backgroundAlpha:1,foreground:'#000000',background:'#ffffff',level:'M'});
  }catch(e){
    // Fallback si la librería no cargó
    const ctx=canvas.getContext('2d');canvas.width=130;canvas.height=130;
    ctx.fillStyle='#fff';ctx.fillRect(0,0,130,130);
    ctx.fillStyle='#000';ctx.font='10px monospace';ctx.textAlign='center';
    ctx.fillText('QR: '+text.substring(0,20),65,65);
  }
}

// ═══ DECISION VISUALIZATION ═══
// Paneles de decisión solo se muestran en el Dashboard (dashboard.html)

// ═══ LOGIN ═══
async function submitLogin(){
  const acc=document.getElementById('accountInput').value.trim();
  const pin=document.getElementById('pinInput').value.replace(/\D/g,'');
  if(!acc){toast(t('enter_account'),'error');return}
  if(pin.length!==4){toast(t('error_pin_4'),'error');return}
  document.getElementById('loginStatus').textContent=t('verifying');
  document.getElementById('loginStatus').style.color='var(--text3)';
  try{
    const data=await api('login',{account_number:acc,pin:pin},'POST');
    if(data.success){
      playSound('success');
      ATM.cardId=data.card_id;ATM.holderName=data.holder_name;ATM.bankCode=data.bank.bank_code;
      ATM.securityQuestion=data.security_question;
      document.getElementById('atmBody').className='atm-body theme-'+ATM.bankCode;
      setBankHeaders(data.bank.bank_name,ATM.bankCode);
      const info=await api('get_card_info',{card_id:ATM.cardId});if(info.success)ATM.cardInfo=info.card;
      // TEORÍA DE COLAS: registrar inicio de servicio
      if(ATM.sessionId) api('queue_service',{session_id:ATM.sessionId,card_id:ATM.cardId},'POST');
      toast(t('welcome')+', '+ATM.holderName,'success');goTo('language');
    }else{
      playSound('error');
      document.getElementById('loginStatus').textContent=data.error||'Error de autenticación';
      document.getElementById('loginStatus').style.color='#ff4466';
      document.getElementById('pinInput').value='';
      if(data.blocked)setTimeout(()=>ejectCard(),2000);
    }
  }catch(err){
    document.getElementById('loginStatus').textContent='Error de conexión al servidor';
    document.getElementById('loginStatus').style.color='#ff4466';
    toast('Error: Verifique que XAMPP esté encendido','error');
    console.error('Login error:',err);
  }
}
function setBankHeaders(n,c){const logos={bmsc:'BMSc',bnb:'BNB',bu:'BU'};document.querySelectorAll('.bank-header').forEach(el=>el.innerHTML=`<div class="bank-title">${logos[c]||'◆'} ${n}</div><div class="bank-subtitle">${t('atm_label')}</div>`)}
function selectCurrency(c){ATM.currency=c;document.getElementById('welcomeUser').textContent=`${t('welcome')}, ${ATM.holderName}  [${c}]`;goTo('menu')}

// Nueva vista: selección de idioma tras login, antes de moneda
function selectLanguage(l){
  setLang(l);
  playSound('key');
  goTo('currency');
}

// ═══ OPERATIONS ═══
// Pedir PIN de seguridad antes de cada operación transaccional
// EXCEPTO la primera vez tras el login (ya ingresó PIN al iniciar sesión)
function goToOperation(op){
  ATM.operation=op;ATM.amount='';ATM.customAmount=false;
  // Operaciones que requieren PIN de seguridad
  const needsPin=['withdrawal','deposit','transfer','pin_change'];
  if(needsPin.includes(op) && !ATM.firstOperation){
    showPinReentry(()=>{ proceedToOperation(op); });
  }else{
    // Primera operación: no pide PIN, pero marca que ya no es la primera
    if(needsPin.includes(op)) ATM.firstOperation=false;
    proceedToOperation(op);
  }
}

function proceedToOperation(op){
  const cs=ATM.currency==='USD'?'$':'Bs';
  if(op==='withdrawal'){
    document.getElementById('wAmount').textContent='0';
    document.getElementById('wCurrency').textContent=cs;
    // Estado inicial: solo se ven las opciones de monto predeterminadas
    const ad=document.getElementById('wAmountDisplay');if(ad)ad.style.display='none';
    const qg=document.getElementById('wQuickGrid');if(qg)qg.style.display='';
    const cb=document.getElementById('wConfirmBtn');if(cb)cb.style.display='none';
    const cr=document.getElementById('customAmountRow');if(cr)cr.style.display='none';
    goTo('withdrawal');
  }
  else if(op==='deposit'){document.getElementById('dAmount').textContent='0';document.getElementById('dCurrency').textContent=cs;goTo('deposit')}
  else if(op==='transfer'){document.getElementById('tAmount').textContent='0';document.getElementById('tCurrency').textContent=cs;loadTransferTargets();goTo('transfer')}
  else if(op==='balance')queryBalance();
  else if(op==='pin_change')goTo('pin-change');
  else if(op==='history')loadHistory();
  else if(op==='settings')goTo('settings');
}

// Overlay para re-ingreso de PIN
function showPinReentry(callback){
  const overlay=document.getElementById('pinReentryOverlay');
  document.getElementById('reentryPinInput').value='';
  document.getElementById('reentryPinStatus').textContent='';
  ATM.pendingAction=callback;
  overlay.classList.add('active');
  setTimeout(()=>{document.getElementById('reentryPinInput').focus()},150);
}

async function submitPinReentry(){
  const pin=document.getElementById('reentryPinInput').value.trim();
  if(pin.length!==4){toast(t('error_pin_4'),'error');return}
  document.getElementById('reentryPinStatus').textContent=t('verifying');
  document.getElementById('reentryPinStatus').style.color='var(--text3)';
  try{
    const data=await api('verify_pin_quick',{card_id:ATM.cardId,pin:pin},'POST');
    if(data.success){
      document.getElementById('pinReentryOverlay').classList.remove('active');
      playSound('success');
      const cb=ATM.pendingAction;ATM.pendingAction=null;
      if(cb)cb();
    }else{
      playSound('error');
      document.getElementById('reentryPinInput').value='';
      document.getElementById('reentryPinStatus').textContent=data.error||'PIN incorrecto';
      document.getElementById('reentryPinStatus').style.color='#ff4466';
    }
  }catch(e){
    document.getElementById('reentryPinStatus').textContent='Error de conexión';
    document.getElementById('reentryPinStatus').style.color='#ff4466';
  }
}

function cancelPinReentry(){
  document.getElementById('pinReentryOverlay').classList.remove('active');
  ATM.pendingAction=null;
}
function setQuick(a){
  ATM.amount=a.toString();ATM.customAmount=false;updateAmountDisplay();playSound('key');
  // Montos fijos van directo al retiro sin confirmación
  if(ATM.operation==='withdrawal'){
    quickWithdrawal(a);
  }
}

// Retiro rápido para montos fijos (sin modal de confirmación,
// pero SÍ pregunta si quiere imprimir recibo antes de dispensar)
function quickWithdrawal(amount){
  askPrintReceipt(()=>{ doWithdrawal(amount); });
}
function showCustomAmount(){
  ATM.customAmount=true;ATM.amount='';
  // Ocultar la grilla de montos predeterminados y mostrar el display + botón confirmar
  const qg=document.getElementById('wQuickGrid');if(qg)qg.style.display='none';
  const ad=document.getElementById('wAmountDisplay');if(ad)ad.style.display='';
  const cb=document.getElementById('wConfirmBtn');if(cb)cb.style.display='';
  const r=document.getElementById('customAmountRow');if(r)r.style.display='block';
  document.getElementById('wAmount').textContent='0';
}
function updateAmountDisplay(){const s=ATM.amount?parseFloat(ATM.amount).toLocaleString('es-BO'):'0';const t={withdrawal:'wAmount',deposit:'dAmount',transfer:'tAmount'};const el=document.getElementById(t[ATM.operation]);if(el)el.textContent=s}

// ═══ CONFIRM ═══
function showConfirmation(type,amount,callback){
  const o=document.getElementById('confirmOverlay');const msgs={withdrawal:t('confirm_withdrawal'),deposit:t('confirm_deposit'),transfer:t('confirm_transfer')};
  const cs=ATM.currency==='USD'?'$':'Bs';
  document.getElementById('confirmDetail').textContent=`${cs} ${parseFloat(amount).toLocaleString('es-BO',{minimumFractionDigits:2})}`;
  document.getElementById('confirmMessage').textContent=msgs[type]||t('confirm_title');
  ATM.pendingAction=callback;o.classList.add('active');
}
function confirmYes(){
  document.getElementById('confirmOverlay').classList.remove('active');
  // Capturamos el callback y limpiamos pendingAction ANTES de ejecutarlo,
  // así si el callback anidado usa pendingAction (ej: askPrintReceipt),
  // no lo sobrescribimos al volver de la cadena síncrona.
  const cb=ATM.pendingAction;ATM.pendingAction=null;
  if(cb)cb();
}
function confirmNo(){document.getElementById('confirmOverlay').classList.remove('active');ATM.pendingAction=null}

// ═══ PRINT RECEIPT ASK ═══
// Pregunta si quiere imprimir el recibo ANTES de dispensar el dinero.
// Guarda la respuesta en ATM.wantsPrint y ejecuta el callback con el flujo normal.
function askPrintReceipt(callback){
  const o=document.getElementById('printAskOverlay');
  ATM.pendingAction=callback;
  o.classList.add('active');
}
function answerPrintAsk(wants){
  ATM.wantsPrint=!!wants;
  document.getElementById('printAskOverlay').classList.remove('active');
  try{playSound('key')}catch(e){}
  const cb=ATM.pendingAction;ATM.pendingAction=null;
  if(cb){ try{ cb(); }catch(e){ console.error('answerPrintAsk cb error:',e); toast(t('error_connection'),'error'); goTo('menu'); } }
}

// ═══ SECURITY QUESTION ═══
function checkSecurityAndProceed(amount,callback){
  // Check if amount > 2000 (large transfer threshold)
  if(amount>=2000&&ATM.securityQuestion){
    const o=document.getElementById('securityOverlay');
    document.getElementById('secQuestion').textContent=ATM.securityQuestion;
    document.getElementById('secAnswer').value='';
    ATM.pendingAction=callback;
    o.classList.add('active');
  }else{callback()}
}
async function submitSecurityAnswer(){
  const answer=document.getElementById('secAnswer').value.trim();
  if(!answer){toast(t('security_answer'),'error');return}
  const data=await api('verify_security',{card_id:ATM.cardId,answer:answer},'POST');
  document.getElementById('securityOverlay').classList.remove('active');
  if(data.success){const cb=ATM.pendingAction;ATM.pendingAction=null;if(cb)cb();}
  else{playSound('error');toast(data.error,'error')}
}
function cancelSecurity(){document.getElementById('securityOverlay').classList.remove('active');ATM.pendingAction=null}

// ═══ WITHDRAWAL ═══

// Validación de montos de retiro según cortes disponibles:
// BOB (Bolivianos): billetes de 200, 100, 50, 20, 10 → mínimo Bs 10, múltiplo de 10
// USD (Dólares): billetes de 100, 50, 20, 10, 5, 1 → mínimo $1, entero (sin centavos)
// Retorna null si es válido, o un mensaje de error si no.
function validateWithdrawalAmount(amount, currency){
  if(!amount||isNaN(amount)||amount<=0) return t('error_invalid_amount');
  if(currency==='BOB'){
    if(amount<10) return t('error_min_bob');
    // Debe ser entero y múltiplo de 10 (el corte más pequeño es 10)
    if(!Number.isInteger(amount)||amount%10!==0) return t('error_bob_denom');
  }else if(currency==='USD'){
    if(amount<1) return t('error_min_usd');
    // Debe ser entero (el corte más pequeño es 1, no hay centavos)
    if(!Number.isInteger(amount)) return t('error_usd_denom');
  }
  return null;
}

function submitWithdrawal(){
  const amount=parseFloat(ATM.amount);
  const errMsg=validateWithdrawalAmount(amount,ATM.currency);
  if(errMsg){toast(errMsg,'error');playSound('error');return}
  // Flujo: confirmar monto → (pregunta de seguridad si aplica) → preguntar si imprime → procesar
  showConfirmation('withdrawal',amount,()=>{
    checkSecurityAndProceed(amount,()=>{
      askPrintReceipt(()=>{ doWithdrawal(amount); });
    });
  });
}

// Ejecuta el retiro después de todas las confirmaciones
async function doWithdrawal(amount){
  goTo('processing');
  try{
    const data=await api('withdrawal',{card_id:ATM.cardId,amount,currency:ATM.currency},'POST');
    setTimeout(async()=>{
      if(data.success){
        await showBillAnimation(amount,ATM.currency);
        showReceipt(data.transaction,t('type_withdrawal'));
      }else{
        playSound('error');toast(data.error||t('error_connection'),'error');
        // Volver al retiro en modo "otra cantidad" para que corrija
        goTo('withdrawal');showCustomAmount();
      }
    },1600);
  }catch(e){
    playSound('error');toast(t('error_connection'),'error');
    goTo('withdrawal');showCustomAmount();
  }
}

// ═══ DEPOSIT ═══
function submitDeposit(){
  const amount=parseFloat(ATM.amount);if(!amount||amount<=0){toast(t('error_invalid_amount'),'error');return}
  showConfirmation('deposit',amount,async()=>{
    goTo('processing');
    const data=await api('deposit',{card_id:ATM.cardId,amount,currency:ATM.currency},'POST');
    setTimeout(()=>{if(data.success)showReceipt(data.transaction,t('type_deposit'));else{playSound('error');toast(data.error,'error');goTo('deposit')}},1600);
  });
}

// ═══ TRANSFER ═══
async function loadTransferTargets(){
  const data=await api('get_transfer_targets',{card_id:ATM.cardId});if(!data.success)return;
  const sel=document.getElementById('transferTarget');
  sel.innerHTML=`<option value="">${t('select_target')}</option>`+data.accounts.map(a=>`<option value="${a.id}">${a.first_name} ${a.last_name} — ${a.bank_name} (${a.account_number})</option>`).join('');
}
function submitTransfer(){
  const amount=parseFloat(ATM.amount);const tid=document.getElementById('transferTarget').value;
  if(!tid){toast(t('error_select_target'),'error');return}
  if(!amount||amount<=0){toast(t('error_invalid_amount'),'error');return}
  showConfirmation('transfer',amount,()=>{
    checkSecurityAndProceed(amount,async()=>{
      goTo('processing');
      const data=await api('transfer',{card_id:ATM.cardId,target_account_id:parseInt(tid),amount,currency:ATM.currency},'POST');
      setTimeout(()=>{if(data.success)showReceipt(data.transaction,t('type_transfer'));else{playSound('error');toast(data.error,'error');goTo('transfer')}},2000);
    });
  });
}

// ═══ BALANCE ═══
async function queryBalance(){goTo('processing');const data=await api('balance_inquiry',{card_id:ATM.cardId});setTimeout(()=>{if(data.success){ATM.txCount++;const b=data.balance;document.getElementById('balBOB').textContent=`Bs ${parseFloat(b.bob).toLocaleString('es-BO',{minimumFractionDigits:2})}`;document.getElementById('balUSD').textContent=`$ ${parseFloat(b.usd).toLocaleString('es-BO',{minimumFractionDigits:2})}`;document.getElementById('balExtra').innerHTML=`${b.holder_name} | •••• ${b.card_last4}<br>${t('available_today')}: Bs ${parseFloat(b.available_today).toLocaleString('es-BO',{minimumFractionDigits:2})}`;playSound('success');goTo('balance')}else{toast(data.error,'error');goTo('menu')}},1200)}

// ═══ PIN CHANGE ═══
async function submitPinChange(){const c=document.getElementById('currentPinInput').value,n=document.getElementById('newPinInput').value,p=document.getElementById('confirmPinInput').value;if(c.length!==4||n.length!==4){toast(t('error_pin_4'),'error');return}if(n!==p){toast(t('pins_no_match'),'error');return}if(c===n){toast(t('pin_different'),'error');return}const d=await api('change_pin',{card_id:ATM.cardId,current_pin:c,new_pin:n},'POST');if(d.success){playSound('success');toast(t('pin_changed'),'success');goTo('menu')}else{playSound('error');toast(d.error,'error')}}

// ═══ HISTORY ═══
async function loadHistory(){goTo('history');const data=await api('get_transactions',{card_id:ATM.cardId,limit:10});const list=document.getElementById('historyList');if(!data.success||!data.transactions.length){list.innerHTML=`<div class="small-text" style="padding:20px 0">${t('no_transactions')}</div>`;return}
const icons={withdrawal:'💸',deposit:'💰',transfer:'🔄',balance_inquiry:'👁️',pin_change:'🔑',donation:'❤️'};
list.innerHTML=data.transactions.map(tx=>{const neg=tx.tx_type==='withdrawal'||tx.tx_type==='transfer'||tx.tx_type==='donation';const cs=tx.currency==='USD'?'$':'Bs';const amt=tx.amount?`${neg?'-':'+'}${cs} ${parseFloat(tx.amount).toLocaleString('es-BO',{minimumFractionDigits:2})}`:'--';return `<div class="history-item"><span class="h-icon" style="font-size:18px;margin-right:10px">${icons[tx.tx_type]||'📝'}</span><div style="flex:1"><div style="font-weight:600;font-size:13px">${t('type_'+tx.tx_type)||tx.tx_type}</div><div style="font-size:10px;color:var(--text3);font-family:'JetBrains Mono',monospace">${tx.created_at}</div></div><div class="h-amount ${neg?'h-neg':'h-pos'}" style="font-family:'JetBrains Mono',monospace;font-weight:600;font-size:14px">${amt}</div></div>`}).join('')}

// ═══ CUSTOM LIMIT ═══
async function saveCustomLimit(){
  const lim=parseFloat(document.getElementById('limitInput').value);
  if(!lim||lim<100){toast('Mínimo 100','error');return}
  const d=await api('set_custom_limit',{card_id:ATM.cardId,limit:lim},'POST');
  if(d.success){playSound('success');toast(d.message,'success')}else toast(d.error,'error');
}

// ═══ RECEIPT ═══
function showReceipt(tx,label){
  ATM.txCount++; // TEORÍA DE COLAS: contar transacciones de esta sesión
  const bankNames={bmsc:'Banco Mercantil Santa Cruz',bnb:'Banco Nacional de Bolivia',bu:'Banco Unión'};
  const bn=bankNames[ATM.bankCode]||'NexoATM';const cs=tx.currency==='USD'?'$':'Bs';
  let rows=`<div class="r-row"><span>${t('date')}:</span><span>${tx.timestamp}</span></div><div class="r-row"><span>${t('ref')}:</span><span>${tx.tx_code}</span></div><div class="r-row"><span>${t('card')}:</span><span>•••• ${tx.card_last4}</span></div><div class="r-divider"></div><div class="r-row"><span>${t('operation')}:</span><span>${label}</span></div>`;
  if(tx.amount)rows+=`<div class="r-row r-total"><span>${t('amount')}:</span><span>${cs} ${parseFloat(tx.amount).toLocaleString('es-BO',{minimumFractionDigits:2})}</span></div>`;
  if(tx.target_account)rows+=`<div class="r-row"><span>${t('target')}:</span><span>${tx.target_account}</span></div>`;
  rows+=`<div class="r-divider"></div><div class="r-row"><span>${t('balance_label')}:</span><span style="font-weight:700">${cs} ${parseFloat(tx.balance_after).toLocaleString('es-BO',{minimumFractionDigits:2})}</span></div>`;
  rows+=`<div class="qr-container"><canvas id="receiptQR"></canvas><p>${t('scan_qr')}</p></div>`;
  document.getElementById('receiptContent').innerHTML=`<div class="r-header"><h4>${bn}</h4><p>NexoATM — ${t('subtitle')}</p></div>${rows}<div style="text-align:center;margin-top:10px;font-size:10px;color:#999">${t('receipt_thanks')}</div>`;
  setTimeout(()=>generateQR(`NexoATM|${tx.tx_code}|${tx.amount}|${tx.currency}|${tx.timestamp}`,'receiptQR'),100);
  playSound('success');toast(t('success'),'success');goTo('receipt');
  // Si eligió "Sí, imprimir" antes del retiro, lanzar la ventana de impresión automáticamente
  if(ATM.wantsPrint){
    setTimeout(()=>{printReceipt();ATM.wantsPrint=false;},700);
  }
}
function printReceipt(){const c=document.getElementById('receiptContent').innerHTML;const w=window.open('','_blank','width=420,height=700');w.document.write(`<html><head><title>NexoATM Recibo</title><style>body{font-family:monospace;font-size:13px;padding:24px;max-width:380px;margin:0 auto}.r-row{display:flex;justify-content:space-between;padding:4px 0}.r-divider{border-top:1px dashed #999;margin:8px 0}.r-header{text-align:center;border-bottom:1px dashed #999;padding-bottom:10px;margin-bottom:10px}.r-total{font-weight:bold;font-size:15px}.qr-container{text-align:center;margin-top:12px}</style></head><body>${c}<script>setTimeout(()=>window.print(),500)<\/script></body></html>`)}

// ═══ EJECT ═══
function ejectCard(){
  playSound('card');
  // TEORÍA DE COLAS: registrar fin de servicio
  if(ATM.sessionId) api('queue_end',{session_id:ATM.sessionId,transactions_count:ATM.txCount},'POST');
  ATM.cardId=null;ATM.cardInfo=null;ATM.bankCode=null;ATM.pin='';ATM.amount='';ATM.operation='';ATM.holderName='';ATM.currency='BOB';ATM.pendingAction=null;ATM.securityQuestion=null;ATM.sessionId=null;ATM.txCount=0;ATM.firstOperation=true;ATM.wantsPrint=false;
  lastLoginField='account';
  clearTimeout(inactTimer);clearTimeout(inactWarnTimer);clearInterval(inactCountdown);
  const bar=document.getElementById('inactivityBar');if(bar)bar.classList.remove('active');
  document.getElementById('atmBody').className='atm-body';
  document.getElementById('accountInput').value='';document.getElementById('pinInput').value='';
  document.getElementById('loginStatus').textContent='';
  toast(t('eject_card'),'info');goTo('idle');
}

// ═══ SISTEMA DE TECLADO — REESCRITO COMPLETO ═══

// Variable que guarda cuál campo de login estaba activo
let lastLoginField = 'account';

// Auto-formato de cuenta con guiones
function formatAccount(raw) {
  const d = raw.replace(/\D/g, '').substring(0, 12);
  let o = '';
  for (let i = 0; i < d.length; i++) {
    if (i > 0 && i % 4 === 0) o += '-';
    o += d[i];
  }
  return o;
}

// Rastrear qué campo tiene foco
document.getElementById('accountInput').addEventListener('focus', function() { lastLoginField = 'account'; });
document.getElementById('pinInput').addEventListener('focus', function() { lastLoginField = 'pin'; });

// Auto-formato cuando el usuario escribe en cuenta con teclado físico
document.getElementById('accountInput').addEventListener('input', function() {
  const pos = this.selectionStart;
  const old = this.value.length;
  this.value = formatAccount(this.value);
  const diff = this.value.length - old;
  this.setSelectionRange(pos + diff, pos + diff);
});

// Enter en el campo de re-ingreso de PIN ejecuta la verificación
const reentryInput = document.getElementById('reentryPinInput');
if (reentryInput) {
  reentryInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); submitPinReentry(); }
    if (e.key === 'Escape') { e.preventDefault(); cancelPinReentry(); }
  });
}

// ═══ TECLADO VIRTUAL (botones en pantalla del cajero) ═══
// Los botones tienen onmousedown="event.preventDefault()" en el HTML
// para que NO roben el foco del input activo

function keyPress(d) {
  playSound('key');
  const v = ATM.currentView;

  if (v === 'login') {
    if (lastLoginField === 'pin') {
      const p = document.getElementById('pinInput');
      if (p.value.length < 4) p.value += d;
    } else {
      const a = document.getElementById('accountInput');
      const raw = a.value.replace(/\D/g, '');
      if (raw.length < 12) {
        a.value = formatAccount(raw + d);
      }
      if (a.value.replace(/\D/g, '').length >= 12) {
        lastLoginField = 'pin';
        document.getElementById('pinInput').focus();
      }
    }
    return;
  }

  if (['withdrawal', 'deposit', 'transfer'].includes(v)) {
    if (ATM.amount.length < 8) {
      ATM.amount += d;
      updateAmountDisplay();
    }
  }
}

function keyCancel() {
  const v = ATM.currentView;
  if (v === 'login') {
    if (lastLoginField === 'pin') {
      document.getElementById('pinInput').value = '';
    } else {
      document.getElementById('accountInput').value = '';
    }
  } else if (['withdrawal', 'deposit', 'transfer'].includes(v)) {
    ATM.amount = '';
    updateAmountDisplay();
  } else if (v === 'menu') {
    ejectCard();
  } else {
    goTo('menu');
  }
}

function keyEnter() {
  const v = ATM.currentView;
  if (v === 'login') {
    if (lastLoginField === 'account') {
      lastLoginField = 'pin';
      document.getElementById('pinInput').focus();
    } else {
      submitLogin();
    }
    return;
  }
  if (v === 'withdrawal') submitWithdrawal();
  else if (v === 'deposit') submitDeposit();
  else if (v === 'transfer') submitTransfer();
}

// ═══ TECLADO FÍSICO DEL PC ═══
// REGLA: si un input HTML tiene foco, NO interceptar números.
// El navegador los escribe solo. Solo capturar Enter y Escape.

document.addEventListener('keydown', function(e) {
  const el = document.activeElement;
  const enInput = el && el.tagName === 'INPUT';

  if (enInput) {
    // Dentro de un input: solo Enter y Escape
    if (e.key === 'Enter') {
      e.preventDefault();
      keyEnter();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      keyCancel();
    }
    // NO tocar nada más. El navegador escribe normalmente.
    return;
  }

  // Fuera de inputs (menú, montos sin input visible, etc.)
  e.preventDefault();
  if (e.key >= '0' && e.key <= '9') keyPress(e.key);
  else if (e.key === 'Enter') keyEnter();
  else if (e.key === 'Escape') keyCancel();
  else if (e.key === 'Backspace') {
    if (['withdrawal', 'deposit', 'transfer'].includes(ATM.currentView)) {
      ATM.amount = ATM.amount.slice(0, -1);
      updateAmountDisplay();
    }
  }
});

document.getElementById('clockDisplay').textContent=new Date().toLocaleTimeString('es-BO');
updateAllTexts();
