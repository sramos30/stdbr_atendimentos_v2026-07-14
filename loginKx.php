<?php
// Primitivos de baixo nível para o ECDH+AEAD do login (libsodium). Zero regra de
// negócio, zero acesso a banco - só geração/rotação/armazenamento do par de
// chaves efêmero do servidor e as operações de derivação/cifra. Chamado
// exclusivamente por dentro de Cadastro (api/objects/usuario.php) - nenhum
// endpoint deve incluir ou chamar este arquivo diretamente.

define('LOGIN_KX_ARQUIVO_CHAVES', dirname(__FILE__).'/.login_kx_keys.json');
define('LOGIN_KX_ROTACAO_SEGUNDOS', 15 * 60);
define('LOGIN_KX_JANELA_GRACA_SEGUNDOS', 15 * 60);

function loginKxLerArquivo() {
    if (!file_exists(LOGIN_KX_ARQUIVO_CHAVES)) {
        return [];
    }

    $dados = json_decode(file_get_contents(LOGIN_KX_ARQUIVO_CHAVES), true);

    return is_array($dados) ? $dados : [];
}

function loginKxGravarArquivo(array $chaves) {
    $tmp = LOGIN_KX_ARQUIVO_CHAVES.'.tmp.'.getmypid();
    file_put_contents($tmp, json_encode($chaves));
    chmod($tmp, 0600);
    rename($tmp, LOGIN_KX_ARQUIVO_CHAVES);
}

// Devolve a chave efêmera mais nova do servidor, rotacionando se a atual tiver
// passado do intervalo de rotação. Poda entradas expiradas (fora da janela de
// graça) a cada chamada.
function loginKxObterOuRotacionarChaveAtual() {
    $fp = fopen(LOGIN_KX_ARQUIVO_CHAVES, 'c+');
    flock($fp, LOCK_EX);

    $chaves = loginKxLerArquivo();
    $agora = time();

    $maisNova = $chaves[0] ?? null;

    if (!$maisNova || ($agora - $maisNova['createdAt']) > LOGIN_KX_ROTACAO_SEGUNDOS) {
        $par = sodium_crypto_kx_keypair();

        $maisNova = [
            'kid' => $agora.'-'.bin2hex(random_bytes(4)),
            'publicKey' => base64_encode(sodium_crypto_kx_publickey($par)),
            'secretKey' => base64_encode(sodium_crypto_kx_secretkey($par)),
            'createdAt' => $agora,
            'expiresAt' => $agora + LOGIN_KX_ROTACAO_SEGUNDOS + LOGIN_KX_JANELA_GRACA_SEGUNDOS,
        ];

        array_unshift($chaves, $maisNova);
    }

    $chaves = array_values(array_filter($chaves, function ($c) use ($agora) {
        return $c['expiresAt'] >= $agora;
    }));

    loginKxGravarArquivo($chaves);

    flock($fp, LOCK_UN);
    fclose($fp);

    return $maisNova;
}

// Busca uma chave específica por kid (sem rotacionar) - usado na hora de
// decifrar um login em andamento, que pode ter sido iniciado com a chave
// anterior à rotação mais recente (janela de graça).
function loginKxBuscarPorKid($kid) {
    $fp = fopen(LOGIN_KX_ARQUIVO_CHAVES, 'c+');
    flock($fp, LOCK_SH);
    $chaves = loginKxLerArquivo();
    flock($fp, LOCK_UN);
    fclose($fp);

    $agora = time();

    foreach ($chaves as $chave) {
        if ($chave['kid'] === $kid && $chave['expiresAt'] >= $agora) {
            return $chave;
        }
    }

    return null;
}

function loginKxDerivarChavesServidor(array $chaveServidor, $clientPublicKeyRaw) {
    // A extensão sodium do PHP (ao contrário de outras bindings) espera o
    // keypair combinado (secretKey.publicKey, 64 bytes), não pk/sk separados -
    // confirmado via ReflectionFunction nesta versão (PHP 8.3 + ext-sodium).
    $serverPk = base64_decode($chaveServidor['publicKey']);
    $serverSk = base64_decode($chaveServidor['secretKey']);
    $serverKeyPair = $serverSk.$serverPk;

    return sodium_crypto_kx_server_session_keys($serverKeyPair, $clientPublicKeyRaw);
}

// AAD derivado de kid+clientPublicKey, nunca transmitido separadamente - cliente
// e servidor recalculam os dois de forma independente a partir dos campos que já
// estão no envelope. Isso amarra o ciphertext a esses dois campos sem precisar
// confiar num campo "aad" solto que poderia ser adulterado (o próprio texto
// cifrado só decifra corretamente se o aad recalculado bater com o que foi usado
// na cifragem original).
function loginKxAad($kid, $clientPublicKeyRaw) {
    return $kid.'|'.base64_encode($clientPublicKeyRaw);
}

function loginKxCifrar($dadosArray, $tx, $kid, $clientPublicKeyRaw) {
    $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
    $aad = loginKxAad($kid, $clientPublicKeyRaw);

    $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
        json_encode($dadosArray), $aad, $nonce, $tx
    );

    return [
        'aeadNonce' => base64_encode($nonce),
        'ciphertext' => base64_encode($ciphertext),
    ];
}

function loginKxDecifrar(array $envelope, $rx, $kid, $clientPublicKeyRaw) {
    $nonce = base64_decode($envelope['aeadNonce']);
    $ciphertext = base64_decode($envelope['ciphertext']);
    $aad = loginKxAad($kid, $clientPublicKeyRaw);

    return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, $aad, $nonce, $rx);
}
