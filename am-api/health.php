<?php
declare(strict_types=1);
require_once __DIR__.'/db.php'; require_once __DIR__.'/functions.php';
try { ensureDatabase(); db()->query('SELECT 1'); jsonResponse(['status'=>'ok','service'=>API_NAME,'version'=>API_VERSION,'database'=>'connected','message'=>'API is running']); } catch(Throwable $e) { jsonResponse(['status'=>'error','service'=>API_NAME,'database'=>'error','message'=>'Database connection failed'],500); }
