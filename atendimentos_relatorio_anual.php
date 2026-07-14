<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=0; include("auth.php"); ?>
<?php $ano = date("Y"); ?>

<!doctype html>
<html lang="en">
	<head>
		<title>Standard Brazil - Administrative area</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<link href="css/bootstrap.min.css" type="text/css" rel="stylesheet" >
		<link href="css/padrao.css" type="text/css" rel="stylesheet"/>
		<style type="text/css">
			body,td,th {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 12px;
			}
		</style>
	</head>
	<body>
		<?php include("menu.html"); ?>
	
		<main role="main" class="container">
			<span>
				<?php if (isset($msg)) echo "<script language=\"JavaScript\"> window.alert(\"$msg\");</script>" ?>
			</span>

			<table width="1000" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" bgcolor="#FFFFFF">
						<div class="box2">
							<div class="test">
								<div class="titulo">
									<strong>
										ATENDIMENTOS - RELATÓRIOS
									</strong>
								</div>
			
								<form action="" method="post" name="frm" id="frm">
									<table width="100%"  border="0" cellspacing="0" cellpadding="7">
										<tr>
											<!-- td width="22%" align="right">Digite o ano:</td -->
											<td>
												<label for="ano">Digite o ano:</label>
												<input name="ano" type="text" id="ano" size="9" maxlength="15" class="required" placeholder="0000" value="<?php echo $ano ?>"/>
											</td>
										</tr>
									</table>
									<p align="center">
										<input name="adicionar" type="submit" id="first" value="Visualizar Relatório" onClick="document.frm.action='atendimentos_relatorio_anual2.php'"/>
										<br />
										<br />
										<input name="adicionar" type="submit" id="second" value="Visualizar Relatório Gráfico Pizza (Diferença em %)" onClick="document.frm.action='atendimentos_relatorio_anual3.php'" />
										<br />
										<br />
										<input name="second" type="submit" id="second2" value="Visualizar Relatório Gráfico Barra (Diferença em ton)" onclick="document.frm.action='relatorio_grafico3.php'" />
									</p>
									<p align="center">
										<input name="second2" type="submit" id="second3" value="Visualizar Relatório Gráfico Barra (Diferença em %" onclick="document.frm.action='relatorio_grafico2.php'" />
									</p>
								</form>
								<p>&nbsp;</p>
							</div>
							<br />
							<p></p>
						</div>
					</td>
				</tr>
			</table>


		</main>

		<!-- <script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script> -->
		<!-- <script src="https://code.jquery.com/jquery-3.3.1.min.js"  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script> -->
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
		<script type="text/javascript" src="scripts/jquery.validate.js"></script>
		<script type="text/javascript">
			$(document).ready(function()
			{
				$("input:text").addClass("ui-widget ui-state-default ui-corner-all");
				$("input:file").addClass("ui-widget ui-state-default ui-corner-all");
				$("select").addClass("ui-widget ui-state-default ui-corner-all");	
				$("#frm").validate();	
			});
		</script>

	</body>
</html>

