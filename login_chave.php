<?php
// Descoberta pública (não autenticada) da chave pública efêmera atual do
// servidor, usada pelo cliente pra iniciar o handshake ECDH+AEAD do login.
// Plumbing puro - toda a lógica de geração/rotação de chave vive em
// Cadastro::chavePublicaAtual() (por dentro, loginKx.php). Nenhum sodium_*
// aqui.

include_once dirname(__FILE__).'/api/config/core.php';
include_once dirname(__FILE__).'/api/config/database.php';
include_once dirname(__FILE__).'/api/objects/usuario.php';

header('Content-Type: application/json; charset=UTF-8');

$database = new Database($config);
$pdo = $database->getConnection();
$cadastro = new Cadastro($pdo, null);

echo json_encode($cadastro->chavePublicaAtual());
