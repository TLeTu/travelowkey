<?php
// Minimal JWT + auth helpers (HS256) for this project

// Configuration
function jwt_config() {
    $secret = getenv('JWT_SECRET');
    if (!$secret || $secret === '') {
        // NOTE: Change this in production via environment variable
        $secret = 'change-this-secret-in-production-'.substr(hash('sha256', __FILE__), 0, 16);
    }
    return [
        'secret' => $secret,
        'iss' => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'),
        'aud' => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'),
        'expSeconds' => 3600,
        'cookieName' => 'auth_token'
    ];
}

function b64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function sign_jwt($claims) {
    $cfg = jwt_config();
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now = time();
    if (!isset($claims['iat'])) $claims['iat'] = $now;
    if (!isset($claims['exp'])) $claims['exp'] = $now + $cfg['expSeconds'];
    if (!isset($claims['iss'])) $claims['iss'] = $cfg['iss'];
    if (!isset($claims['aud'])) $claims['aud'] = $cfg['aud'];

    $header_enc = b64url_encode(json_encode($header));
    $payload_enc = b64url_encode(json_encode($claims));
    $signature = hash_hmac('sha256', $header_enc . '.' . $payload_enc, $cfg['secret'], true);
    $signature_enc = b64url_encode($signature);
    return $header_enc . '.' . $payload_enc . '.' . $signature_enc;
}

function verify_jwt($jwt) {
    if (!$jwt || strpos($jwt, '.') === false) return [false, null];
    $cfg = jwt_config();
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return [false, null];

    list($h, $p, $s) = $parts;
    $expected = b64url_encode(hash_hmac('sha256', $h . '.' . $p, $cfg['secret'], true));
    if (!hash_equals($expected, $s)) return [false, null];

    $payload = json_decode(b64url_decode($p), true);
    if (!$payload) return [false, null];
    $now = time();
    if (isset($payload['exp']) && $now > intval($payload['exp'])) return [false, null];
    if (isset($payload['nbf']) && $now < intval($payload['nbf'])) return [false, null];
    // Optionally check iss/aud here if needed
    return [true, $payload];
}

function set_auth_cookie($jwt, $expiresAt = null) {
    $cfg = jwt_config();
    if ($expiresAt === null) $expiresAt = time() + $cfg['expSeconds'];
    // Secure flag only when HTTPS
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $opts = [
        'expires' => $expiresAt,
        'path' => '/',
        'secure' => $isSecure, // set to true when serving over HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ];
    setcookie($cfg['cookieName'], $jwt, $opts);
}

function clear_auth_cookie() {
    $cfg = jwt_config();
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    setcookie($cfg['cookieName'], '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

function get_auth_token_from_cookie() {
    $cfg = jwt_config();
    return isset($_COOKIE[$cfg['cookieName']]) ? $_COOKIE[$cfg['cookieName']] : null;
}

function get_auth_user_id() {
    $jwt = get_auth_token_from_cookie();
    list($ok, $payload) = verify_jwt($jwt);
    if (!$ok) return null;
    return isset($payload['sub']) ? $payload['sub'] : null;
}

function require_auth() {
    $uid = get_auth_user_id();
    if (!$uid) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
    return $uid;
}
