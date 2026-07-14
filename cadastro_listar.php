<?php include "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=2; include("auth.php"); ?>

<?php
	include_once "ez_results.php"; 

  $query = "SELECT cadastro_id, nome, ultimoacesso, nivel, ativo FROM tb_cadastro ORDER BY nome";
  $rows = $db->query($query); // or die(mysql_error());  
  $cadastro = $db->get_results( $query );
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
	
		<div role="main" class="container" align="center" style="padding:5px">
			<?php
        $numRecords = 0;
        if( isset($cadastro) ) {
          $numRecords = count($cadastro);
        }	

				if( $numRecords > 0 ) {
					echo '<div>';
					echo '<table>';
					echo '<tr>';
					echo '<th><strong>Nome</strong></th>';
					echo '<th><strong>Último acesso</strong></th>';
					echo '<th><strong>Nível</strong></th>';
					echo '<th><strong>Ativo?</strong></th>';
          echo '<th><strong>Editar</strong></th>';
          echo '<th><strong>Apagar</strong></th>';
					echo '</tr>';
					
					$i = 0;

					while( $i<$numRecords ) {
						echo '<tr>';
						echo '<td align="left" >'.$cadastro[$i]->nome.'</td>';
						echo '<td align="center">'.$cadastro[$i]->ultimoacesso.'</td>';
						echo '<td align="center">'.$cadastro[$i]->nivel.'</td>';
						echo '<td align="center">'.$cadastro[$i]->ativo.'</td>';
						echo '<td><p onclick="openModalWindow('."'cadastro_gerenciar_api.php?cadastro_id=".$cadastro[$i]->cadastro_id."'".')"><u>Editar</u></td>';
						echo '<td align="center"><p onclick="window.open('."'";
						echo 'cadastro_remover.php?cadastro_id='.$cadastro[$i]->cadastro_id;
						echo "')".'"><u>Apagar</u></td>';

						$i = $i + 1; 
					}

					echo '</table>';
					echo '</div>';
				}
  		?>
		</div>
		<?php include_once("script.html"); ?>

	</body>
</html>