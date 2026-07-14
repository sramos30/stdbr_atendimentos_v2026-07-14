<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=2; include("auth.php"); ?>

<?php
	$produto_id = intval($_GET['produto_id']);

	if (isset($confirma) && $produto_id > 0 ) {
		$db->query("DELETE FROM tb_produtos WHERE produto_id=$produto_id");

		$msg = "Produto removido do cadastro com sucesso!";
		$redir="produtos_listar.php";
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
					  <?php 
              if (isset($msg))
                echo "<script language=\"JavaScript\"> window.alert(\"$msg\");</script>";

              if (isset($redir)) 
                echo "<script language=\"JavaScript\"> window.location=\"$redir\";</script>" 
            ?> 
					</span>
					REMOVER PRODUTO</div>
					<p>Tem certeza que deseja remover este produto?</p>
					<form action="" method="post" name="form1" id="form1">
					  <input type="submit" name="confirma" id="confirma" value="Remover produto!" />
					  <input name="produto_id" type="hidden" id="produto_id" value="<?php echo $produto_id; ?>" />
					</form>
				  </div>
				  <br />
					<p></p>
				  </div>
        </td>
			  </tr>
			</table>
		</main>
	</body>
</html>
