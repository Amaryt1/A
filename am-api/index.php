<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';
require_once __DIR__.'/functions.php';
jsonResponse(['status'=>'ok','service'=>API_NAME,'version'=>API_VERSION,'message'=>'AM API is available','endpoints'=>['GET /am-api/health','POST /am-api/register','POST /am-api/login','GET /am-api/me','POST /am-api/logout']]);
