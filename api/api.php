<?php
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
