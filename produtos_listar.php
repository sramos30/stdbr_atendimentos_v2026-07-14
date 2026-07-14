<?php include "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=1; include("auth.php"); ?>

<?php
	include_once "ez_results.php"; 

  $query = "SELECT produto_id, nome, descricao, tags FROM tb_produtos ORDER BY nome";
  $rows = $db->query($query); // or die(mysql_error());  
  $produtos = $db->get_results( $query );
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
        if( isset($produtos) ) {
          $numRecords = count($produtos);
        }	

				if( $numRecords > 0 ) {
					echo '<div>';
					echo '<table>';
					echo '<tr>';
					echo '<th><strong>nome</strong></th>';
					echo '<th><strong>descrição</strong></th>';
					echo '<th><strong>Outros nomes</strong></th>';
          echo '<th><strong>Editar</strong></th>';
					echo '<th><strong>Apagar</strong></th>';
					echo '</tr>';
					
					$i = 0;

					while( $i<$numRecords ) {
						echo '<tr>';
						echo '<td align="left" >'.$produtos[$i]->nome.'</td>';
						echo '<td align="left">'.$produtos[$i]->descricao.'</td>';
						echo '<td align="left">'.$produtos[$i]->tags.'</td>';
						echo '<td><p onclick="openModalWindow('."'produtos_gerenciar_api.php?produto_id=".$produtos[$i]->produto_id."'".')"><u>Editar</u></td>';
						echo '<td align="center"><p onclick="window.open('."'";
						echo 'produtos_remover.php?produto_id='.$produtos[$i]->produto_id;
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