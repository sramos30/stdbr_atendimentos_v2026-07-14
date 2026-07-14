<?php

include_once dirname(__FILE__).'/../../tokenAuth.php';
include_once dirname(__FILE__).'/../../loginKx.php';

// Bitmap de capacidades - substituiu o "nivel" hierárquico antigo (0/1/2).
// Definidos aqui (não em tokenAuth.php) porque a semântica de autorização é
// domínio de Cadastro, não de transporte/criptografia de token.
define('GRUPO_VER', 1);
define('GRUPO_CRIAR', 2);
define('GRUPO_ALTERAR', 4);
define('GRUPO_EXCLUIR', 8);
define('GRUPO_GERENCIAR_CATALOGO', 16);
define('GRUPO_CADASTRAR_USUARIO_QR', 32);
define('GRUPO_GERENCIAR_USUARIOS', 64);

class Cadastro {
    // database connection and table name
    private $conn;

    // utilities
    private $utilities;

    // constructor with $db as database connection
    public function __construct($db,$utilities){
        $this->conn = $db;
        $this->utilities = $utilities;
    }

    function runQuery( $kwId, $kwEmail, $select, $page, $kwRecsPPage ) {
        $stmt = null;

        if( $kwRecsPPage <= 0 ) {
            $kwRecsPPage = 655360;
            $from_record_num = 0;
        } else {
            $from_record_num = ($kwRecsPPage * $page) - $kwRecsPPage;
        }

        $query = "SELECT ".$select;
        $query .= " FROM tb_cadastro a WHERE 1 ";

        if( strlen($kwId) > 0 ) {
            $query .= " AND a.cadastro_id = '$kwId' ";
        } else if( strlen($kwEmail) > 0 ) {
            $kwEmail=htmlspecialchars(strip_tags($kwEmail));

            $query .= " AND a.email = '$kwEmail' ";
        }

        $query .= " LIMIT $kwRecsPPage OFFSET $from_record_num;";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        $stmt->execute();

        return $stmt;
    }

    // read cadastro
    function tb_cadastro($kwId, $kwEmail, $page, $kwRecsPPage){
        // select all query
        $select = "cadastro_id, nome, email, ultimoacesso, nivel, ativo, redefineSenha";

        $stmt = $this->runQuery($kwId, $kwEmail, $select, $page, $kwRecsPPage);
        $num = $stmt->rowCount();

        return $stmt;
    }

    function count($kwId, $kwEmail) {
        $select = "COUNT(*) as total_rows ";
        $stmt = $this->runQuery($kwId, $kwEmail, $select, 0, -1 );
        $num = $stmt->rowCount();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_rows'];
    }

    function getNewRecordId() {
        $newValue = -1;
        $stmt = null;

        $query = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_NAME = 'tb_cadastro'";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        $stmt->execute();

        if( $stmt->rowCount() > 0 ) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $newValue = $row["AUTO_INCREMENT"];
        }

        return $newValue;
    }

    // Cria um novo usuário real (tb_cadastro). Exige GRUPO_GERENCIAR_USUARIOS do
    // chamador - é parte do mesmo CRUD administrativo de update(). redefineSenha
    // (senha temporária inicial) é hasheada antes de gravar.
    function insertNew($objCadastro, array $tokenPayloadChamador) {
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
        );

        if( !$this->possuiGrupos($tokenPayloadChamador, GRUPO_GERENCIAR_USUARIOS) ) {
            $errorStr["err_code"] = -403;
            $errorStr["err_msg"] = "não autorizado";
            return $errorStr;
        }

        if( !$objCadastro ) {
            $errorStr["err_code"] = -1;
            $errorStr["err_msg"] = "Nothing to insert";
            return $errorStr;
        }

        try {
            $redefineSenhaHash = strlen($objCadastro["redefineSenha"] ?? '') > 0
                ? password_hash($objCadastro["redefineSenha"], PASSWORD_DEFAULT)
                : null;

            $stmt = $this->conn->prepare(
                "INSERT INTO tb_cadastro (cadastro_id, nome, email, ultimoacesso, nivel, ativo, redefineSenha) ".
                "VALUES (:cadastro_id, :nome, :email, '0000-00-00 00:00:00', :nivel, :ativo, :redefineSenha)"
            );

            $stmt->execute([
                'cadastro_id' => intval($objCadastro["cadastro_id"]),
                'nome' => strip_tags($objCadastro["nome"]),
                'email' => strip_tags($objCadastro["email"]),
                'nivel' => strip_tags($objCadastro["nivel"]),
                'ativo' => strip_tags($objCadastro["ativo"]),
                'redefineSenha' => $redefineSenhaHash,
            ]);

            $errorStr["rc_rowCount"] = $stmt->rowCount();

            if( $stmt->rowCount() > 0 )
                $errorStr["rc"] = true;

        } catch (PDOException $exception) {
            $errorStr["err_code"] = $exception->getCode();
            $errorStr["err_msg"] = $exception->getMessage();
        }

        return $errorStr;
    }

    // CRUD administrativo de usuário real. Exige GRUPO_GERENCIAR_USUARIOS do
    // chamador - checagem só aqui dentro, nenhum endpoint HTTP duplica essa
    // lógica. Allowlist de campos: nome/email/nivel/grupos/cliente/ativo/
    // redefineSenha. senha e ultimoacesso/ultimo_acesso NUNCA são editáveis por
    // aqui (propriedade exclusiva do sistema de login) - bloqueio explícito antes
    // mesmo da allowlist, redundância proposital. redefineSenha é hasheado antes
    // de gravar (é assim que o admin "reseta" a senha de outro usuário).
    function update($objCadastro, array $tokenPayloadChamador) {
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
        );

        if( !$this->possuiGrupos($tokenPayloadChamador, GRUPO_GERENCIAR_USUARIOS) ) {
            $errorStr["err_code"] = -403;
            $errorStr["err_msg"] = "não autorizado";
            return $errorStr;
        }

        if( array_key_exists('senha', $objCadastro) || array_key_exists('ultimoacesso', $objCadastro)
            || array_key_exists('ultimo_acesso', $objCadastro) ) {
            $errorStr["err_code"] = -400;
            $errorStr["err_msg"] = "campo não permitido";
            return $errorStr;
        }

        $id = intval($objCadastro["cadastro_id"] ?? 0);

        $allowlist = ['nome', 'email', 'nivel', 'grupos', 'cliente', 'ativo', 'redefineSenha'];
        $campos = array_intersect_key($objCadastro, array_flip($allowlist));

        if( array_key_exists('redefineSenha', $campos) && strlen((string)$campos['redefineSenha']) > 0 ) {
            $campos['redefineSenha'] = password_hash($campos['redefineSenha'], PASSWORD_DEFAULT);
        }

        if( $id <= 0 || empty($campos) ) {
            $errorStr["err_code"] = -1;
            $errorStr["err_msg"] = "No fields to update";
            return $errorStr;
        }

        try {
            $sets = [];
            $params = ['id' => $id];

            foreach( $campos as $campo => $valor ) {
                $sets[] = "$campo = :$campo";
                $params[$campo] = strip_tags((string)$valor);
            }
            // redefineSenha já é um hash bcrypt (não deve passar por strip_tags,
            // que não altera esse alfabeto, mas por clareza recomputa se preciso).
            if( array_key_exists('redefineSenha', $campos) ) {
                $params['redefineSenha'] = $campos['redefineSenha'];
            }

            $query = "UPDATE tb_cadastro SET ".implode(', ', $sets)." WHERE cadastro_id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);

            $errorStr["rc_rowCount"] = $stmt->rowCount();
            $errorStr["rc"] = true;
        } catch (PDOException $exception) {
            $errorStr["rc"] = false;
            $errorStr["err_code"] = $exception->getCode();
            $errorStr["err_msg"] = $exception->getMessage();
        }

        return $errorStr;
    }

    // Parametrização por tipo de usuário - evita duplicar SQL entre tb_cadastro
    // (usuário real) e tb_usuarios_qr (usuário temporário/QR).
    private function config($tipo) {
        $mapa = [
            'real' => [
                'tabela' => 'tb_cadastro',
                'pk' => 'cadastro_id',
                'campoId' => 'email',
                'colUltimoAcesso' => 'ultimoacesso',
                'extraWhere' => "ativo = 'S'",
            ],
            'qr' => [
                'tabela' => 'tb_usuarios_qr',
                'pk' => 'usuario_qr_id',
                'campoId' => 'identificacao',
                'colUltimoAcesso' => 'ultimo_acesso',
                'extraWhere' => "ativo = 'S' AND validade > NOW()",
            ],
        ];

        return $mapa[$tipo] ?? null;
    }

    // Hash-dummy fixo (computado uma vez por request) pra rodar password_verify()
    // mesmo quando a linha não existe - mitiga enumeração de usuário/timing.
    private static function hashDummy() {
        static $hash = null;

        if ($hash === null) {
            $hash = password_hash('nao-existe-nunca-bate-mitigacao-timing-enumeracao', PASSWORD_DEFAULT);
        }

        return $hash;
    }

    // Valida credenciais contra tb_cadastro ('real') ou tb_usuarios_qr ('qr').
    // Aceita senha OU redefineSenha (senha temporária) - só a segunda marca
    // precisaTrocarSenha. Devolve os dados normalizados prontos pra virar payload
    // de token, ou null se inválido.
    function validarSenha($tipo, $identificador, $senhaInformada, array $contexto = []) {
        if (strlen($senhaInformada) === 0) {
            return null;
        }

        $cfg = $this->config($tipo);
        if (!$cfg) {
            return null;
        }

        $sql = "SELECT * FROM {$cfg['tabela']} WHERE {$cfg['campoId']} = :id AND {$cfg['extraWhere']}";
        $params = ['id' => $identificador];

        if ($tipo === 'qr' && isset($contexto['atendimento_id']) && (int)$contexto['atendimento_id'] > 0) {
            $sql .= " AND atendimento_id = :atendimento_id";
            $params['atendimento_id'] = (int)$contexto['atendimento_id'];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$linha) {
            password_verify($senhaInformada, self::hashDummy());
            return null;
        }

        $precisaTrocarSenha = false;

        if (!empty($linha['senha']) && password_verify($senhaInformada, $linha['senha'])) {
            $precisaTrocarSenha = false;
        } elseif (!empty($linha['redefineSenha']) && password_verify($senhaInformada, $linha['redefineSenha'])) {
            $precisaTrocarSenha = true;
        } else {
            return null;
        }

        if ($tipo === 'real') {
            return [
                'tipo' => 'real',
                'usuario_id' => (int)$linha['cadastro_id'],
                'nome' => $linha['nome'],
                'email' => $linha['email'],
                'cliente' => $linha['cliente'] ?? null,
                'grupos' => (int)$linha['grupos'],
                'precisaTrocarSenha' => $precisaTrocarSenha,
            ];
        }

        return [
            'tipo' => 'qr',
            'usuario_id' => (int)$linha['usuario_qr_id'],
            'nome' => $linha['nome'],
            'atendimento_id' => (int)$linha['atendimento_id'],
            'grupos' => (int)$linha['grupos'],
            'precisaTrocarSenha' => $precisaTrocarSenha,
        ];
    }

    // ultimo_acesso/ultimoacesso é propriedade exclusiva deste método - nunca
    // recebido como parâmetro de fora, sempre calculado internamente (hora do
    // servidor). Best-effort: falha de banco aqui não pode derrubar a renovação
    // do cookie de token, já que auth.php/apiAuth.php dependem disso em toda
    // requisição autenticada.
    private function atualizarUltimoAcesso($tipo, $usuarioId) {
        $cfg = $this->config($tipo);
        if (!$cfg || !$this->conn) {
            // Sem config válida ou sem conexão de banco (ex: banco fora do ar) -
            // best-effort, não pode derrubar a renovação do cookie.
            return;
        }

        try {
            $stmt = $this->conn->prepare(
                "UPDATE {$cfg['tabela']} SET {$cfg['colUltimoAcesso']} = :valor WHERE {$cfg['pk']} = :id"
            );
            $stmt->execute(['valor' => date('Y-m-d H:i:s'), 'id' => (int)$usuarioId]);
        } catch (\Throwable $e) {
            // Throwable (não só PDOException) de propósito - qualquer falha aqui
            // (conexão caiu no meio, etc.) não pode ser fatal.
            error_log('Cadastro::atualizarUltimoAcesso falhou: '.$e->getMessage());
        }
    }

    // Emite um token novo (login) - atualiza ultimo_acesso e grava o cookie.
    function gerarToken(array $dadosUsuario) {
        $this->atualizarUltimoAcesso($dadosUsuario['tipo'], $dadosUsuario['usuario_id']);

        $payload = $dadosUsuario;
        $token = definirCookieToken($payload);

        return [$token, $payload];
    }

    // Renova um token existente (expiração deslizante) - também atualiza
    // ultimo_acesso, conteúdo do payload não muda.
    function renovarToken(array $tokenPayloadAtual) {
        $this->atualizarUltimoAcesso($tokenPayloadAtual['tipo'], $tokenPayloadAtual['usuario_id']);
        return definirCookieToken($tokenPayloadAtual);
    }

    // Troca de senha self-service - genérica pra qualquer tipo de usuário (real
    // ou QR). Só o próprio usuário pode trocar a própria senha (não é como o
    // admin reseta a de terceiros, que passa por update()/redefineSenha). Nunca
    // toca ultimo_acesso - quem chama reemite o token depois via gerarToken/
    // renovarToken.
    function atualizarSenha($tipo, $usuarioId, $senhaNova, array $tokenPayloadChamador) {
        $erro = ['rc' => false, 'err_code' => -1, 'err_msg' => ''];

        if (($tokenPayloadChamador['tipo'] ?? null) !== $tipo
            || (int)($tokenPayloadChamador['usuario_id'] ?? 0) !== (int)$usuarioId) {
            $erro['err_code'] = -403;
            $erro['err_msg'] = 'não autorizado';
            return $erro;
        }

        if (strlen((string)$senhaNova) < 6) {
            $erro['err_code'] = -400;
            $erro['err_msg'] = 'senha muito curta';
            return $erro;
        }

        $cfg = $this->config($tipo);
        if (!$cfg) {
            $erro['err_msg'] = 'tipo inválido';
            return $erro;
        }

        try {
            $hash = password_hash($senhaNova, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare(
                "UPDATE {$cfg['tabela']} SET senha = :hash, redefineSenha = NULL WHERE {$cfg['pk']} = :id"
            );
            $stmt->execute(['hash' => $hash, 'id' => (int)$usuarioId]);

            return ['rc' => true, 'err_code' => 0, 'rc_rowCount' => $stmt->rowCount()];
        } catch (PDOException $e) {
            $erro['err_code'] = $e->getCode();
            $erro['err_msg'] = $e->getMessage();
            return $erro;
        }
    }

    // Único ponto de entrada de autorização - só Cadastro conhece/define o
    // conteúdo do payload de token. auth.php/apiAuth.php chamam só isto, nunca
    // tokenAuth.php diretamente.
    function autorizar($gruposExigidos = GRUPO_VER) {
        $payload = obterTokenAtual();

        if (!$payload || !$this->payloadValido($payload) || !$this->possuiGrupos($payload, $gruposExigidos)) {
            limparCookieToken();
            return null;
        }

        return $payload;
    }

    private function payloadValido($payload) {
        if (!is_array($payload)) {
            return false;
        }

        if (!isset($payload['usuario_id'], $payload['tipo'], $payload['grupos'])) {
            return false;
        }

        if (!in_array($payload['tipo'], ['real', 'qr'], true)) {
            return false;
        }

        if (!is_int($payload['grupos'])) {
            return false;
        }

        return true;
    }

    private function possuiGrupos($payload, $gruposExigidos) {
        if (!$payload || !isset($payload['grupos'])) {
            return false;
        }

        return ($payload['grupos'] & $gruposExigidos) === $gruposExigidos;
    }

    // Descoberta de chave pública efêmera do servidor (endpoint login_chave.php).
    // Nunca devolve a chave privada.
    function chavePublicaAtual() {
        $chave = loginKxObterOuRotacionarChaveAtual();

        return [
            'kid' => $chave['kid'],
            'publicKey' => $chave['publicKey'],
            'exp' => $chave['expiresAt'],
        ];
    }

    private function respostaProtocolo($httpStatus, array $corpo) {
        return ['httpStatus' => $httpStatus, 'corpo' => $corpo];
    }

    private function cifrarRespostaFinal(array $dados, $tx, $kid, $clientPublicKeyRaw) {
        $envelopeResposta = loginKxCifrar($dados, $tx, $kid, $clientPublicKeyRaw);
        return $this->respostaProtocolo(200, $envelopeResposta);
    }

    // Anti-replay: dedup de nonce numa janela curta. Colisão de PK (ou qualquer
    // erro) = rejeita por segurança.
    private function registrarNonceOuRejeitar($nonce) {
        try {
            $this->conn->exec("DELETE FROM tb_login_replay WHERE criado_em < NOW() - INTERVAL 2 MINUTE");

            $hash = hash('sha256', (string)$nonce);
            $stmt = $this->conn->prepare(
                "INSERT INTO tb_login_replay (nonce_hash, criado_em) VALUES (:hash, NOW())"
            );
            $stmt->execute(['hash' => $hash]);

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Único método que login.php chama no POST - absorve todo o protocolo
    // ECDH+AEAD (decifra envelope, valida ts/nonce, valida credenciais, emite
    // token, cifra resposta). Nenhum endpoint toca sodium_* diretamente.
    function processarLogin($corpoBrutoRequisicao, array $contexto) {
        $envelope = json_decode($corpoBrutoRequisicao, true);

        if (!is_array($envelope)
            || !isset($envelope['kid'], $envelope['clientPublicKey'], $envelope['aeadNonce'], $envelope['ciphertext'])) {
            return $this->respostaProtocolo(400, ['erro' => 'requisicao_invalida']);
        }

        $chaveServidor = loginKxBuscarPorKid($envelope['kid']);
        if (!$chaveServidor) {
            return $this->respostaProtocolo(409, ['erro' => 'chave_expirada']);
        }

        $clientPublicKeyRaw = base64_decode($envelope['clientPublicKey']);
        [$rx, $tx] = loginKxDerivarChavesServidor($chaveServidor, $clientPublicKeyRaw);

        $plano = loginKxDecifrar($envelope, $rx, $envelope['kid'], $clientPublicKeyRaw);
        if ($plano === false) {
            return $this->respostaProtocolo(400, ['erro' => 'requisicao_invalida']);
        }

        $dadosLogin = json_decode($plano, true);
        if (!is_array($dadosLogin) || !isset($dadosLogin['email'], $dadosLogin['senha'], $dadosLogin['ts'], $dadosLogin['nonce'])) {
            return $this->respostaProtocolo(400, ['erro' => 'requisicao_invalida']);
        }

        if (abs(time() - (int)$dadosLogin['ts']) > 60) {
            return $this->cifrarRespostaFinal(
                ['ok' => false, 'erro' => 'Requisição expirada, tente novamente'],
                $tx, $envelope['kid'], $clientPublicKeyRaw
            );
        }

        if (!$this->registrarNonceOuRejeitar($dadosLogin['nonce'])) {
            return $this->cifrarRespostaFinal(
                ['ok' => false, 'erro' => 'Requisição duplicada'],
                $tx, $envelope['kid'], $clientPublicKeyRaw
            );
        }

        $ctx = $contexto['ctx'] ?? '';
        $atendimentoId = (int)($contexto['atendimento_id'] ?? 0);

        $dadosUsuario = null;

        if ($ctx === 'qr' && $atendimentoId > 0) {
            $dadosUsuario = $this->validarSenha('qr', $dadosLogin['email'], $dadosLogin['senha'], ['atendimento_id' => $atendimentoId]);
        }

        if (!$dadosUsuario) {
            $dadosUsuario = $this->validarSenha('real', $dadosLogin['email'], $dadosLogin['senha']);
        }

        if (!$dadosUsuario) {
            return $this->cifrarRespostaFinal(
                ['ok' => false, 'erro' => 'Usuário ou senha inválida'],
                $tx, $envelope['kid'], $clientPublicKeyRaw
            );
        }

        [$token, $payload] = $this->gerarToken($dadosUsuario);

        if (!empty($dadosLogin['senhaNova']) && !empty($payload['precisaTrocarSenha'])) {
            $resultado = $this->atualizarSenha($payload['tipo'], $payload['usuario_id'], $dadosLogin['senhaNova'], $payload);

            if ($resultado['rc']) {
                $payload['precisaTrocarSenha'] = false;
                [$token, $payload] = $this->gerarToken($payload);
            }
        }

        return $this->cifrarRespostaFinal([
            'ok' => true,
            'next' => $contexto['next'] ?? 'index.php',
            'precisaTrocarSenha' => !empty($payload['precisaTrocarSenha']),
        ], $tx, $envelope['kid'], $clientPublicKeyRaw);
    }
}

?>
