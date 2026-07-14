<?php
// Script one-shot: migra senha/redefineSenha de texto puro para hash
// (password_hash/PASSWORD_DEFAULT) em tb_cadastro e tb_usuarios_qr.
//
// CLI-only - roda uma vez antes de publicar o código que passa a exigir hash
// (Cadastro::validarSenha() via password_verify()). Idempotente: pode ser
// reexecutado sem dano, pula valores já hasheados e valores vazios/nulos.
//
// Uso: php cli/rehash_senhas.php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Acesso negado - script só roda via CLI.\n");
}

include_once dirname(__FILE__).'/../api/config/core.php';
include_once dirname(__FILE__).'/../api/config/database.php';

$database = new Database($config);
$pdo = $database->getConnection();

if (!$pdo) {
    fwrite(STDERR, "Falha ao conectar no banco: ".$database->err_msg."\n");
    exit(1);
}

$tabelas = [
    ['tabela' => 'tb_cadastro', 'pk' => 'cadastro_id'],
    ['tabela' => 'tb_usuarios_qr', 'pk' => 'usuario_qr_id'],
];

$totalAtualizados = 0;

foreach ($tabelas as $t) {
    $stmt = $pdo->query("SELECT {$t['pk']} AS id, senha, redefineSenha FROM {$t['tabela']}");
    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "{$t['tabela']}: {$stmt->rowCount()} linha(s)\n";

    foreach ($linhas as $linha) {
        foreach (['senha', 'redefineSenha'] as $coluna) {
            $valor = $linha[$coluna];

            if ($valor === null || $valor === '') {
                continue; // nunca hasheia vazio/nulo
            }

            if (str_starts_with($valor, '$2y$') || str_starts_with($valor, '$argon2')) {
                continue; // já hasheado - idempotência
            }

            $hash = password_hash($valor, PASSWORD_DEFAULT);

            $upd = $pdo->prepare("UPDATE {$t['tabela']} SET $coluna = :hash WHERE {$t['pk']} = :id");
            $upd->execute(['hash' => $hash, 'id' => $linha['id']]);

            $totalAtualizados++;
            echo "  {$t['tabela']}.{$t['pk']}={$linha['id']}.$coluna -> hasheado\n";
        }
    }
}

echo "Concluído. $totalAtualizados coluna(s) hasheada(s).\n";
