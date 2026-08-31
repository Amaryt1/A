<?php
declare(strict_types=1);
require_once __DIR__.'/db.php'; require_once __DIR__.'/functions.php'; requestMethod('POST');
try { $t=bearerToken(); if($t){ $s=db()->prepare('UPDATE users SET token_hash=NULL, token_expires_at=NULL WHERE token_hash=?'); $s->execute([hash('sha256',$t)]); } jsonResponse(['status'=>'ok','message'=>'Logout successful']); } catch(Throwable $x) { jsonResponse(['status'=>'error','message'=>'Server error'],500); }
