<?php
require_once __DIR__ . '/../config/database.php';

class DecisionEngine {
    public static function evaluatePinAuth($cardId, $pin) {
        $start = microtime(true);
        $criteria = [];
        $stmt = db()->prepare("SELECT * FROM cards WHERE id = ?");
        $stmt->execute([$cardId]);
        $card = $stmt->fetch();

        $exists = ($card !== false);
        $criteria[] = ['name'=>'card_exists','value'=>$exists?'found':'not_found','weight'=>30,'passed'=>$exists];
        if (!$exists) return self::log('pin_auth',$cardId,$criteria,0,100,'DENIED','Tarjeta no encontrada',$start);

        $notBlocked = ($card['card_status'] !== 'blocked' || ($card['blocked_until'] && strtotime($card['blocked_until']) <= time()));
        $criteria[] = ['name'=>'card_not_blocked','value'=>$card['card_status'],'weight'=>25,'passed'=>$notBlocked];

        if ($card['card_status'] === 'blocked' && $card['blocked_until'] && strtotime($card['blocked_until']) <= time()) {
            db()->prepare("UPDATE cards SET card_status='active', pin_attempts=0, blocked_until=NULL WHERE id=?")->execute([$cardId]);
            $card['pin_attempts'] = 0;
        }

        if (!$notBlocked) {
            $rem = ceil((strtotime($card['blocked_until']) - time()) / 60);
            return self::log('pin_auth',$cardId,$criteria,0,100,'DENIED',"Tarjeta bloqueada. Espere {$rem} min.",$start);
        }

        $hasAttempts = ($card['pin_attempts'] < $card['max_pin_attempts']);
        $criteria[] = ['name'=>'attempts_available','value'=>$card['pin_attempts'].'/'.$card['max_pin_attempts'],'weight'=>20,'passed'=>$hasAttempts];

        $pinOk = ($pin === $card['pin_hash']);
        $criteria[] = ['name'=>'pin_correct','value'=>$pinOk?'match':'mismatch','weight'=>25,'passed'=>$pinOk];

        $score = self::calcScore($criteria);
        $result = ($score >= 100) ? 'APPROVED' : 'DENIED';
        $reason = null;

        if (!$pinOk) {
            $att = $card['pin_attempts'] + 1;
            $max = $card['max_pin_attempts'];
            if ($att >= $max) {
                $bu = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                db()->prepare("UPDATE cards SET pin_attempts=?, card_status='blocked', blocked_until=? WHERE id=?")->execute([$att,$bu,$cardId]);
                $reason = "Tarjeta bloqueada por seguridad.";
                auditLog('error',"BLOQUEADA tarjeta ID:{$cardId}",$cardId);
            } else {
                db()->prepare("UPDATE cards SET pin_attempts=? WHERE id=?")->execute([$att,$cardId]);
                $reason = "PIN incorrecto. Quedan ".($max-$att)." intentos.";
                auditLog('warning',"PIN incorrecto ID:{$cardId} ({$att}/{$max})",$cardId);
            }
        } else {
            db()->prepare("UPDATE cards SET pin_attempts=0 WHERE id=?")->execute([$cardId]);
            auditLog('success',"PIN OK tarjeta ID:{$cardId}",$cardId);
        }
        return self::log('pin_auth',$cardId,$criteria,$score,100,$result,$reason,$start);
    }

    public static function evaluateWithdrawal($cardId, $amount, $currency = 'BOB') {
        $start = microtime(true);
        $criteria = [];
        $balCol = $currency === 'USD' ? 'balance_usd' : 'balance_bob';

        $stmt = db()->prepare("SELECT c.*, a.id as account_id, a.{$balCol} as balance, a.daily_limit, a.withdrawn_today, a.bank_id, a.status as account_status FROM cards c JOIN accounts a ON c.account_id=a.id WHERE c.id=? FOR UPDATE");
        $stmt->execute([$cardId]);
        $data = $stmt->fetch();

        $cardActive = ($data && $data['card_status'] === 'active');
        $criteria[] = ['name'=>'card_active','value'=>$data?$data['card_status']:'not_found','weight'=>25,'passed'=>$cardActive];
        if (!$data) return self::log('withdrawal_auth',$cardId,$criteria,0,100,'DENIED','Tarjeta no disponible',$start);

        $accActive = ($data['account_status'] === 'active');
        $criteria[] = ['name'=>'account_active','value'=>$data['account_status'],'weight'=>20,'passed'=>$accActive];

        $validAmt = ($amount > 0);
        $criteria[] = ['name'=>'valid_amount','value'=>$amount,'weight'=>10,'passed'=>$validAmt];

        $hasFunds = ($amount <= $data['balance']);
        $criteria[] = ['name'=>'sufficient_funds','value'=>"bal:{$data['balance']} req:{$amount}",'weight'=>25,'passed'=>$hasFunds];

        $avail = $data['daily_limit'] - $data['withdrawn_today'];
        $withinLimit = ($amount <= $avail);
        $criteria[] = ['name'=>'within_daily_limit','value'=>"avail:{$avail} req:{$amount}",'weight'=>20,'passed'=>$withinLimit];

        $score = self::calcScore($criteria);
        $result = ($score >= 100) ? 'APPROVED' : 'DENIED';
        $reason = null;
        if (!$cardActive) $reason = 'Tarjeta no activa';
        elseif (!$accActive) $reason = 'Cuenta no activa';
        elseif (!$validAmt) $reason = 'Monto inválido';
        elseif (!$hasFunds) $reason = 'Fondos insuficientes';
        elseif (!$withinLimit) $reason = "Excede límite diario. Disponible: " . number_format($avail, 2);

        $d = self::log('withdrawal_auth',$cardId,$criteria,$score,100,$result,$reason,$start);
        $d['data'] = $data;
        return $d;
    }

    public static function evaluateDeposit($cardId, $amount) {
        $start = microtime(true);
        $criteria = [];
        $stmt = db()->prepare("SELECT c.*, a.id as account_id, a.balance_bob, a.balance_usd, a.bank_id, a.status as account_status FROM cards c JOIN accounts a ON c.account_id=a.id WHERE c.id=? FOR UPDATE");
        $stmt->execute([$cardId]);
        $data = $stmt->fetch();

        $cardActive = ($data && $data['card_status'] === 'active');
        $criteria[] = ['name'=>'card_active','value'=>$data?$data['card_status']:'not_found','weight'=>30,'passed'=>$cardActive];
        if (!$data) return self::log('deposit_auth',$cardId,$criteria,0,100,'DENIED','Tarjeta no disponible',$start);

        $accActive = ($data['account_status'] === 'active');
        $criteria[] = ['name'=>'account_active','value'=>$data['account_status'],'weight'=>30,'passed'=>$accActive];

        $validAmt = ($amount > 0 && $amount <= 50000);
        $criteria[] = ['name'=>'valid_amount','value'=>$amount,'weight'=>40,'passed'=>$validAmt];

        $score = self::calcScore($criteria);
        $result = ($score >= 100) ? 'APPROVED' : 'DENIED';
        $reason = !$cardActive ? 'Tarjeta no activa' : (!$accActive ? 'Cuenta no activa' : (!$validAmt ? 'Monto inválido' : null));

        $d = self::log('deposit_auth',$cardId,$criteria,$score,100,$result,$reason,$start);
        $d['data'] = $data;
        return $d;
    }

    public static function evaluateTransfer($cardId, $targetAccountId, $amount, $currency = 'BOB') {
        $start = microtime(true);
        $criteria = [];
        $balCol = $currency === 'USD' ? 'balance_usd' : 'balance_bob';

        $stmt = db()->prepare("SELECT c.*, a.id as account_id, a.{$balCol} as balance, a.bank_id, a.status as account_status FROM cards c JOIN accounts a ON c.account_id=a.id WHERE c.id=? FOR UPDATE");
        $stmt->execute([$cardId]);
        $source = $stmt->fetch();

        $cardActive = ($source && $source['card_status'] === 'active');
        $criteria[] = ['name'=>'card_active','value'=>$source?$source['card_status']:'not_found','weight'=>20,'passed'=>$cardActive];
        if (!$source) return self::log('transfer_auth',$cardId,$criteria,0,100,'DENIED','Tarjeta no disponible',$start);

        $stmt = db()->prepare("SELECT * FROM accounts WHERE id=? FOR UPDATE");
        $stmt->execute([$targetAccountId]);
        $target = $stmt->fetch();

        $tgtExists = ($target !== false);
        $criteria[] = ['name'=>'target_exists','value'=>$tgtExists?'found':'not_found','weight'=>20,'passed'=>$tgtExists];

        $notSame = ($source['account_id'] != $targetAccountId);
        $criteria[] = ['name'=>'different_accounts','value'=>$notSame?'diff':'same','weight'=>15,'passed'=>$notSame];

        $validAmt = ($amount > 0);
        $criteria[] = ['name'=>'valid_amount','value'=>$amount,'weight'=>15,'passed'=>$validAmt];

        $hasFunds = ($amount <= $source['balance']);
        $criteria[] = ['name'=>'sufficient_funds','value'=>"bal:{$source['balance']} req:{$amount}",'weight'=>30,'passed'=>$hasFunds];

        $score = self::calcScore($criteria);
        $result = ($score >= 100) ? 'APPROVED' : 'DENIED';
        $reason = null;
        if (!$cardActive) $reason = 'Tarjeta no activa';
        elseif (!$tgtExists) $reason = 'Cuenta destino no encontrada';
        elseif (!$notSame) $reason = 'No puede transferir a la misma cuenta';
        elseif (!$validAmt) $reason = 'Monto inválido';
        elseif (!$hasFunds) $reason = 'Fondos insuficientes';

        $d = self::log('transfer_auth',$cardId,$criteria,$score,100,$result,$reason,$start);
        $d['source'] = $source;
        $d['target'] = $target;
        return $d;
    }

    private static function calcScore($criteria) {
        $total = array_sum(array_column($criteria,'weight'));
        $earned = 0;
        foreach ($criteria as $c) if ($c['passed']) $earned += $c['weight'];
        return $total > 0 ? round(($earned/$total)*100,2) : 0;
    }

    private static function log($type,$cardId,$criteria,$score,$threshold,$result,$reason,$start) {
        $ms = round((microtime(true)-$start)*1000);
        try {
            db()->prepare("INSERT INTO decision_log (decision_type,card_id,criteria_evaluated,total_score,threshold,decision_result,denial_reason,processing_time_ms) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$type,$cardId,json_encode($criteria),$score,$threshold,$result,$reason,$ms]);
        } catch (Exception $e) {}
        return ['decision'=>$result,'score'=>$score,'threshold'=>$threshold,'criteria'=>$criteria,'reason'=>$reason,'processing_time_ms'=>$ms];
    }
}
