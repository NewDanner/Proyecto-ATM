<?php
<<<<<<< HEAD
header('Content-Type:application/json;charset=utf-8');
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:GET,POST');
header('Access-Control-Allow-Headers:Content-Type');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(200);exit;}

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/decisions.php';

$action=$_GET['action']??$_POST['action']??'';
switch($action){
    case 'login':login();break;
    case 'get_card_info':getCardInfo();break;
    case 'withdrawal':processWithdrawal();break;
    case 'deposit':processDeposit();break;
    case 'transfer':processTransfer();break;
    case 'balance_inquiry':balanceInquiry();break;
    case 'change_pin':changePin();break;
    case 'get_transactions':getTransactions();break;
    case 'get_transfer_targets':getTransferTargets();break;
    case 'get_favorites':getFavorites();break;
    case 'add_favorite':addFavorite();break;
    case 'verify_security':verifySecurity();break;
    case 'donation':processDonation();break;
    case 'set_custom_limit':setCustomLimit();break;
    case 'get_stats':getStats();break;
    case 'get_audit_log':getAuditLog();break;
    case 'get_decision_log':getDecisionLog();break;
    default:jsonResponse(['success'=>false,'error'=>"Acción no válida"],400);
}

function login(){
    $in=json_decode(file_get_contents('php://input'),true);
    $acc=trim($in['account_number']??'');$pin=$in['pin']??'';
    if(!$acc||strlen($pin)!==4)jsonResponse(['success'=>false,'error'=>'Ingrese cuenta y PIN de 4 dígitos'],400);
    $st=db()->prepare("SELECT a.id as account_id,a.bank_id,a.daily_limit,a.custom_limit,b.code as bank_code,b.name as bank_name,b.short_name,b.primary_color,b.secondary_color,b.accent_color,b.logo_text FROM accounts a JOIN banks b ON a.bank_id=b.id WHERE a.account_number=? AND a.status='active'");
    $st->execute([$acc]);$account=$st->fetch();
    if(!$account){auditLog('warning',"Login fallido: cuenta [{$acc}]");jsonResponse(['success'=>false,'error'=>'Cuenta no encontrada']);}
    $st=db()->prepare("SELECT c.id FROM cards c WHERE c.account_id=? AND c.card_status!='expired' LIMIT 1");
    $st->execute([$account['account_id']]);$card=$st->fetch();
    if(!$card)jsonResponse(['success'=>false,'error'=>'Sin tarjeta activa']);
    $decision=DecisionEngine::evaluatePinAuth($card['id'],$pin);
    if($decision['decision']==='APPROVED'){
        $st=db()->prepare("SELECT holder_name FROM cards WHERE id=?");$st->execute([$card['id']]);$cd=$st->fetch();
        $st=db()->prepare("SELECT security_question FROM customers cu JOIN accounts a ON cu.id=a.customer_id WHERE a.id=?");
        $st->execute([$account['account_id']]);$sq=$st->fetch();
        jsonResponse(['success'=>true,'card_id'=>$card['id'],'holder_name'=>$cd['holder_name'],'bank'=>$account,'security_question'=>$sq['security_question']??null,'decision'=>$decision]);
    }else{
        $blocked=strpos($decision['reason']??'','bloqueada')!==false;
        jsonResponse(['success'=>false,'error'=>$decision['reason'],'blocked'=>$blocked,'decision'=>$decision]);
    }
}

function getCardInfo(){
    $cid=intval($_GET['card_id']??0);
    $st=db()->prepare("SELECT c.id as card_id,c.card_number,c.holder_name,c.expiry_date,c.card_status,cn.code as network_code,cn.name as network_name,a.id as account_id,a.account_number,a.balance_bob,a.balance_usd,a.daily_limit,a.custom_limit,a.withdrawn_today,a.account_type,b.id as bank_id,b.code as bank_code,b.name as bank_name,b.short_name,b.primary_color,b.secondary_color,b.accent_color,b.logo_text,cu.first_name,cu.last_name,cu.preferred_lang,cu.id as customer_id FROM cards c JOIN accounts a ON c.account_id=a.id JOIN card_networks cn ON c.network_id=cn.id JOIN banks b ON a.bank_id=b.id JOIN customers cu ON a.customer_id=cu.id WHERE c.id=?");
    $st->execute([$cid]);$info=$st->fetch();
    if(!$info)jsonResponse(['success'=>false,'error'=>'No encontrada'],404);
    jsonResponse(['success'=>true,'card'=>$info]);
}

function processWithdrawal(){
    $in=json_decode(file_get_contents('php://input'),true);
    $cid=intval($in['card_id']??0);$amount=floatval($in['amount']??0);$cur=($in['currency']??'BOB')==='USD'?'USD':'BOB';
    $bc=$cur==='USD'?'balance_usd':'balance_bob';
    $pdo=db();$pdo->beginTransaction();
    try{
        $d=DecisionEngine::evaluateWithdrawal($cid,$amount,$cur);
        if($d['decision']!=='APPROVED'){$pdo->rollBack();jsonResponse(['success'=>false,'error'=>$d['reason'],'decision'=>$d]);}
        $data=$d['data'];$bb=$data['balance'];$ba=$bb-$amount;$tx=generateTxCode();
        $pdo->prepare("UPDATE accounts SET {$bc}=?,withdrawn_today=withdrawn_today+? WHERE id=?")->execute([$ba,$amount,$data['account_id']]);
        $pdo->prepare("INSERT INTO transactions(tx_code,card_id,account_id,bank_id,tx_type,currency,amount,balance_before,balance_after,description,status)VALUES(?,?,?,?,'withdrawal',?,?,?,?,'Retiro en cajero','completed')")->execute([$tx,$cid,$data['account_id'],$data['bank_id'],$cur,$amount,$bb,$ba]);
        $pdo->commit();auditLog('success',"RETIRO:{$cur} {$amount}",$cid);
        jsonResponse(['success'=>true,'transaction'=>['tx_code'=>$tx,'type'=>'withdrawal','amount'=>$amount,'currency'=>$cur,'balance_before'=>$bb,'balance_after'=>$ba,'card_last4'=>substr(str_replace(' ','',$data['card_number']),-4),'holder_name'=>$data['holder_name'],'timestamp'=>date('Y-m-d H:i:s')],'decision'=>$d]);
    }catch(Exception $e){$pdo->rollBack();jsonResponse(['success'=>false,'error'=>'Error'],500);}
}

function processDeposit(){
    $in=json_decode(file_get_contents('php://input'),true);
    $cid=intval($in['card_id']??0);$amount=floatval($in['amount']??0);$cur=($in['currency']??'BOB')==='USD'?'USD':'BOB';
    $bc=$cur==='USD'?'balance_usd':'balance_bob';
    $pdo=db();$pdo->beginTransaction();
    try{
        $d=DecisionEngine::evaluateDeposit($cid,$amount);
        if($d['decision']!=='APPROVED'){$pdo->rollBack();jsonResponse(['success'=>false,'error'=>$d['reason'],'decision'=>$d]);}
        $data=$d['data'];$bb=$cur==='USD'?$data['balance_usd']:$data['balance_bob'];$ba=$bb+$amount;$tx=generateTxCode();
        $pdo->prepare("UPDATE accounts SET {$bc}=? WHERE id=?")->execute([$ba,$data['account_id']]);
        $pdo->prepare("INSERT INTO transactions(tx_code,card_id,account_id,bank_id,tx_type,currency,amount,balance_before,balance_after,description,status)VALUES(?,?,?,?,'deposit',?,?,?,?,'Depósito en cajero','completed')")->execute([$tx,$cid,$data['account_id'],$data['bank_id'],$cur,$amount,$bb,$ba]);
        $pdo->commit();auditLog('success',"DEPÓSITO:{$cur} {$amount}",$cid);
        jsonResponse(['success'=>true,'transaction'=>['tx_code'=>$tx,'type'=>'deposit','amount'=>$amount,'currency'=>$cur,'balance_before'=>$bb,'balance_after'=>$ba,'card_last4'=>substr(str_replace(' ','',$data['card_number']),-4),'holder_name'=>$data['holder_name'],'timestamp'=>date('Y-m-d H:i:s')]]);
    }catch(Exception $e){$pdo->rollBack();jsonResponse(['success'=>false,'error'=>'Error'],500);}
}

function processTransfer(){
    $in=json_decode(file_get_contents('php://input'),true);
    $cid=intval($in['card_id']??0);$tid=intval($in['target_account_id']??0);$amount=floatval($in['amount']??0);$cur=($in['currency']??'BOB')==='USD'?'USD':'BOB';
    $bc=$cur==='USD'?'balance_usd':'balance_bob';
    $pdo=db();$pdo->beginTransaction();
    try{
        $d=DecisionEngine::evaluateTransfer($cid,$tid,$amount,$cur);
        if($d['decision']!=='APPROVED'){$pdo->rollBack();jsonResponse(['success'=>false,'error'=>$d['reason'],'decision'=>$d]);}
        $src=$d['source'];$tgt=$d['target'];$sb=$src['balance'];$sa=$sb-$amount;
        $ta=($cur==='USD'?$tgt['balance_usd']:$tgt['balance_bob'])+$amount;$tx=generateTxCode();
        $pdo->prepare("UPDATE accounts SET {$bc}=? WHERE id=?")->execute([$sa,$src['account_id']]);
        $pdo->prepare("UPDATE accounts SET {$bc}=? WHERE id=?")->execute([$ta,$tid]);
        $pdo->prepare("INSERT INTO transactions(tx_code,card_id,account_id,bank_id,tx_type,currency,amount,balance_before,balance_after,target_account_id,description,status)VALUES(?,?,?,?,'transfer',?,?,?,?,?,?,'completed')")->execute([$tx,$cid,$src['account_id'],$src['bank_id'],$cur,$amount,$sb,$sa,$tid,"Transferencia a ".$tgt['account_number']]);
        $pdo->commit();auditLog('success',"TRANSFER:{$cur} {$amount}",$cid);
        jsonResponse(['success'=>true,'transaction'=>['tx_code'=>$tx,'type'=>'transfer','amount'=>$amount,'currency'=>$cur,'balance_before'=>$sb,'balance_after'=>$sa,'target_account'=>$tgt['account_number'],'card_last4'=>substr(str_replace(' ','',$src['card_number']),-4),'holder_name'=>$src['holder_name'],'timestamp'=>date('Y-m-d H:i:s')]]);
    }catch(Exception $e){$pdo->rollBack();jsonResponse(['success'=>false,'error'=>'Error'],500);}
}

function balanceInquiry(){
    $cid=intval($_GET['card_id']??0);
    $st=db()->prepare("SELECT c.id,a.id as account_id,a.balance_bob,a.balance_usd,a.daily_limit,a.custom_limit,a.withdrawn_today,a.account_type,a.bank_id,c.card_number,c.holder_name FROM cards c JOIN accounts a ON c.account_id=a.id WHERE c.id=?");
    $st->execute([$cid]);$d=$st->fetch();if(!$d)jsonResponse(['success'=>false,'error'=>'No encontrada'],404);
    $tx=generateTxCode();
    db()->prepare("INSERT INTO transactions(tx_code,card_id,account_id,bank_id,tx_type,currency,balance_before,balance_after,description,status)VALUES(?,?,?,?,'balance_inquiry','BOB',?,?,'Consulta de saldo','completed')")->execute([$tx,$cid,$d['account_id'],$d['bank_id'],$d['balance_bob'],$d['balance_bob']]);
    $lim=$d['custom_limit']??$d['daily_limit'];
    jsonResponse(['success'=>true,'balance'=>['bob'=>$d['balance_bob'],'usd'=>$d['balance_usd'],'daily_limit'=>$lim,'available_today'=>$lim-$d['withdrawn_today'],'account_type'=>$d['account_type'],'holder_name'=>$d['holder_name'],'card_last4'=>substr(str_replace(' ','',$d['card_number']),-4)],'tx_code'=>$tx]);
}

function changePin(){
    $in=json_decode(file_get_contents('php://input'),true);
    $cid=intval($in['card_id']??0);$cp=$in['current_pin']??'';$np=$in['new_pin']??'';
    if(!$cid||strlen($cp)!==4||strlen($np)!==4)jsonResponse(['success'=>false,'error'=>'Datos inválidos'],400);
    if($cp===$np)jsonResponse(['success'=>false,'error'=>'El nuevo PIN debe ser diferente']);
    $st=db()->prepare("SELECT pin_hash FROM cards WHERE id=? AND card_status='active'");$st->execute([$cid]);$c=$st->fetch();
    if(!$c||$cp!==$c['pin_hash'])jsonResponse(['success'=>false,'error'=>'PIN actual incorrecto']);
    db()->prepare("UPDATE cards SET pin_hash=? WHERE id=?")->execute([$np,$cid]);
    auditLog('success',"Cambio PIN ID:{$cid}",$cid);
    jsonResponse(['success'=>true,'message'=>'PIN cambiado exitosamente']);
}

function verifySecurity(){
    $in=json_decode(file_get_contents('php://input'),true);
    $cid=intval($in['card_id']??0);$answer=strtolower(trim($in['answer']??''));
    $st=db()->prepare("SELECT cu.security_answer FROM customers cu JOIN accounts a ON cu.id=a.customer_id JOIN cards c ON c.account_id=a.id WHERE c.id=?");
    $st->execute([$cid]);$d=$st->fetch();
    if(!$d)jsonResponse(['success'=>false,'error'=>'No encontrado']);
    $ok=strtolower(trim($d['security_answer']??''))===$answer;
    auditLog($ok?'success':'warning',($ok?'Pregunta seguridad OK':'Pregunta seguridad FALLIDA')." card:{$cid}",$cid);
    jsonResponse(['success'=>$ok,'error'=>$ok?null:'Respuesta incorrecta']);
}

function getFavorites(){
    $cid=intval($_GET['card_id']??0);
    $st=db()->prepare("SELECT f.id,f.alias,a.account_number,a.account_type,b.short_name as bank_name,cu.first_name,cu.last_name FROM favorites f JOIN accounts a ON f.target_account_id=a.id JOIN banks b ON a.bank_id=b.id JOIN customers cu ON a.customer_id=cu.id JOIN cards c ON c.account_id IN(SELECT id FROM accounts WHERE customer_id=f.customer_id) WHERE c.id=?");
    $st->execute([$cid]);
    jsonResponse(['success'=>true,'favorites'=>$st->fetchAll()]);
}

function addFavorite(){
    $in=json_decode(file_get_contents('php://input'),true);
    $cid=intval($in['card_id']??0);$tid=intval($in['target_account_id']??0);$alias=$in['alias']??'';
    $st=db()->prepare("SELECT cu.id FROM customers cu JOIN accounts a ON cu.id=a.customer_id JOIN cards c ON c.account_id=a.id WHERE c.id=?");
    $st->execute([$cid]);$cu=$st->fetch();if(!$cu)jsonResponse(['success'=>false,'error'=>'No encontrado']);
    try{db()->prepare("INSERT INTO favorites(customer_id,target_account_id,alias)VALUES(?,?,?)")->execute([$cu['id'],$tid,$alias]);
        jsonResponse(['success'=>true,'message'=>'Favorito guardado']);
    }catch(Exception $e){jsonResponse(['success'=>false,'error'=>'Ya existe como favorito']);}
}

function processDonation(){
    $in=json_decode(file_get_contents('php://input'),true);
    $cid=intval($in['card_id']??0);$amount=floatval($in['amount']??0);$cur=($in['currency']??'BOB')==='USD'?'USD':'BOB';
    $cause=$in['cause']??'Causa social';$bc=$cur==='USD'?'balance_usd':'balance_bob';
    $pdo=db();$pdo->beginTransaction();
    try{
        $st=$pdo->prepare("SELECT c.*,a.id as account_id,a.{$bc} as balance,a.bank_id FROM cards c JOIN accounts a ON c.account_id=a.id WHERE c.id=? FOR UPDATE");
        $st->execute([$cid]);$d=$st->fetch();
        if(!$d||$amount<=0||$amount>$d['balance']){$pdo->rollBack();jsonResponse(['success'=>false,'error'=>'Fondos insuficientes o monto inválido']);}
        $bb=$d['balance'];$ba=$bb-$amount;$tx=generateTxCode();
        $pdo->prepare("UPDATE accounts SET {$bc}=? WHERE id=?")->execute([$ba,$d['account_id']]);
        $pdo->prepare("INSERT INTO transactions(tx_code,card_id,account_id,bank_id,tx_type,currency,amount,balance_before,balance_after,description,status)VALUES(?,?,?,?,'donation',?,?,?,?,?,'completed')")->execute([$tx,$cid,$d['account_id'],$d['bank_id'],$cur,$amount,$bb,$ba,"Donación: {$cause}"]);
        $pdo->commit();auditLog('success',"DONACIÓN:{$cur} {$amount} para {$cause}",$cid);
        jsonResponse(['success'=>true,'transaction'=>['tx_code'=>$tx,'type'=>'donation','amount'=>$amount,'currency'=>$cur,'balance_before'=>$bb,'balance_after'=>$ba,'card_last4'=>substr(str_replace(' ','',$d['card_number']),-4),'holder_name'=>$d['holder_name'],'timestamp'=>date('Y-m-d H:i:s'),'cause'=>$cause]]);
    }catch(Exception $e){$pdo->rollBack();jsonResponse(['success'=>false,'error'=>'Error'],500);}
}

function setCustomLimit(){
    $in=json_decode(file_get_contents('php://input'),true);
    $cid=intval($in['card_id']??0);$limit=floatval($in['limit']??0);
    if($limit<100||$limit>50000)jsonResponse(['success'=>false,'error'=>'Límite debe ser entre 100 y 50,000']);
    $st=db()->prepare("UPDATE accounts a JOIN cards c ON c.account_id=a.id SET a.custom_limit=? WHERE c.id=?");
    $st->execute([$limit,$cid]);auditLog('info',"Límite personalizado: {$limit} card:{$cid}",$cid);
    jsonResponse(['success'=>true,'message'=>'Límite actualizado','new_limit'=>$limit]);
}

function getTransactions(){
    $cid=$_GET['card_id']??null;$lim=min(intval($_GET['limit']??20),100);
    if($cid){$st=db()->prepare("SELECT t.*,b.short_name as bank_name FROM transactions t JOIN banks b ON t.bank_id=b.id WHERE t.card_id=? ORDER BY t.created_at DESC LIMIT ?");$st->execute([intval($cid),$lim]);}
    else{$st=db()->prepare("SELECT t.*,b.short_name as bank_name,c.card_number,c.holder_name FROM transactions t JOIN banks b ON t.bank_id=b.id JOIN cards c ON t.card_id=c.id ORDER BY t.created_at DESC LIMIT ?");$st->execute([$lim]);}
    jsonResponse(['success'=>true,'transactions'=>$st->fetchAll()]);
}

function getTransferTargets(){
    $cid=intval($_GET['card_id']??0);
    $st=db()->prepare("SELECT account_id FROM cards WHERE id=?");$st->execute([$cid]);$c=$st->fetch();
    $st=db()->prepare("SELECT a.id,a.account_number,a.account_type,b.short_name as bank_name,cu.first_name,cu.last_name FROM accounts a JOIN banks b ON a.bank_id=b.id JOIN customers cu ON a.customer_id=cu.id WHERE a.id!=? AND a.status='active' ORDER BY b.name");
    $st->execute([$c['account_id']??0]);jsonResponse(['success'=>true,'accounts'=>$st->fetchAll()]);
}

function getStats(){
    $today=date('Y-m-d');
    $ts=db()->prepare("SELECT COUNT(*)as c,COALESCE(SUM(amount),0)as t FROM transactions WHERE DATE(created_at)=?");$ts->execute([$today]);$ts=$ts->fetch();
    $ac=db()->query("SELECT COUNT(*)as c FROM cards WHERE card_status='active'")->fetch()['c'];
    $bs=db()->query("SELECT b.short_name,b.primary_color,COUNT(t.id)as tx_count,COALESCE(SUM(t.amount),0)as tx_total FROM banks b LEFT JOIN transactions t ON b.id=t.bank_id AND DATE(t.created_at)=CURDATE() GROUP BY b.id")->fetchAll();
    $rt=db()->query("SELECT t.tx_code,t.tx_type,t.amount,t.currency,t.created_at,c.card_number,c.holder_name,b.short_name as bank_name,b.primary_color FROM transactions t JOIN cards c ON t.card_id=c.id JOIN banks b ON t.bank_id=b.id ORDER BY t.created_at DESC LIMIT 15")->fetchAll();
    $ns=db()->query("SELECT cn.name,cn.code,COUNT(c.id)as card_count FROM card_networks cn LEFT JOIN cards c ON cn.id=c.network_id GROUP BY cn.id")->fetchAll();
    // Hourly stats for chart
    $hs=db()->query("SELECT HOUR(created_at)as h,COUNT(*)as c,COALESCE(SUM(amount),0)as t FROM transactions WHERE DATE(created_at)=CURDATE() GROUP BY HOUR(created_at) ORDER BY h")->fetchAll();
    // Type distribution
    $td=db()->query("SELECT tx_type,COUNT(*)as c FROM transactions WHERE DATE(created_at)=CURDATE() GROUP BY tx_type")->fetchAll();
    jsonResponse(['success'=>true,'stats'=>['today_count'=>intval($ts['c']),'today_total'=>floatval($ts['t']),'active_cards'=>intval($ac),'bank_stats'=>$bs,'recent_transactions'=>$rt,'network_stats'=>$ns,'hourly'=>$hs,'type_distribution'=>$td]]);
}

function getAuditLog(){$lim=min(intval($_GET['limit']??50),200);$st=db()->prepare("SELECT*FROM audit_log ORDER BY created_at DESC LIMIT ?");$st->execute([$lim]);jsonResponse(['success'=>true,'logs'=>$st->fetchAll()]);}

function getDecisionLog(){$lim=min(intval($_GET['limit']??50),200);$st=db()->prepare("SELECT*FROM decision_log ORDER BY created_at DESC LIMIT ?");$st->execute([$lim]);$ls=$st->fetchAll();foreach($ls as &$l)$l['criteria_evaluated']=json_decode($l['criteria_evaluated'],true);jsonResponse(['success'=>true,'decisions'=>$ls]);}
=======
// ═══════════════════════════════════════════════════════════════
// NexoATM — API Principal (PHP + MySQL + Teoría de Decisiones)
// ═══════════════════════════════════════════════════════════════
// Cada operación pasa por el Motor de Decisiones antes de ser
// ejecutada. El motor evalúa criterios ponderados y solo
// aprueba si el puntaje total cumple el umbral mínimo.
// ═══════════════════════════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/decisions.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_banks':          getBanks(); break;
    case 'get_cards_by_bank':  getCardsByBank(); break;
    case 'verify_pin':         verifyPin(); break;
    case 'get_card_info':      getCardInfo(); break;
    case 'withdrawal':         processWithdrawal(); break;
    case 'deposit':            processDeposit(); break;
    case 'transfer':           processTransfer(); break;
    case 'balance_inquiry':    balanceInquiry(); break;
    case 'change_pin':         changePin(); break;           // RF-7
    case 'get_transactions':   getTransactions(); break;     // RF-8
    case 'get_transfer_targets': getTransferTargets(); break;
    case 'get_stats':          getStats(); break;
    case 'get_audit_log':      getAuditLog(); break;
    case 'get_decision_log':   getDecisionLog(); break;
    case 'register_customer':  registerCustomer(); break;    // RF-15
    default:
        jsonResponse(['success' => false, 'error' => "Acción no válida: {$action}"], 400);
}

// ═══════════════════════════════════════════════
// FUNCIONES
// ═══════════════════════════════════════════════

function getBanks() {
    $stmt = db()->query("SELECT * FROM banks WHERE status='active' ORDER BY name");
    jsonResponse(['success' => true, 'banks' => $stmt->fetchAll()]);
}

function getCardsByBank() {
    $bankId = intval($_GET['bank_id'] ?? 0);
    if (!$bankId) jsonResponse(['success' => false, 'error' => 'bank_id requerido'], 400);
    $stmt = db()->prepare("
        SELECT c.id, c.card_number, c.holder_name, c.expiry_date, c.card_status,
               cn.code as network_code, cn.name as network_name,
               a.account_number, a.account_type, a.currency
        FROM cards c
        JOIN accounts a ON c.account_id = a.id
        JOIN card_networks cn ON c.network_id = cn.id
        WHERE a.bank_id = ? AND c.card_status != 'expired'
        ORDER BY c.holder_name
    ");
    $stmt->execute([$bankId]);
    jsonResponse(['success' => true, 'cards' => $stmt->fetchAll()]);
}

// ─── RF-1, RF-2: AUTENTICACIÓN PIN (con Teoría de Decisiones) ───
function verifyPin() {
    $input = json_decode(file_get_contents('php://input'), true);
    $cardId = intval($input['card_id'] ?? 0);
    $pin = $input['pin'] ?? '';

    if (!$cardId || strlen($pin) !== 4) {
        jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
    }

    // TEORÍA DE DECISIONES: evaluar autenticación
    $decision = DecisionEngine::evaluatePinAuth($cardId, $pin);

    if ($decision['decision'] === 'APPROVED') {
        // Obtener nombre
        $stmt = db()->prepare("SELECT holder_name FROM cards WHERE id = ?");
        $stmt->execute([$cardId]);
        $card = $stmt->fetch();

        jsonResponse([
            'success' => true,
            'message' => 'PIN correcto',
            'holder_name' => $card['holder_name'],
            'decision' => $decision // Enviar datos de decisión al frontend
        ]);
    } else {
        $blocked = strpos($decision['reason'] ?? '', 'bloqueada') !== false;
        jsonResponse([
            'success' => false,
            'error' => $decision['reason'],
            'blocked' => $blocked,
            'decision' => $decision
        ]);
    }
}

function getCardInfo() {
    $cardId = intval($_GET['card_id'] ?? 0);
    if (!$cardId) jsonResponse(['success' => false, 'error' => 'card_id requerido'], 400);
    $stmt = db()->prepare("
        SELECT c.id as card_id, c.card_number, c.holder_name, c.expiry_date, c.card_status,
               cn.code as network_code, cn.name as network_name,
               a.id as account_id, a.account_number, a.balance, a.daily_limit,
               a.withdrawn_today, a.account_type, a.currency,
               b.id as bank_id, b.code as bank_code, b.name as bank_name, b.short_name,
               b.primary_color, b.secondary_color, b.accent_color, b.logo_text,
               cu.first_name, cu.last_name, cu.preferred_lang
        FROM cards c
        JOIN accounts a ON c.account_id = a.id
        JOIN card_networks cn ON c.network_id = cn.id
        JOIN banks b ON a.bank_id = b.id
        JOIN customers cu ON a.customer_id = cu.id
        WHERE c.id = ?
    ");
    $stmt->execute([$cardId]);
    $info = $stmt->fetch();
    if (!$info) jsonResponse(['success' => false, 'error' => 'Tarjeta no encontrada'], 404);
    jsonResponse(['success' => true, 'card' => $info]);
}

// ─── RF-4: RETIRO (con Teoría de Decisiones) ───
function processWithdrawal() {
    $input = json_decode(file_get_contents('php://input'), true);
    $cardId = intval($input['card_id'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);

    $pdo = db();
    $pdo->beginTransaction();

    try {
        // TEORÍA DE DECISIONES: evaluar retiro
        $decision = DecisionEngine::evaluateWithdrawal($cardId, $amount);

        if ($decision['decision'] !== 'APPROVED') {
            $pdo->rollBack();
            auditLog('warning', "RETIRO DENEGADO: {$decision['reason']} | Card ID:{$cardId}", $cardId);
            jsonResponse(['success' => false, 'error' => $decision['reason'], 'decision' => $decision]);
        }

        $data = $decision['data'];
        $balanceBefore = $data['balance'];
        $balanceAfter = $balanceBefore - $amount;
        $txCode = generateTxCode();

        $stmt = $pdo->prepare("UPDATE accounts SET balance=?, withdrawn_today=withdrawn_today+? WHERE id=?");
        $stmt->execute([$balanceAfter, $amount, $data['account_id']]);

        $stmt = $pdo->prepare("INSERT INTO transactions (tx_code, card_id, account_id, bank_id, tx_type, amount, balance_before, balance_after, description, status) VALUES (?,?,?,?,'withdrawal',?,?,?,'Retiro en cajero','completed')");
        $stmt->execute([$txCode, $cardId, $data['account_id'], $data['bank_id'], $amount, $balanceBefore, $balanceAfter]);

        $pdo->commit();
        auditLog('success', "RETIRO APROBADO: {$data['currency']} {$amount} | Score: {$decision['score']}", $cardId);

        jsonResponse([
            'success' => true,
            'transaction' => [
                'tx_code' => $txCode, 'type' => 'withdrawal', 'amount' => $amount,
                'currency' => $data['currency'], 'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'card_last4' => substr(str_replace(' ','',$data['card_number']), -4),
                'holder_name' => $data['holder_name'], 'timestamp' => date('Y-m-d H:i:s')
            ],
            'decision' => $decision
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        auditLog('error', "ERROR RETIRO: " . $e->getMessage(), $cardId);
        jsonResponse(['success' => false, 'error' => 'Error al procesar'], 500);
    }
}

// ─── RF-5: DEPÓSITO (con Teoría de Decisiones) ───
function processDeposit() {
    $input = json_decode(file_get_contents('php://input'), true);
    $cardId = intval($input['card_id'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $decision = DecisionEngine::evaluateDeposit($cardId, $amount);

        if ($decision['decision'] !== 'APPROVED') {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'error' => $decision['reason'], 'decision' => $decision]);
        }

        $data = $decision['data'];
        $balanceBefore = $data['balance'];
        $balanceAfter = $balanceBefore + $amount;
        $txCode = generateTxCode();

        $stmt = $pdo->prepare("UPDATE accounts SET balance=? WHERE id=?");
        $stmt->execute([$balanceAfter, $data['account_id']]);

        $stmt = $pdo->prepare("INSERT INTO transactions (tx_code, card_id, account_id, bank_id, tx_type, amount, balance_before, balance_after, description, status) VALUES (?,?,?,?,'deposit',?,?,?,'Depósito en cajero','completed')");
        $stmt->execute([$txCode, $cardId, $data['account_id'], $data['bank_id'], $amount, $balanceBefore, $balanceAfter]);

        $pdo->commit();
        auditLog('success', "DEPÓSITO APROBADO: {$data['currency']} {$amount}", $cardId);

        jsonResponse([
            'success' => true,
            'transaction' => [
                'tx_code' => $txCode, 'type' => 'deposit', 'amount' => $amount,
                'currency' => $data['currency'], 'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'card_last4' => substr(str_replace(' ','',$data['card_number']), -4),
                'holder_name' => $data['holder_name'], 'timestamp' => date('Y-m-d H:i:s')
            ],
            'decision' => $decision
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Error al procesar'], 500);
    }
}

// ─── RF-6: TRANSFERENCIA (con Teoría de Decisiones) ───
function processTransfer() {
    $input = json_decode(file_get_contents('php://input'), true);
    $cardId = intval($input['card_id'] ?? 0);
    $targetAccountId = intval($input['target_account_id'] ?? 0);
    $amount = floatval($input['amount'] ?? 0);

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $decision = DecisionEngine::evaluateTransfer($cardId, $targetAccountId, $amount);

        if ($decision['decision'] !== 'APPROVED') {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'error' => $decision['reason'], 'decision' => $decision]);
        }

        $source = $decision['source'];
        $target = $decision['target'];
        $srcBefore = $source['balance'];
        $srcAfter = $srcBefore - $amount;
        $tgtAfter = $target['balance'] + $amount;
        $txCode = generateTxCode();

        $stmt = $pdo->prepare("UPDATE accounts SET balance=? WHERE id=?");
        $stmt->execute([$srcAfter, $source['account_id']]);
        $stmt->execute([$tgtAfter, $targetAccountId]);

        $stmt = $pdo->prepare("INSERT INTO transactions (tx_code, card_id, account_id, bank_id, tx_type, amount, balance_before, balance_after, target_account_id, description, status) VALUES (?,?,?,?,'transfer',?,?,?,?,?,'completed')");
        $stmt->execute([$txCode, $cardId, $source['account_id'], $source['bank_id'], $amount, $srcBefore, $srcAfter, $targetAccountId, "Transferencia a cta " . $target['account_number']]);

        $pdo->commit();
        auditLog('success', "TRANSFERENCIA APROBADA: {$source['currency']} {$amount}", $cardId);

        jsonResponse([
            'success' => true,
            'transaction' => [
                'tx_code' => $txCode, 'type' => 'transfer', 'amount' => $amount,
                'currency' => $source['currency'], 'balance_before' => $srcBefore,
                'balance_after' => $srcAfter, 'target_account' => $target['account_number'],
                'card_last4' => substr(str_replace(' ','',$source['card_number']), -4),
                'holder_name' => $source['holder_name'], 'timestamp' => date('Y-m-d H:i:s')
            ],
            'decision' => $decision
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Error al procesar'], 500);
    }
}

// ─── RF-3: CONSULTA DE SALDO ───
function balanceInquiry() {
    $cardId = intval($_GET['card_id'] ?? 0);
    if (!$cardId) jsonResponse(['success' => false, 'error' => 'card_id requerido'], 400);

    $stmt = db()->prepare("
        SELECT c.id, a.id as account_id, a.balance, a.daily_limit, a.withdrawn_today,
               a.account_type, a.currency, a.bank_id, c.card_number, c.holder_name
        FROM cards c JOIN accounts a ON c.account_id = a.id WHERE c.id = ?
    ");
    $stmt->execute([$cardId]);
    $data = $stmt->fetch();
    if (!$data) jsonResponse(['success' => false, 'error' => 'No encontrada'], 404);

    $txCode = generateTxCode();
    $stmt = db()->prepare("INSERT INTO transactions (tx_code, card_id, account_id, bank_id, tx_type, balance_before, balance_after, description, status) VALUES (?,?,?,?,'balance_inquiry',?,?,'Consulta de saldo','completed')");
    $stmt->execute([$txCode, $cardId, $data['account_id'], $data['bank_id'], $data['balance'], $data['balance']]);

    auditLog('info', "Consulta saldo: ****" . substr($data['card_number'],-4), $cardId);

    jsonResponse([
        'success' => true,
        'balance' => [
            'amount' => $data['balance'], 'currency' => $data['currency'],
            'daily_limit' => $data['daily_limit'],
            'available_today' => $data['daily_limit'] - $data['withdrawn_today'],
            'account_type' => $data['account_type'],
            'holder_name' => $data['holder_name'],
            'card_last4' => substr(str_replace(' ','',$data['card_number']), -4)
        ],
        'tx_code' => $txCode
    ]);
}

// ─── RF-7: CAMBIO DE PIN ───
function changePin() {
    $input = json_decode(file_get_contents('php://input'), true);
    $cardId = intval($input['card_id'] ?? 0);
    $currentPin = $input['current_pin'] ?? '';
    $newPin = $input['new_pin'] ?? '';

    if (!$cardId || strlen($currentPin) !== 4 || strlen($newPin) !== 4) {
        jsonResponse(['success' => false, 'error' => 'Datos inválidos'], 400);
    }

    if ($currentPin === $newPin) {
        jsonResponse(['success' => false, 'error' => 'El nuevo PIN debe ser diferente al actual']);
    }

    $stmt = db()->prepare("SELECT pin_hash, holder_name FROM cards WHERE id = ? AND card_status = 'active'");
    $stmt->execute([$cardId]);
    $card = $stmt->fetch();

    if (!$card || $currentPin !== $card['pin_hash']) {
        jsonResponse(['success' => false, 'error' => 'PIN actual incorrecto']);
    }

    $stmt = db()->prepare("UPDATE cards SET pin_hash = ? WHERE id = ?");
    $stmt->execute([$newPin, $cardId]);

    $txCode = generateTxCode();
    $stmt = db()->prepare("SELECT a.id, a.balance, a.bank_id FROM accounts a JOIN cards c ON c.account_id=a.id WHERE c.id=?");
    $stmt->execute([$cardId]);
    $acc = $stmt->fetch();

    $stmt = db()->prepare("INSERT INTO transactions (tx_code, card_id, account_id, bank_id, tx_type, balance_before, balance_after, description, status) VALUES (?,?,?,?,'pin_change',?,?,'Cambio de PIN','completed')");
    $stmt->execute([$txCode, $cardId, $acc['id'], $acc['bank_id'], $acc['balance'], $acc['balance']]);

    auditLog('success', "CAMBIO DE PIN exitoso para tarjeta ID:{$cardId}", $cardId);

    jsonResponse(['success' => true, 'message' => 'PIN cambiado exitosamente', 'tx_code' => $txCode]);
}

// ─── RF-8: HISTORIAL DE TRANSACCIONES ───
function getTransactions() {
    $cardId = $_GET['card_id'] ?? null;
    $limit = min(intval($_GET['limit'] ?? 20), 100);

    if ($cardId) {
        $stmt = db()->prepare("SELECT t.*, b.short_name as bank_name FROM transactions t JOIN banks b ON t.bank_id=b.id WHERE t.card_id=? ORDER BY t.created_at DESC LIMIT ?");
        $stmt->execute([intval($cardId), $limit]);
    } else {
        $stmt = db()->prepare("SELECT t.*, b.short_name as bank_name, c.card_number, c.holder_name FROM transactions t JOIN banks b ON t.bank_id=b.id JOIN cards c ON t.card_id=c.id ORDER BY t.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
    }
    jsonResponse(['success' => true, 'transactions' => $stmt->fetchAll()]);
}

function getTransferTargets() {
    $cardId = intval($_GET['card_id'] ?? 0);
    $stmt = db()->prepare("SELECT account_id FROM cards WHERE id = ?");
    $stmt->execute([$cardId]);
    $current = $stmt->fetch();
    $currentAccountId = $current['account_id'] ?? 0;

    $stmt = db()->prepare("SELECT a.id, a.account_number, a.account_type, a.currency, b.short_name as bank_name, cu.first_name, cu.last_name FROM accounts a JOIN banks b ON a.bank_id=b.id JOIN customers cu ON a.customer_id=cu.id WHERE a.id != ? AND a.status='active' ORDER BY b.name");
    $stmt->execute([$currentAccountId]);
    jsonResponse(['success' => true, 'accounts' => $stmt->fetchAll()]);
}

// ─── RF-15: REGISTRO DE USUARIOS ───
function registerCustomer() {
    $input = json_decode(file_get_contents('php://input'), true);
    $ci = $input['ci'] ?? '';
    $firstName = $input['first_name'] ?? '';
    $lastName = $input['last_name'] ?? '';
    $bankId = intval($input['bank_id'] ?? 0);
    $pin = $input['pin'] ?? '';

    if (!$ci || !$firstName || !$lastName || !$bankId || strlen($pin) !== 4) {
        jsonResponse(['success' => false, 'error' => 'Todos los campos son requeridos'], 400);
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        // Verificar CI no duplicado
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE ci = ?");
        $stmt->execute([$ci]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'error' => 'Ya existe un cliente con ese CI']);
        }

        // Crear cliente
        $stmt = $pdo->prepare("INSERT INTO customers (ci, first_name, last_name) VALUES (?, ?, ?)");
        $stmt->execute([$ci, $firstName, $lastName]);
        $customerId = $pdo->lastInsertId();

        // Crear cuenta
        $accNumber = $bankId . '001-' . str_pad($customerId, 4, '0', STR_PAD_LEFT) . '-' . date('Y');
        $stmt = $pdo->prepare("INSERT INTO accounts (account_number, customer_id, bank_id, balance, daily_limit) VALUES (?, ?, ?, 1000.00, 5000.00)");
        $stmt->execute([$accNumber, $customerId, $bankId]);
        $accountId = $pdo->lastInsertId();

        // Crear tarjeta
        $cardNum = '4' . str_pad(rand(100,999),3,'0') . ' ' . str_pad(rand(1000,9999),4,'0') . ' ' . str_pad(rand(1000,9999),4,'0') . ' ' . str_pad(rand(1000,9999),4,'0');
        $holderName = strtoupper(substr($firstName,0,1) . '. ' . $lastName);
        $expiry = str_pad(rand(1,12),2,'0',STR_PAD_LEFT) . '/' . (date('y') + 3);

        $stmt = $pdo->prepare("INSERT INTO cards (card_number, account_id, network_id, holder_name, expiry_date, pin_hash) VALUES (?, ?, 1, ?, ?, ?)");
        $stmt->execute([$cardNum, $accountId, $holderName, $expiry, $pin]);

        $pdo->commit();
        auditLog('success', "Nuevo cliente registrado: {$firstName} {$lastName} (CI: {$ci})");

        jsonResponse([
            'success' => true,
            'message' => 'Cuenta creada exitosamente',
            'account_number' => $accNumber,
            'card_number' => $cardNum,
            'initial_balance' => 1000.00
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Error al registrar'], 500);
    }
}

// ─── DASHBOARD STATS ───
function getStats() {
    $today = date('Y-m-d');
    $stmt = db()->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount),0) as total FROM transactions WHERE DATE(created_at)=?");
    $stmt->execute([$today]);
    $todayStats = $stmt->fetch();

    $stmt = db()->query("SELECT COUNT(*) as count FROM cards WHERE card_status='active'");
    $activeCards = $stmt->fetch()['count'];

    $stmt = db()->query("SELECT b.short_name, b.primary_color, COUNT(t.id) as tx_count, COALESCE(SUM(t.amount),0) as tx_total FROM banks b LEFT JOIN transactions t ON b.id=t.bank_id AND DATE(t.created_at)=CURDATE() GROUP BY b.id ORDER BY tx_count DESC");
    $bankStats = $stmt->fetchAll();

    $stmt = db()->query("SELECT t.tx_code, t.tx_type, t.amount, t.created_at, t.status, c.card_number, c.holder_name, b.short_name as bank_name, b.primary_color FROM transactions t JOIN cards c ON t.card_id=c.id JOIN banks b ON t.bank_id=b.id ORDER BY t.created_at DESC LIMIT 10");
    $recentTx = $stmt->fetchAll();

    $stmt = db()->query("SELECT cn.name, cn.code, COUNT(c.id) as card_count FROM card_networks cn LEFT JOIN cards c ON cn.id=c.network_id GROUP BY cn.id");
    $networkStats = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'stats' => [
            'today_count' => intval($todayStats['count']),
            'today_total' => floatval($todayStats['total']),
            'active_cards' => intval($activeCards),
            'bank_stats' => $bankStats,
            'recent_transactions' => $recentTx,
            'network_stats' => $networkStats
        ]
    ]);
}

function getAuditLog() {
    $limit = min(intval($_GET['limit'] ?? 50), 200);
    $stmt = db()->prepare("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    jsonResponse(['success' => true, 'logs' => $stmt->fetchAll()]);
}

// ─── LOG DE DECISIONES (Teoría de Decisiones) ───
function getDecisionLog() {
    $limit = min(intval($_GET['limit'] ?? 50), 200);
    $stmt = db()->prepare("SELECT * FROM decision_log ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    $logs = $stmt->fetchAll();
    foreach ($logs as &$log) {
        $log['criteria_evaluated'] = json_decode($log['criteria_evaluated'], true);
    }
    jsonResponse(['success' => true, 'decisions' => $logs]);
}
>>>>>>> b7c1a06386a779af52f4d1b04e9e4731c2491363
