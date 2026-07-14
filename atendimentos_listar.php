<?php include "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=1; include("auth.php"); ?>

<?php
	include_once "ez_results.php"; 

	if( isset($_POST["pagesize"]) AND ((int)$_POST["pagesize"]) > 0 )
		$page_rows = (int)$_POST["pagesize"];
	else 
		$page_rows = 25;

	//if (isset($confirma)) 
	//{
	//	$db->query("DELETE FROM tb_atendimentos WHERE atendimento_id=$atendimento_id");
	//}
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
		<?php
			include("menu.html");
			
			if( !isset($atendId) )
				$atendId = 0;

			if( !isset($navio) )
				$navio = "";

			if( !isset($codAtend) )
				$codAtend = "";

			if( !isset($datainicial) )
				$datainicial = "01/01/".date("Y");

			if( !isset($navio) )
				$navio = '';

			if( isset($_POST["pagenum"]) )
				$pagenum = (int)$_POST["pagenum"]; 
			else
				$pagenum = 0;

			if( isset($_POST["buscar"]) ) {
				$pagenum = 1;

				$whereclause = '';
				
				$atendId = (int)$_POST["atendId"]; 
				$navio = trim($_POST["navio"]);
				$codAtend = trim($_POST["codAtend"]);
				$datainicial = $_POST["datainicial"];

				$query = "";

				if( $atendId > 0 ) {
					$query = "SELECT atendimento_id, codAtendimento, navio, DATE_FORMAT(data,'%d/%m/%Y') as data2, cliente FROM tb_atendimentos WHERE atendimento_id = ".$atendId." ORDER BY data2, codAtendimento";
				}else if( strlen($codAtend)>0 ) {
					$codAtend=htmlspecialchars(strip_tags($codAtend));
					$query = "SELECT atendimento_id, codAtendimento, navio, DATE_FORMAT(data,'%d/%m/%Y') as data2, cliente FROM tb_atendimentos WHERE codAtendimento LIKE '%".$codAtend."%' ORDER BY data2, codAtendimento";
				} else {
					if( isset($navio) AND strlen($navio) > 0 )
						$whereclause .= " navio LIKE '%".$navio."%' ";
						
					if( isset($datainicial) )
					{
						if( strlen($whereclause) > 0 )
							$whereclause .= " and ";
							
						$whereclause .= " data >= STR_TO_DATE('$datainicial', '%d/%m/%Y') ";
					}
				
					$query = "SELECT atendimento_id, codAtendimento, navio, DATE_FORMAT(data,'%d/%m/%Y') as data2, cliente FROM tb_atendimentos ";

					if( strlen($whereclause) > 0 )
						$query .= " where " . $whereclause;
						
					$query .= " ORDER BY data, atendimento_id";
				}

				$rows = $db->query($query);// or die(mysql_error());
				
				if( $rows == 0 ) {
					$pagenum == 0;
				} else {

					//if( isset($_POST["pagesize"]) AND ((int)$_POST["pagesize"]) > 0 )
					//	$page_rows = (int)$_POST["pagesize"];
					//else 
					//	$page_rows = 15;

					$last = ceil($rows/$page_rows);

					//$atendimento = $db->get_row($query);
				}
			} else {
				if( isset($_POST["last"]) )
					$last = (int)$_POST["last"];
				else 
					$last = "1";
			}

			//var_dump( $atendimento );
			//print( "query: " ); print_r( $query);
		?>
	
		<div role="main" class="container" align="center" style="padding:5px">
			<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" >

			<?php
				if( $pagenum == 0 ) {
					$pagenum = -1;
				} 
			?>
			<div class="form-horizontal" style="display:inline" align="right">
			<label for="pagesize">page size: </label>
			<input type="text" size="3" id="pagesize" name="pagesize" value="<?php echo $page_rows; ?>"/>
			<label for="datainicial">Initial Date: </label>
			<input type="text" id="datainicial" name="datainicial" size="9" maxlength="10" placeholder="dd/mm/yyyy" value="<?php echo $datainicial; ?>"/>
			<label for="atendId"> Atend.Id: </label>
			<input type="text" name="atendId" id="atendId" align="right" size="6" value="<?php echo $atendId; ?>"/>
			<label for="codAtend"> Cod.Atend: </label>
			<input type="text" name="codAtend" id="codAtend" size="9" align="right" value="<?php echo $codAtend; ?>"/>
			<label for="navio"> Navio: </label>
			<input type="text" name="navio" id="navio" size="10" value="<?php echo $navio; ?>"/>		
			<input type="submit" name="buscar" id="buscar" value="OK" />		
			<?php
					if( $pagenum > 0 ) {
						if( isset($_POST["btn_first"]) )
							$pagenum = 1;
						else if( isset($_POST["btn_last"]) )
							$pagenum = $last;
						else if( isset($_POST["btn_previous"]) AND $pagenum > 1 )
							$pagenum = $pagenum - 1;
						else if( isset($_POST["btn_next"]) && $pagenum < $last)
							$pagenum = $pagenum + 1;

						if ($pagenum > 1) 
							echo '<input type="submit" name="btn_first" value="<<"/> <input type="submit" name="btn_previous" value="<"/>';

						echo $pagenum.'/'.$last.' ';

						if ($pagenum < $last) 
							echo '<input type="submit" name="btn_next" value=">"/> <input type="submit" name="btn_last" value=">>"/>';

						echo '<p>';
	
						$max = 'limit ' .($pagenum - 1) * $page_rows .','.$page_rows; 
					} else {
						$max = $page_rows;
					}

					if( isset($query) ) {
						$atendimento = $db->get_results( $query.' '.$max );
					} else {
						$query = "";
					}
	
					$numRecords = 0;
					if( isset($atendimento) ) {
						$numRecords = count($atendimento);
					}
					
					echo '</div>';

				if( $numRecords > 0 ) {
					echo '<div>';
					echo '<table>';
					echo '<tr>';
					echo '<th><strong>Data</strong></th>';
					echo '<th><strong>Id Atendimento</strong></th>';
					echo '<th><strong>Cod Atendimento</strong></th>';
					echo '<th><strong>Navio</strong></th>';
					echo '<th><strong></strong></th>';
					echo '<th><strong>Editar</strong></th>';
					echo '<th><strong>Apagar</strong></th>';
					echo '</tr>';
					
					$i = 0;

					while( $i<$numRecords ) {
						echo '<tr>';
						echo '<td align="center" >'.$atendimento[$i]->data2.'</td>';
						echo '<td align="center" >'.$atendimento[$i]->atendimento_id.'</td>';
						echo '<td align="center" >'.$atendimento[$i]->codAtendimento.'</td>';
						//echo '<td align="left" colspan="3" >'.$atendimento[$i]->navio.'</td>';
						echo '<td align="left">'.$atendimento[$i]->navio.'</td>';

						if( $atendimento[$i]->cliente ) {
							echo '<td align="left">'.$atendimento[$i]->cliente.'</td>';
						} else {
							echo '<td align="center">';

							$filelist = glob('planos/plano_de_carga'.$atendimento[$i]->atendimento_id.'.*' );
							if( count($filelist) > 0 ) {
								foreach( $filelist as $fileitem ) {
									$filename = basename( $fileitem );
									//echo "<button type=\"button\" onclick=\"openModalWindow('.$fileitem.')\">".$matches[count($matches)-1]."</button>";
									if( preg_match('/\.([pP][dD][fF])$/', $fileitem, $matches) === 1 ) {
										//echo "<button type=\"button\" onclick=\"openModalWindow('.$fileitem.')\">".$matches[count($matches)-1]."</button>";
										echo "<button type=\"button\" onclick=\"openModalWindow('".$fileitem."?q=".microtime(true)."')\">".$matches[count($matches)-1]."</button>";
										//$ln = './api/planos.php?mode=raw&id='.$atendimento[$i]->atendimento_id.'&ext='.$matches[count($matches)-1];
										//echo "<button type=\"button\" onclick=\"openModalWindow('".$ln."')\">".$matches[count($matches)-1]."</button>";
									} else if( preg_match('/\.([xX][lL][sS].*)$/', $fileitem, $matches) === 1 ) {
										$ln = './api/planos.php?mode=raw&id='.$atendimento[$i]->atendimento_id.'&ext='.$matches[count($matches)-1];
										echo "<button type=\"button\" onclick=\"location.href='".$ln."'\">".$matches[count($matches)-1]."</button>";
										//echo "<button type=\"button\" onclick=\"location.href='".$fileitem."'\">".$matches[count($matches)-1]."</button>";
										//echo "<button type=\"button\" onclick=\"window.open('".$fileitem."')\">".$matches[count($matches)-1]."</button>";
										//echo "<button type=\"button\" onclick=\"window.open('".$fileitem."?q=".microtime(true)."')\">".$matches[count($matches)-1]."</button>";
										//echo "<button type=\"button\" onclick=\"openModalWindow('./api/showexcel.php?mode=table&filename=".$atendimento[$i]->atendimento_id.".".$matches[count($matches)-1]."')\">".$matches[count($matches)-1]."</button>";
									}
								}										
							}

							echo '</td>';
						}

						//$win2Open = 'planos/plano_de_carga'.$atendimento[$i]->atendimento_id;
						//$fileCount = count(glob($win2Open.".*"));
						//echo '<td align="center">'.(($fileCount==0)?"N":"S(".$fileCount.")").'</td>';

						//echo '<td align="center"><a href="atendimentos_gerenciar.php?atendimento_id='.$atendimento[$i]->atendimento_id.'"><p><u>Editar</u></p></a></td>';
						//echo '<td align="center"><p onclick="window.open('."'atendimentos_gerenciar.php?atendimento_id=".$atendimento[$i]->atendimento_id."'".')"><u>Editar</u></td>';
						//echo '<td align="center"><p onclick="openModalWindow('."'atendimentos_gerenciar.php?atendimento_id=".$atendimento[$i]->atendimento_id."'".')"><u>Editar</u></td>';
						echo '<td align="center"><p onclick="openModalWindow('."'atendimentos_gerenciar_api.php?edit&id=".$atendimento[$i]->atendimento_id."'".')"><u>Editar</u></td>';
						echo '<td align="center"><p onclick="window.open('."'";
						echo 'atendimentos_remover.php?atendimento_id='.$atendimento[$i]->atendimento_id;
						echo "')".'"><u>Apagar</u></td>';

						//echo '<td align="center"><p onclick="openModalWindow('."'atendimentos_gerenciar_v2.php?atendimento_id=".$atendimento[$i]->atendimento_id."'".')"><u>Editar Vesão antiga</u></td>';
						echo '</tr>';

						$i = $i + 1; 
					}

					echo '</table>';
					echo '</div>';
				}
					
				echo '<input type="hidden" name="query" value="'.$query.'" />';
				echo '<input type="hidden" name="last" value="'.$last.'" />';
				echo '<input type="hidden" name="page_rows" value="'.$page_rows.'" />';
				echo '<input type="hidden" name="pagenum" value="'.$pagenum.'"/>';
			echo '</form>';
		?>
		</div>
		<?php include_once("script.html"); ?>

	</body>
</html>
