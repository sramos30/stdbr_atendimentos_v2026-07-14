<?php
	// Reescrito para falar só com Cadastro (api/objects/usuario.php) - todo o
	// escopo de token/autorização vive lá, este arquivo não inclui nem chama
	// tokenAuth.php diretamente. Mantém a mesma interface externa ($refid,
	// $permissao) para não exigir mudança nos ~30 arquivos que já fazem
	// `$permissao=N; include("auth.php");` - $permissao (0/1/2) é traduzido
	// internamente pros bits de grupo exigidos.

	include_once dirname(__FILE__).'/api/config/core.php';
	include_once dirname(__FILE__).'/api/config/database.php';
	include_once dirname(__FILE__).'/api/objects/usuario.php';

	if( $refid != "login.php" ) {
		$database = new Database($config);
		$pdo = $database->getConnection();
		$cadastro = new Cadastro($pdo, null);

		if( !isset($permissao) )
			$permissao = 0;

		$nivelParaBits = [
			0 => GRUPO_VER,
			1 => GRUPO_VER | GRUPO_CRIAR | GRUPO_ALTERAR | GRUPO_EXCLUIR | GRUPO_GERENCIAR_CATALOGO | GRUPO_CADASTRAR_USUARIO_QR,
			2 => GRUPO_VER | GRUPO_CRIAR | GRUPO_ALTERAR | GRUPO_EXCLUIR | GRUPO_GERENCIAR_CATALOGO | GRUPO_CADASTRAR_USUARIO_QR | GRUPO_GERENCIAR_USUARIOS,
		];

		$gruposExigidos = isset($nivelParaBits[$permissao]) ? $nivelParaBits[$permissao] : GRUPO_VER;

		$tokenPayload = $cadastro->autorizar($gruposExigidos);

		if( !$tokenPayload ) {
			header('Location: login.php');
			exit;
		}

		// Login feito com a senha inicial/redefinida (redefineSenha) - obrigatório
		// trocar antes de acessar qualquer outra página.
		if( !empty($tokenPayload['precisaTrocarSenha']) && $refid != "alterar_senha.php" ) {
			$cadastro->renovarToken($tokenPayload);
			header('Location: alterar_senha.php');
			exit;
		}

		// Renova o token (desliza a expiração a cada requisição válida) e
		// atualiza ultimo_acesso/ultimo_acesso internamente.
		$cadastro->renovarToken($tokenPayload);
	}
?>
