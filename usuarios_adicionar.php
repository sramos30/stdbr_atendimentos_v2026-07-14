<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=1; include("auth.php"); ?>

<?php

	if (isset($_POST['adicionar'])) {
			$db->query("INSERT INTO tb_cadastro (nome, email, senha, nivel) VALUES ('$nome','$email','$senha','$nivel')");		
			$msg = "Usuário adicionado com sucesso!";
			}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Standard Brazil - Área Administrativa</title>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
		<link href="padrao.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<?php include("menu.html"); ?>
	
		<main role="main" class="container">
			<table width="1000" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
			  <tr>
				<td valign="top" bgcolor="#FFFFFF"><div class="box2">
					<table width="100%" border="0" cellpadding="5" cellspacing="0">
					  <tr>
						<td height="21" background="imagens/back_tit.jpg" style="height:21px"><span class="style1">Adicionar
							  Usuário
						  <?php if (isset($msg)) echo "<script language=\"JavaScript\"> window.alert(\"$msg\");window.location='usuarios_editar.php';</script>" ?>
						</span></td>
					  </tr>
					  <tr>
						<td><div class="box4">
						  <form action="" method="post" name="Usuário" id="Usuário" style="display: inline; margin: 0;">
							<table width="100%" cellpadding="4" cellspacing="0">
							  <tr>
								<td>Nome: </td>
								<td><input name="nome" type="text" id="nome4" value="<?php echo $lista->nome ?>" size="45" maxlength="100" /></td>
							  </tr>
							  <tr>
								<td>E-mail:</td>
								<td><input name="email" type="text" id="nome3" value="<?php echo $lista->email ?>" size="45" maxlength="100" /></td>
							  </tr>
							  <tr>
								<td width="12%">Senha:</td>
								<td width="88%"><input name="senha" type="text" id="senha" value="<?php echo $lista->senha ?>" size="45" maxlength="100" /></td>
							  </tr>
							  <tr>
								<td>Nível:</td>
								<td><select name="nivel" id="nivel">
								  <option value="0" selected="selected">Cliente</option>
								  <option value="1">Administrador</option>
								</select></td>
							  </tr>
							</table>
							<p align="center">
							  <input name="adicionar" type="submit" id="adicionar" value="Adicionar Usuário" />
							</p>
						  </form>
						</div>
						</td>
					  </tr>
					</table>
					<p></p>
				</div></td>
			  </tr>
			</table>
		</main>
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
		
		<!--
		<script type="text/javascript" src="scripts/jquery-1.2.6.pack.js"></script>
		<script type="text/javascript" src="scripts/jquery.maskedinput-1.1.4.pack.js"></script>
		<script type="text/javascript">  
			$(document).ready(function(){  
			$(function(){  
			$.mask.addPlaceholder("~","[+-]");  
			$("#telefone").mask("(99) 9999-9999");  
			$("#cnpj").mask("99.999.999/9999-99");  
			});  
			});  
		 </script> 
		-->
	</body>
</html>
