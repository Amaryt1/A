<?php
declare(strict_types=1);

function jsonResponse(array $data, int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

function getJsonBody(): array {
    $raw=file_get_contents('php://input');
    if($raw===false||trim($raw)==='') return [];
    $data=json_decode($raw,true);
    if(!is_array($data)) jsonResponse(['status'=>'error','message'=>'Invalid JSON body'],400);
    return $data;
}

function requestMethod(string $method): void {
    if(($_SERVER['REQUEST_METHOD']??'')!==strtoupper($method)){
        header('Allow: '.strtoupper($method));
        jsonResponse(['status'=>'error','message'=>'Method not allowed'],405);
    }
}

function bearerToken(): ?string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if ($header !== '' && preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
        return trim($m[1]);
    }

    $fallback = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? $_SERVER['HTTP_X_ACCESS_TOKEN'] ?? '';
    if ($fallback !== '') return trim($fallback);

    // Temporary compatibility fallback for testing on hosts that strip auth headers.
    $queryToken = $_GET['token'] ?? '';
    return is_string($queryToken) && $queryToken !== '' ? trim($queryToken) : null;
}

function issueToken(int $userId): string {
    $token=bin2hex(random_bytes(32));
    $hash=hash('sha256',$token);
    $expires=(new DateTimeImmutable('+'.TOKEN_EXPIRE_DAYS.' days'))->format('Y-m-d H:i:s');
    $stmt=db()->prepare('UPDATE users SET token_hash=?, token_expires_at=? WHERE id=?');
    $stmt->execute([$hash,$expires,$userId]);
    return $token;
}

function authenticatedUser(): array {
    $token=bearerToken();
    if(!$token) jsonResponse(['status'=>'error','message'=>'Authorization token required'],401);
    $stmt=db()->prepare('SELECT id,username,email,created_at FROM users WHERE token_hash=? AND token_expires_at>NOW() LIMIT 1');
    $stmt->execute([hash('sha256',$token)]);
    $user=$stmt->fetch();
    if(!$user) jsonResponse(['status'=>'error','message'=>'Invalid or expired token'],401);
    return $user;
}

function validateUsername(string $username): bool {
    return (bool)preg_match('/^[A-Za-z0-9_.-]{3,100}$/',$username);
}
