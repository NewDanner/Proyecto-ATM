<?php
define('DB_HOST','localhost');define('DB_NAME','nexoatm_db');define('DB_USER','root');define('DB_PASS','');define('DB_CHARSET','utf8mb4');
class Database{private static $i=null;private $pdo;
private function __construct(){$this->pdo=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}
public static function getInstance(){if(!self::$i)self::$i=new self();return self::$i;}
public function getConnection(){return $this->pdo;}}
function db(){return Database::getInstance()->getConnection();}
function jsonResponse($d,$c=200){http_response_code($c);header('Content-Type:application/json;charset=utf-8');echo json_encode($d,JSON_UNESCAPED_UNICODE);exit;}
function auditLog($l,$m,$cid=null){try{db()->prepare("INSERT INTO audit_log(level,message,ip_address,card_id)VALUES(?,?,?,?)")->execute([$l,$m,$_SERVER['REMOTE_ADDR']??'127.0.0.1',$cid]);}catch(Exception $e){}}
function generateTxCode(){return 'TX'.strtoupper(base_convert(time(),10,36)).strtoupper(substr(bin2hex(random_bytes(3)),0,4));}
