<?php
// Endpoint público, único e exclusivo para a página do QR code (/atendimentos/link).
// Isolado de api/atendimentos.php de propósito: aqui só é possível LER um único
// atendimento (nunca listar, criar, editar ou apagar).
//
// Segurança vem inteiramente da autenticação por token (tokenAuth.php) - "tag" e
// "tk" NÃO são mecanismo de segurança, são só identificação/desambiguação:
//   tag = codAtendimento (referência legível, não é único no banco hoje)
//   tk  = atendimento_id (chave real e única) para QR codes novos; para QR codes
//         antigos já impressos (tk = crc32 antigo, não corresponde a nenhum
//         atendimento_id), cai para busca por codAtendimento, aceitando a mesma
//         ambiguidade que já existe hoje nesses casos - não há como corrigir
//         retroativamente um código já impresso.
//
// Autorização: usuário QR só acessa o atendimento_id ao qual foi vinculado.
// Usuário real acessa livremente (qualquer atendimento), sem restrição adicional.

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once './config/core.php';
include_once dirname(__FILE__).'/../tokenAuth.php';
include_once './config/database.php';
include_once './shared/utilities.php';
include_once './objects/atendimento.php';

if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "method not allowed"]);
    exit;
}

$tag = isset($_GET['tag']) ? htmlspecialchars(strip_tags($_GET['tag'])) : '';
$tk = isset($_GET['tk']) ? htmlspecialchars(strip_tags($_GET['tk'])) : '';

if ($tag === '' && $tk === '') {
    http_response_code(400);
    echo json_encode(["error" => "tag ou tk são obrigatórios"]);
    exit;
}

$tokenPayload = obterTokenAtual();

if (!$tokenPayload) {
    http_response_code(401);
    echo json_encode(["error" => "not authenticated"]);
    exit;
}

$utilities = new Utilities();
$database = new Database($config);
$db = $database->getConnection();
$atendimento = new Atendimento($db, $utilities);

$planosDir = '../planos';
$row = null;

// 1) QR novo: tk é o atendimento_id (chave real, única).
if (ctype_digit((string) $tk)) {
    $stmt = $atendimento->tb_atendimentos(["Id" => $tk, "RecsPPage" => 1, "page" => 1]);

    if ($stmt && $stmt->rowCount() > 0) {
        $candidato = $stmt->fetch(PDO::FETCH_ASSOC);

        // Confere coerência com o tag informado, quando presente.
        if ($tag === '' || $candidato["codAtendimento"] === $tag) {
            $row = $candidato;
        }
    }
}

// 2) QR antigo (impresso antes desta mudança): tk não corresponde a um
// atendimento_id válido - cai para busca por codAtendimento, como já era feito.
if (!$row && $tag !== '') {
    $stmt = $atendimento->tb_atendimentos(["CodAtd" => $tag, "RecsPPage" => 1, "page" => 1]);

    if ($stmt && $stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$row) {
    http_response_code(404);
    echo json_encode(["error" => "atendimento não encontrado"]);
    exit;
}

$atdId = $row["atendimento_id"];

// Autorização: usuário QR só acessa o atendimento_id ao qual foi vinculado.
if ($tokenPayload['tipo'] === 'qr' && (int) $tokenPayload['atendimento_id'] !== (int) $atdId) {
    http_response_code(403);
    echo json_encode(["error" => "sem acesso a este atendimento"]);
    exit;
}

// Renova o token (desliza a expiração a cada requisição válida).
definirCookieToken($tokenPayload);

$out = [];
$out["atdId"] = $atdId;
$out["codAtendimento"] = $row["codAtendimento"];
$out["data"] = $row["data"];
$out["navio"] = $row["navio"];
$out["balanca"] = sprintf("%.3f", $row["balanca"]);
$out["arqueacao"] = sprintf("%.3f", $row["arqueacao"]);
$out["comando_navio"] = sprintf("%.3f", $row["comando_navio"]);
$out["perito_receita"] = sprintf("%.3f", $row["perito_receita"]);
$out["outras_partes1_id"] = $row["outras_partes1_id"];
$out["outras_partes1"] = sprintf("%.3f", $row["outras_partes1"]);
$out["outras_partes2_id"] = $row["outras_partes2_id"];
$out["outras_partes2"] = sprintf("%.3f", $row["outras_partes2"]);
$out["outras_partes3_id"] = $row["outras_partes3_id"];
$out["outras_partes3"] = sprintf("%.3f", $row["outras_partes3"]);
$out["excesso"] = sprintf("%.3f", $row["excesso"]);
$out["falta"] = sprintf("%.3f", $row["falta"]);
$out["diferenca"] = sprintf("%.2f", $row["diferenca"]);
$out["link"] = $row["link"];
$out["cliente"] = $row["cliente"];

$out["lstPlanos"] = [];
$filelist = glob($planosDir.'/plano_de_carga'.$atdId.'.*');

foreach ($filelist as $fileitem) {
    if (preg_match('/\.([pP][dD][fF])$/', $fileitem, $matches) === 1) {
        array_push($out["lstPlanos"], $matches[count($matches) - 1]);
    } elseif (preg_match('/\.([xX][lL][sS].*)$/', $fileitem, $matches) === 1) {
        array_push($out["lstPlanos"], $matches[count($matches) - 1]);
    }
}

$queryParmsFixo = ["Id" => $atdId, "RecsPPage" => 1, "page" => 1];

$out["produtos"] = [];
$stmtProds = $atendimento->tb_atendimentos_produtos($queryParmsFixo);
if ($stmtProds && $stmtProds->rowCount() > 0) {
    while ($rowProd = $stmtProds->fetch(PDO::FETCH_ASSOC)) {
        array_push($out["produtos"], $rowProd["produto_id"]);
    }
}

$out["terminais"] = [];
$stmtTerms = $atendimento->tb_atendimentos_terminais($queryParmsFixo);
if ($stmtTerms && $stmtTerms->rowCount() > 0) {
    while ($rowTerm = $stmtTerms->fetch(PDO::FETCH_ASSOC)) {
        array_push($out["terminais"], $rowTerm["terminal_id"]);
    }
}

$out["poroes"] = [];
$stmtPorTerm = $atendimento->tb_atendimentos_poroes_terminais($queryParmsFixo);
if ($stmtPorTerm && $stmtPorTerm->rowCount() > 0) {
    while ($rowPT = $stmtPorTerm->fetch(PDO::FETCH_ASSOC)) {
        $porao = $rowPT["porao"];

        if (!key_exists($porao, $out["poroes"])) {
            $out["poroes"][$porao] = [
                "produto_id" => intval($rowPT["produto_id"]),
                "fatorestiva" => sprintf("%.2f", $rowPT["fatorestiva"]),
                "cubagem" => intval($rowPT["cubagem"]),
                "condicao" => $rowPT["condicao"],
                "terminais" => [],
            ];
        }

        $out["poroes"][$porao]["terminais"][$rowPT["terminal_id"]]["quantidade"] = sprintf("%.3f", $rowPT["quantidade"]);
    }
}

// Formato compatível com o que loadAtendimento() já espera (objeto indexado pelo
// atdId), para simplificar a troca do lado do front-end.
http_response_code(200);
echo json_encode([$atdId => $out]);
