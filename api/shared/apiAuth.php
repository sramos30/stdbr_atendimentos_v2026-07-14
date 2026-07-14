<?php
// Exige um token válido, devolvendo 401 em JSON quando ausente/expirado/sem os
// grupos exigidos. Usado pelos endpoints de api/*.php que não têm motivo pra
// ficar acessíveis sem login (correção emergencial - Fase 0).
//
// Fala só com Cadastro (api/objects/usuario.php) - não inclui nem chama
// tokenAuth.php diretamente, todo o escopo de autorização vive lá.
//
// Opcionalmente, defina $apiAuthGruposExigidos (bitmap) ANTES de incluir este
// arquivo para exigir uma capacidade específica; sem isso, exige só GRUPO_VER
// (equivalente a "qualquer usuário logado", igual ao comportamento anterior).
// Nota: como as constantes GRUPO_* agora vivem em api/objects/usuario.php (não
// mais em tokenAuth.php), quem usar esse padrão precisa garantir que
// objects/usuario.php já foi incluído antes de referenciar GRUPO_*.

include_once dirname(__FILE__).'/../config/core.php';
include_once dirname(__FILE__).'/../config/database.php';
include_once dirname(__FILE__).'/../objects/usuario.php';

$database = new Database($config);
$pdo = $database->getConnection();
$cadastro = new Cadastro($pdo, null);

$gruposExigidos = isset($apiAuthGruposExigidos) ? $apiAuthGruposExigidos : GRUPO_VER;

$tokenPayload = $cadastro->autorizar($gruposExigidos);

if (!$tokenPayload) {
    http_response_code(401);
    echo json_encode(["error" => "not authenticated"]);
    exit;
}

// Renova o token (desliza a expiração a cada requisição válida) e atualiza
// ultimo_acesso internamente.
$cadastro->renovarToken($tokenPayload);
