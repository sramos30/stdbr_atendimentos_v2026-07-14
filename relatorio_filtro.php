<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=0; include("auth.php"); ?>
<?php

	get_POST_array( array( 'data', 'data2', 'produtos_id', 'terminais_id', 'difmenor', 'difmaior' ) );
	
	echo setlocale(LC_TIME,"US");

	if( !isset($data) || strlen($data) == 0 )
		$data = "01/01/".date("Y");
	
	$qdata=date2mysql($data);

	if( !isset($data2) || strlen($data2) == 0 )
		$data2 = "31/".date("m/Y");

	$qdata2=date2mysql($data2);

	//var_dump( $_POST, "data:$data, data2:$data2, qdata:$qdata, qdata2:$qdata2"); die();

	require_once("gera_excel.php");
?>

<!doctype html>
<html lang="en">
	<head>
		<title>Standard Brazil - Administrative area</title>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
		<link href="padrao.css<?php echo '?v='.microtime(true); ?>" rel="stylesheet" type="text/css" />
	</head>

	<body>
		<?php include("menu.html"); ?>
		<div>
			<?php
				$sql="SELECT * FROM tb_atendimentos ate LEFT JOIN tb_atendimentos_produtos prod ON ( ate.atendimento_id = prod.atendimento_id ) LEFT JOIN tb_atendimentos_terminais term ON ( ate.atendimento_id = term.atendimento_id ) WHERE";
	
				$sql.=" data BETWEEN '$qdata' AND '$qdata2'";
				
				if( isset($difmenor) && strlen($difmenor) > 0 )
					$sql.=" AND (diferenca <= '-$difmenor'";
					
				if( isset($difmaior) && strlen($difmaior) > 0 ) 
				{
					if( isset($difmenor) && strlen($difmenor) > 0 )
						$sql.= " OR diferenca >= '$difmaior') ";
					else
						$sql.= " AND (diferenca >= '$difmaior') ";
				}else if(isset($difmenor) && strlen($difmenor) > 0) {
					$sql.= ") ";
				}
			
				if(isset($_POST['produtos_id'])) 
				{
					$p1=1;
					foreach ($_POST['produtos_id'] as $selectedOption) 
					{
						if($p1==1)
							$sql.=" AND (prod.produto_id='$selectedOption'";	
						else
							$sql.=" OR prod.produto_id='$selectedOption'";		
						$p1++;
					}
					$sql.=")";
				}
	
				if(isset($_POST['terminais_id'])) 
				{
					$t1=1;
					foreach ($_POST['terminais_id'] as $selectedOption) 
					{
						if($t1==1)
							$sql.=" AND (term.terminal_id='$selectedOption'";	
						else
							$sql.=" OR term.terminal_id='$selectedOption'";		
						$t1++;
					}
					$sql.=")";
				}
	
				$sql.= " GROUP BY ate.atendimento_id ORDER BY ate.data, ate.atendimento_id";

				//var_dump( $sql );

				setlocale(LC_ALL, 'pt_BR');
				
				$m = array(1 =>"Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"); 

				$atendimento = $db->get_results("$sql");
				$total_navios=count($atendimento);

			?>

			<table border="1" class="fullwidth">
				<THEAD> 
					<tr class="fullwidth">
						<td colspan="16" bgcolor="#00B050" class="fullwidth titulo"><?php echo "Atendimentos efetuados entre $data e $data2"; ?></td>
					</tr>
					<tr>
						<td colspan="3" rowspan="2" align="center" bgcolor="#99CCFF" ><strong>DATA</strong></td>
						<td rowspan="2" align="center" bgcolor="#00FFFF" ><strong>NAVIO</strong></td>
						<td rowspan="2" align="center" bgcolor="#00FFFF" ><strong>PRODUTO</strong></td>
						<td rowspan="2" align="center" bgcolor="#99CCFF" ><strong>CARREGADO<br />POR BALANÇA</strong></td>
						<td rowspan="2" align="center" bgcolor="#00FFFF" ><strong>CARREGADO POR<br />ARQUEAÇÃO</strong></td>
						<td colspan="2" align="center" bgcolor="#99CCFF" "><strong>DIFERENÇA &#8482;</strong></td>
						<td rowspan="2" align="center" ><strong>TERMINAIS</strong></td>
						<td rowspan="2" align="center" bgcolor="#00CCFF" ><strong>PERC</strong></td>
						<td rowspan="2" align="center" ><strong>COMANDO NAVIO</strong></td>
						<td rowspan="2" align="center" ><strong>PERITO RECEITA</strong></td>
						<td rowspan="2" align="center" ><strong>OUTRAS PARTES 1</strong></td>
						<td rowspan="2" align="center" ><strong>OUTRAS PARTES 2</strong></td>
						<td rowspan="2" align="center" ><strong>OUTRAS PARTES 3</strong></td>
					</tr>
					<tr>
						<td align="center" bgcolor="#CCFFCC"><strong>EXCESSO</strong></td>
						<td align="center" bgcolor="#FFFF00"><strong>FALTA</strong></td>
					</tr>
				</THEAD>
				<?php
					unset($total_balanca);
					unset($total_arqueacao);
					unset($total_excesso);
					unset($total_falta);
					unset($total_diferenca);
					unset($total_comando_navio);
					unset($total_perito_receita);
					unset($total_outras_partes1);
					unset($total_outras_partes2);
					unset($total_outras_partes3);

					foreach($atendimento as $atendimento) 
					{	
						$total_balanca+=$atendimento->balanca;
						$total_arqueacao+=$atendimento->arqueacao;
						$total_excesso+=$atendimento->excesso;
						$total_falta+=$atendimento->falta;
						$total_diferenca+=$atendimento->diferenca;
						$total_comando_navio+=$atendimento->comando_navio;
						$total_perito_receita+=$atendimento->perito_receita;
						$total_outras_partes1+=$atendimento->outras_partes1;
						$total_outras_partes2+=$atendimento->outras_parte2;
						$total_outras_partes3+=$atendimento->outras_parte3;
				?>
				<TBODY>
					<?php 
						if(file_exists("planos/plano_de_carga".$atendimento->atendimento_id.".pdf")) 
						{ 
							$variavel=microtime(true);
					?>
					<tr class="lovelyrow" onclick="window.open('<?php echo  "planos/plano_de_carga".$atendimento->atendimento_id.".pdf?q=$variavel"; ?>')">
					<?php } else { ?>
					<tr>
					<?php } ?>

						<td align="center" style="border-right:none"><?php echo strftime( '%e', strtotime(date("$atendimento->data"))); ?></td>
						<td align="center" style="border-right:none"><?php echo strftime( '%b', strtotime(date("$atendimento->data"))); ?></td>
						<td align="center"><?php echo strftime( '%Y', strtotime(date("$atendimento->data"))); ?></td>
						<td align="center"><?php echo $atendimento->navio ?></td>
						<td align="center">
							<?php 
								$produto = $db->get_results("SELECT tb_produtos.nome FROM tb_produtos, tb_atendimentos_produtos WHERE tb_atendimentos_produtos.produto_id = tb_produtos.produto_id AND tb_atendimentos_produtos.atendimento_id=$atendimento->atendimento_id");
								$c=count($produto);
								$b=1;
								foreach($produto as $produto) 
								{
									echo $produto->nome;
									
									if($b < $c) echo "/";
										$b++;
								}
							?>
						</td>
						<td align="right"><?php echo number_format($atendimento->balanca, 3, ',', '.');  ?></td>
						<td align="right"><?php echo number_format($atendimento->arqueacao, 3, ',', '.');  ?></td>
						<td align="center" bgcolor="#D8E4BC"><?php if($atendimento->excesso >0) echo number_format ($atendimento->excesso, 3, ',', '.');  ?></td>
						<td align="center" bgcolor="#FFFF00"><?php if($atendimento->falta >0) echo number_format ($atendimento->falta, 3, ',', '.'); ?></td>
						<td align="center" class="teminais">
							<?php 
								$terminal = $db->get_results("SELECT tb_terminais.nome FROM tb_terminais, tb_atendimentos_terminais WHERE tb_atendimentos_terminais.terminal_id = tb_terminais.terminal_id AND tb_atendimentos_terminais.atendimento_id=$atendimento->atendimento_id");
								$c=count($terminal);
								$b=1;
								foreach($terminal as $terminal) 
								{
									echo $terminal->nome;
									if($b < $c) echo "/";
										$b++;
								}
							?>
						</td>
						<td align="center" nowrap="nowrap"><?php echo $atendimento->diferenca ?>%</td>
						<td align="center"><?php echo number_format($atendimento->comando_navio, 3, ',', '.'); ?></td>
						<td align="center"><?php echo number_format($atendimento->perito_receita, 3, ',', '.'); ?></td>
						<td align="center"><?php if($atendimento->outras_partes1 >0) { echo number_format($atendimento->outras_partes1, 3, ',', '.'); echo " ($atendimento->outras_partes1_id)"; } ?></td>
						<td align="center"><?php if($atendimento->outras_partes2 >0) { echo number_format($atendimento->outras_partes2, 3, ',', '.'); echo " ($atendimento->outras_partes2_id)"; } ?></td>
						<td align="center"><?php if($atendimento->outras_partes3 >0) { echo number_format($atendimento->outras_partes3, 3, ',', '.'); echo " ($atendimento->outras_partes3_id)"; } ?></td>
					</tr>
					<?php } ?>
					<tr>
						<td colspan="4" align="center" bgcolor="#99CCFF">&nbsp;</td>
						<td align="center" bgcolor="#99CCFF"><strong><?php echo $total_navios." NAVIOS"; ?></strong></td>
						<td align="right" bgcolor="#99CCFF"><strong><?php echo number_format($total_balanca, 3, ',', '.'); ?></strong></td>
						<td align="right" bgcolor="#99CCFF"><strong><?php echo number_format($total_arqueacao, 3, ',', '.'); ?></strong></td>
						<td align="center" bgcolor="#00B050"><strong><?php echo number_format ($total_excesso, 3, ',', '.'); ?></strong></td>
						<td align="center" bgcolor="#FFFF00"><strong><?php echo number_format ($total_falta, 3, ',', '.'); ?></strong></td>
						<td align="center" <?php if($total_diferenca >0) $bg="#00B050"; else $bg="red"; ?>bgcolor="<?php echo $bg?>"><strong><?php echo number_format (($total_excesso-$total_falta), 3, ',', '.'); ?></strong></td>
						<td align="center" bgcolor="<?php echo $bg?>"><strong><?php echo round(((($total_excesso-$total_falta)*100)/$total_balanca), 2); //$total_diferenca ?>%</strong></td>
						<td align="center"><strong><?php echo number_format($total_comando_navio, 3, ',', '.'); ?></strong></td>
						<td align="center"><strong><?php echo number_format($total_perito_receita, 3, ',', '.'); ?></strong></td>
						<td align="center"><strong><?php if($atendimento->total_outras_partes1 >0) echo number_format($total_outras_partes1, 3, ',', '.'); ?> </strong></td>
						<td align="center"><strong><?php if($atendimento->total_outras_partes2 >0) echo number_format($total_outras_partes2, 3, ',', '.'); ?> </strong></td>
						<td align="center"><strong><?php if($atendimento->total_outras_partes3 >0) echo number_format($total_outras_partes3, 3, ',', '.'); ?> </strong></td>
					</tr>
				</TBODY>
			</table>
			<br />
			<br />
		</div>

		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
	</body>
</html>
