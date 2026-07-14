<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=1; include("auth.php"); ?>

<?php
	if (isset($confirma)) {
		$db->query("DELETE FROM tb_atendimentos WHERE atendimento_id=$atendimento_id");
		$db->query("DELETE FROM tb_atendimentos_produtos WHERE atendimento_id=$atendimento_id");
		$db->query("DELETE FROM tb_atendimentos_terminais WHERE atendimento_id=$atendimento_id");
		$db->query("DELETE FROM tb_atendimentos_poroes WHERE atendimento_id=$atendimento_id");

		$filelist = glob('planos/plano_de_carga'.$atendimento_id.'.*' );

		foreach( $filelist as $fileitem ) {
			unlink( $fileitem );
		}

		$msg = "Atendimento removido com sucesso!";
		$redir="atendimentos_listar.php";
	}
?>
<!doctype html>
<html lang="en">
	<head>
		<title>Standard Brazil - Administrative area</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<link href="css/bootstrap.min.css" type="text/css" rel="stylesheet" >
		<link type="text/css" rel="stylesheet" href="css/padrao.css?suid=<?php echo (microtime(true) * 10000.); ?>" />
	</head>
	<body>
		<?php include("menu.html"); ?>
		<main role="main" class="container">
			<table width="100%" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
			  <tr align="center">
				<td bgcolor="#FFFFFF">
					<div class="box2">
				  <div class="test">
					<div class="titulo"><span class="margem">
					  <?php if (isset($msg)) echo "<script language=\"JavaScript\"> window.alert(\"$msg\");</script>"; if (isset($redir)) echo "<script language=\"JavaScript\"> window.location=\"$redir\";</script>" ?> 
					</span>
					ATENDIMENTOS - REMOVER ATENDIMENTO</div>
					<p>Tem certeza que deseja remover o atendimento?</p>
					<form action="" method="post" name="form1" id="form1">
					  <input type="submit" name="confirma" id="confirma" value="Remover atendimento!" />
					  <input name="atendimento_id" type="hidden" id="atendimento_id" value="<?php echo $atendimento_id; ?>" />
					</form>
				  </div>
				  <br />
					<p></p>
				  </div></td>
			  </tr>
			</table>
		</main>
	</body>
</html>
