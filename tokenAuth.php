<?php
// Biblioteca de baixo nível: só criptografia/transporte de token (assinatura
// HMAC-SHA256, cookie). Não conhece o conteúdo/semântica do payload - isso é
// domínio exclusivo de Cadastro (api/objects/usuario.php), que é quem chama
// estas funções por dentro. Nenhum outro arquivo deve incluir/chamar isto
// diretamente - só Cadastro.

define('TOKEN_TTL_SEGUNDOS', 3600);
define('TOKEN_COOKIE_NAME', 'atd_token');

function tokenSecret() {
    static $secret = null;

    if ($secret !== null) {
        return $secret;
    }

    $config_path = dirname(__FILE__).'/';
    $config = [];

    if (file_exists($config_path.'.dev-env')) {
        $config = parse_ini_file($config_path.'.dev-env');
    } elseif (file_exists($config_path.'dbstdbrz2.ini')) {
        $config = parse_ini_file($config_path.'dbstdbrz2.ini');
    }

    $secret = isset($config['token_secret']) ? $config['token_secret'] : null;

    if (!$secret) {
        throw new Exception('token_secret não configurado em .dev-env/dbstdbrz2.ini');
    }

    return $secret;
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function criarToken(array $payload, $ttlSegundos = TOKEN_TTL_SEGUNDOS) {
    $payload['exp'] = time() + $ttlSegundos;

    $payloadB64 = base64UrlEncode(json_encode($payload));
    $assinatura = base64UrlEncode(hash_hmac('sha256', $payloadB64, tokenSecret(), true));

    return $payloadB64.'.'.$assinatura;
}

function validarToken($tokenStr) {
    if (!$tokenStr || strpos($tokenStr, '.') === false) {
        return null;
    }

    list($payloadB64, $assinatura) = explode('.', $tokenStr, 2);

    $assinaturaEsperada = base64UrlEncode(hash_hmac('sha256', $payloadB64, tokenSecret(), true));

    if (!hash_equals($assinaturaEsperada, $assinatura)) {
        return null;
    }

    $payload = json_decode(base64UrlDecode($payloadB64), true);

    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}

function definirCookieToken(array $payload, $ttlSegundos = TOKEN_TTL_SEGUNDOS) {
    $token = criarToken($payload, $ttlSegundos);

    setcookie(TOKEN_COOKIE_NAME, $token, [
        'expires' => time() + $ttlSegundos,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // habilitar quando servido só por HTTPS
    ]);

    return $token;
}

function obterTokenAtual() {
    if (!isset($_COOKIE[TOKEN_COOKIE_NAME])) {
        return null;
    }

    return validarToken($_COOKIE[TOKEN_COOKIE_NAME]);
}

function limparCookieToken() {
    setcookie(TOKEN_COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path' => '/',
    ]);
}
