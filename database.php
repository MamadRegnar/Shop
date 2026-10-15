<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require __DIR__ . '/config.php';
function out(array $a, int $code=200): never { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function body(): array { $raw=file_get_contents('php://input'); $j=json_decode($raw ?: '{}',true); return is_array($j)?$j:$_POST; }
function token(): string { $h=getallheaders(); $auth=$h['Authorization']??$h['authorization']??''; if(preg_match('/Bearer\\s+(.+)/i',$auth,$m))return trim($m[1]); $d=body(); return (string)($d['token']??$_POST['token']??''); }
try {
 $pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
} catch(Throwable $e){out(['ok'=>false,'error'=>'Database connection failed. Check backend/config.php'],500);}
$a=(string)($_GET['action']??'status'); $d=body();
function user(PDO $p): array { $t=token(); if(strlen($t)<20)out(['ok'=>false,'error'=>'Please login again'],401); $q=$p->prepare('SELECT u.id,u.username,u.display_name FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token_hash=? AND s.expires_at>NOW()');$q->execute([hash('sha256',$t)]);$u=$q->fetch();if(!$u)out(['ok'=>false,'error'=>'Invalid or expired session'],401);return $u; }
function newSession(PDO $p,int $id): string {$t=bin2hex(random_bytes(32));$q=$p->prepare('INSERT INTO sessions(token_hash,user_id,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 30 DAY))');$q->execute([hash('sha256',$t),$id]);return $t;}
try {
 switch($a){
 case 'status': out(['ok'=>true,'service'=>'REGNAR CHAT API','status'=>'online']);
 case 'register':
  $u=strtolower(trim((string)($d['username']??'')));$n=trim((string)($d['display_name']??$d['name']??$u));$pw=(string)($d['password']??'');
  if(!preg_match('/^[a-z0-9_]{3,32}$/',$u))out(['ok'=>false,'error'=>'Username must be 3-32 chars: a-z, 0-9, underscore'],422);
  if(strlen($pw)<8)out(['ok'=>false,'error'=>'Password must be at least 8 characters'],422);
  if($n==='')$n=$u;
  $q=$pdo->prepare('INSERT INTO users(username,display_name,password_hash) VALUES(?,?,?)');$q->execute([$u,mb_substr($n,0,80),password_hash($pw,PASSWORD_DEFAULT)]);$id=(int)$pdo->lastInsertId();$t=newSession($pdo,$id);out(['ok'=>true,'token'=>$t,'username'=>$u,'display_name'=>$n]);
 case 'login':
  $q=$pdo->prepare('SELECT * FROM users WHERE username=?');$q->execute([strtolower(trim((string)($d['username']??'')))]);$x=$q->fetch();
  if(!$x||!password_verify((string)($d['password']??''),$x['password_hash']))out(['ok'=>false,'error'=>'نام کاربری یا رمز اشتباه است'],401);
  out(['ok'=>true,'token'=>newSession($pdo,(int)$x['id']),'username'=>$x['username'],'display_name'=>$x['display_name']]);
 case 'logout':
  $t=token();$q=$pdo->prepare('DELETE FROM sessions WHERE token_hash=?');$q->execute([hash('sha256',$t)]);out(['ok'=>true]);
 case 'profile':
  $u=user($pdo);out(['ok'=>true,'user'=>$u]);
 case 'editname':
  $u=user($pdo);$n=trim((string)($d['display_name']??$d['name']??''));if($n==='')out(['ok'=>false,'error'=>'Name required'],422);$n=mb_substr($n,0,80);$q=$pdo->prepare('UPDATE users SET display_name=? WHERE id=?');$q->execute([$n,$u['id']]);out(['ok'=>true,'display_name'=>$n]);
 case 'private_chat':
  $u=user($pdo);$target=strtolower(trim((string)($d['username']??'')));$q=$pdo->prepare('SELECT id,username FROM users WHERE username=?');$q->execute([$target]);$v=$q->fetch();if(!$v)out(['ok'=>false,'error'=>'این کاربر پیدا نشد'],404);if((int)$v['id']===(int)$u['id'])out(['ok'=>false,'error'=>'نمی‌توانی با خودت چت خصوصی بسازی'],422);
  $pdo->beginTransaction();
  $q=$pdo->prepare("SELECT r.id FROM rooms r JOIN room_members a ON a.room_id=r.id JOIN room_members b ON b.room_id=r.id WHERE r.type='private' AND a.user_id=? AND b.user_id=? LIMIT 1");$q->execute([$u['id'],$v['id']]);$rid=$q->fetchColumn();
  if(!$rid){$q=$pdo->prepare("INSERT INTO rooms(title,type,owner_id) VALUES(?,'private',?)");$q->execute([$v['username'], $u['id']]);$rid=(int)$pdo->lastInsertId();$q=$pdo->prepare("INSERT INTO room_members(room_id,user_id,role) VALUES(?,?,'owner'),(?,?,'member')");$q->execute([$rid,$u['id'],$rid,$v['id']]);}
  $pdo->commit();out(['ok'=>true,'room_id'=>(int)$rid,'title'=>$v['username']]);
 case 'create_room':
  $u=user($pdo);$n=trim((string)($d['title']??$d['name']??''));$type=(string)($d['type']??'group');$admin=(int)($d['admin_only']??0);if($n==='')out(['ok'=>false,'error'=>'Room title required'],422);if(!in_array($type,['group','channel'],true))$type='group';
  $q=$pdo->prepare('INSERT INTO rooms(title,type,owner_id,admin_only) VALUES(?,?,?,?)');$q->execute([mb_substr($n,0,100),$type,$u['id'],$admin?1:0]);$id=(int)$pdo->lastInsertId();$q=$pdo->prepare("INSERT INTO room_members(room_id,user_id,role) VALUES(?,?,'owner')");$q->execute([$id,$u['id']]);out(['ok'=>true,'room_id'=>$id]);
 case 'join_room':
  $u=user($pdo);$id=(int)($d['room_id']??0);$q=$pdo->prepare('SELECT id FROM rooms WHERE id=?');$q->execute([$id]);if(!$q->fetch())out(['ok'=>false,'error'=>'Room not found'],404);$q=$pdo->prepare("INSERT IGNORE INTO room_members(room_id,user_id,role) VALUES(?,?,'member')");$q->execute([$id,$u['id']]);out(['ok'=>true]);
 case 'list_rooms':
  $u=user($pdo);$q=$pdo->prepare('SELECT r.id,r.title,r.type,r.admin_only,MAX(m.created_at) last_message FROM rooms r JOIN room_members rm ON rm.room_id=r.id LEFT JOIN messages m ON m.room_id=r.id WHERE rm.user_id=? GROUP BY r.id ORDER BY COALESCE(MAX(m.created_at),r.created_at) DESC');$q->execute([$u['id']]);out(['ok'=>true,'rooms'=>$q->fetchAll()]);

 case 'set_posting_mode':
  $u=user($pdo);$rid=(int)($d['room_id']??0);$mode=(int)($d['admin_only']??0);
  $q=$pdo->prepare("SELECT owner_id FROM rooms WHERE id=?");$q->execute([$rid]);$owner=$q->fetchColumn();
  if(!$owner)out(['ok'=>false,'error'=>'Room not found'],404);
  $q=$pdo->prepare("SELECT role FROM room_members WHERE room_id=? AND user_id=?");$q->execute([$rid,$u['id']]);$role=$q->fetchColumn();
  if(!in_array($role,['owner','admin'],true))out(['ok'=>false,'error'=>'Only room admins can change posting mode'],403);
  $q=$pdo->prepare("UPDATE rooms SET admin_only=? WHERE id=?");$q->execute([$mode?1:0,$rid]);out(['ok'=>true,'admin_only'=>$mode?1:0]);
 case 'deletemessage':
  $u=user($pdo);$mid=(int)($d['message_id']??$d['id']??0);
  $q=$pdo->prepare("SELECT m.sender_id,m.room_id,rm.role FROM messages m JOIN room_members rm ON rm.room_id=m.room_id AND rm.user_id=? WHERE m.id=?");$q->execute([$u['id'],$mid]);$m=$q->fetch();
  if(!$m)out(['ok'=>false,'error'=>'Message not found or access denied'],404);
  if((int)$m['sender_id']!==(int)$u['id']&&!in_array($m['role'],['owner','admin'],true))out(['ok'=>false,'error'=>'Cannot delete this message'],403);
  $q=$pdo->prepare("DELETE FROM messages WHERE id=?");$q->execute([$mid]);out(['ok'=>true]);

 case 'sendmessage':
  $u=user($pdo);$rid=(int)($d['room_id']??0);$msg=trim((string)($d['message']??$d['text']??''));if($msg==='')out(['ok'=>false,'error'=>'Message is empty'],422);if(mb_strlen($msg)>4000)out(['ok'=>false,'error'=>'Message too long'],422);
  $q=$pdo->prepare('SELECT r.admin_only,rm.role FROM rooms r JOIN room_members rm ON rm.room_id=r.id WHERE r.id=? AND rm.user_id=?');$q->execute([$rid,$u['id']]);$m=$q->fetch();if(!$m)out(['ok'=>false,'error'=>'You are not a member of this room'],403);if((int)$m['admin_only']===1&&!in_array($m['role'],['owner','admin'],true))out(['ok'=>false,'error'=>'Only admins can post'],403);
  $q=$pdo->prepare('INSERT INTO messages(room_id,sender_id,body) VALUES(?,?,?)');$q->execute([$rid,$u['id'],$msg]);out(['ok'=>true,'message_id'=>(int)$pdo->lastInsertId()]);
 case 'getmessages':
  $u=user($pdo);$rid=(int)($d['room_id']??0);$q=$pdo->prepare('SELECT 1 FROM room_members WHERE room_id=? AND user_id=?');$q->execute([$rid,$u['id']]);if(!$q->fetch())out(['ok'=>false,'error'=>'Not a member'],403);
  $q=$pdo->prepare('SELECT m.id,m.body AS message,m.created_at,u.username,u.display_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.room_id=? ORDER BY m.id DESC LIMIT 100');$q->execute([$rid]);out(['ok'=>true,'messages'=>array_reverse($q->fetchAll())]);
 default: out(['ok'=>false,'error'=>'Unknown action'],404);
 }
} catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();if((string)$e->getCode()==='23000')out(['ok'=>false,'error'=>'Username already exists'],409);out(['ok'=>false,'error'=>'Request failed'],500);}
