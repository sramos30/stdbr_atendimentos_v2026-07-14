<?php
include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=0; include("auth.php");

// (Antes havia uma linha em branco aqui, fora de tags PHP, entre este bloco e o
// de baixo - isso já manda saída pro corpo da resposta e faz o setcookie() da
// troca de senha, mais abaixo, falhar silenciosamente por "headers already
// sent". Os dois blocos foram unidos por isso.)

// Genérico pra qualquer tipo de usuário (real ou QR) - $tokenPayload['tipo'] já
// carrega isso, Cadastro::atualizarSenha() é parametrizado por tipo. Toda a
// lógica de senha (hash, zerar redefineSenha) vive em Cadastro, não aqui.
if (isset($adicionar)) {
	if($senha2!='') {
		$resultado = $cadastro->atualizarSenha($tokenPayload['tipo'], $tokenPayload['usuario_id'], $senha2, $tokenPayload);

		if ($resultado['rc']) {
			$tokenPayload['precisaTrocarSenha'] = false;
			$cadastro->renovarToken($tokenPayload);
			$msg = "Senha alterada com sucesso!";
		} else {
			$msg = "Não foi possível alterar a senha (mínimo de 6 caracteres).";
		}
	}
}
?>
<!doctype html>
<html lang="en">
	<head>
		<title>Standard Brazil - Administrative area</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<link href="css/bootstrap.min.css" type="text/css" rel="stylesheet" >
		<link href="css/padrao.css" type="text/css" rel="stylesheet"/>
	</head>
	<body>
		<?php include("menu.html"); ?>
		<main role="main" class="container">
			<table width="1000" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
			  <tr>
				<td valign="top" bgcolor="#FFFFFF"><div class="box2">
				  <div class="test">
					<div class="titulo"><strong><span class="margem">
					  <?php if (isset($msg)) echo "<script language=\"JavaScript\"> window.alert(\"$msg\");</script>" ?>
					</span>ALTERAR SENHA</strong></div>
					<form action="" method="post" name="noticia" id="noticia">
					  <table width="100%"  border="0" cellspacing="0" cellpadding="7">
						<tr>
						  <td width="22%" align="right">Nova senha:</td>
						  <td width="78%"><input name="senha2" type="text" id="senha2" size="9" maxlength="15" class="required"/></td>
						</tr>
					  </table>
					  <p align="center">
						<input name="adicionar" type="submit" id="adicionar" value="Alterar" />
					  </p>
					</form>
					<p>&nbsp;</p>
				  </div>
				  <br />
					<p></p>
				  </div></td>
			  </tr>
			</table>
		</main>
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
	
	</body>
</html>
