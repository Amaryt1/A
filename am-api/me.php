<?php
declare(strict_types=1);
require_once __DIR__.'/db.php'; require_once __DIR__.'/functions.php'; requestMethod('GET');
try { ensureDatabase(); $u=authenticatedUser(); jsonResponse(['status'=>'ok','user'=>['id'=>(int)$u['id'],'username'=>$u['username'],'email'=>$u['email'],'created_at'=>$u['created_at']]]); } catch(Throwable $x) { jsonResponse(['status'=>'error','message'=>'Server error'],500); }
