<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); include("auth.php"); ?>
<!doctype html>
<html lang="en">
	<head>
		<title>Standard Brazil - Administrative area - <?PHP echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? ''); ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<link href="css/bootstrap.min.css" type="text/css" rel="stylesheet" >
		<link href="css/padrao.css" type="text/css" rel="stylesheet"/>
	</head>
	<body>
		<?php include("menu.html"); ?>
	
		<main role="main" class="container">
			<div class="jumbotron">
				<?php echo "Atendimentos Cadastrados:".$db->get_var("SELECT count(*) FROM tb_atendimentos"); ?>
			</div>
		</main>
		<?php include_once('script.html'); ?>
	</body>
</html>
