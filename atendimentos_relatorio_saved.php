<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=0; include("auth.php"); ?>
<?php 
	echo setlocale(LC_TIME,"ptb");

	if( isset($_GET["visualizar"]) || isset($_GET["geraexcel"]) )
		get_GET_array( array( 'data', 'data2', 'produtos_id', 'terminais_id', 'difmenor', 'difmaior', 'visualizar', 'geraexcel', 'msg' ) );
	else
		get_POST_array( array( 'data', 'data2', 'produtos_id', 'terminais_id', 'difmenor', 'difmaior', 'visualizar', 'geraexcel', 'msg' ) );
	
	if( !isset($data) || strlen($data) == 0 )
		$data = "01/01/".date("Y");
	
	$qdata=substr($data,6,4).'-'.substr($data,3,2).'-'.substr($data,0,2);

	if( !isset($data2) || strlen($data2) == 0 )
		$data2 = date("d/m/Y");

	$qdata2=substr($data2,6,4).'-'.substr($data2,3,2).'-'.substr($data2,0,2);

	$m = array(1 =>"Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"); 

	if( isset($geraexcel) ) {
		//$url = $_SERVER["SERVER_NAME"].$_SERVER["CONTEXT_PREFIX"]."/api/atendimentos.php?excel=s";
		$url = $_SERVER["SERVER_HOST"].$_SERVER["CONTEXT_PREFIX"]."/atendimentos/api/atendimentos.php?excel=s";

		$url.="&d1=".$qdata;
		$url.="&d2=".$qdata2;

		if( isset($difmenor) && strlen($difmenor) > 0) 
			$url.="&falta=".$difmenor;

		if( isset($difmaior) && strlen($difmaior) >0 ) 
			$url.="&excesso=".$difmaior;

		if( isset($produtos_id) ) {
			$url.="&prods=";

			$first = TRUE;
			foreach ($produtos_id as $id) 
			{
				if($first==FALSE)
					$url.=",";
				$url.=$id;
				$first=FALSE;
			}
		}

		if( isset($terminais_id) ) { 
			$url.="&terms=";

			$first = TRUE;
			foreach ($terminais_id as $id) 
			{
				if($first==FALSE)
					$url.=",";
				$url.=$id;				
				$first=FALSE;
			}
		}
		
		header('Location: ' . $url);
		

		//$fp = fopen('php://memory','wb');
		//
		//$ch = curl_init();
		//curl_setopt($ch, CURLOPT_URL, $url );
		//curl_setopt($ch, CURLOPT_HEADER, 0);
		//curl_setopt($ch, CURLOPT_FILE, $fp);
		//curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
		//curl_exec($ch);
		//curl_close($ch);
		//
		//$size = ftell($fp);
		//
		//header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		//header('Content-Disposition: attachment; filename="atendimentos.xlsx"');
		//header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T' , time() ));
		//header('Content-Length: '.$size);
		//
		//while( ob_get_level() ) {
		//	ob_end_clean();
		//}
		//fseek($fp,0);
		//fpassthru( $fp );
		//
		//fclose($fp);
		//
		//header('Location: ' . $_SERVER['HTTP_REFERER']);

		die();
	}

	if( isset($visualizar) ) {

		$sql="SELECT  DISTINCT ate.atendimento_id, codAtendimento, data, navio, balanca, arqueacao, comando_navio, perito_receita, outras_partes1, outras_partes1_id, outras_partes2, outras_partes2_id, outras_partes3, outras_partes3_id, excesso, falta, diferenca FROM tb_atendimentos ate LEFT JOIN tb_atendimentos_produtos prod ON ( ate.atendimento_id = prod.atendimento_id ) LEFT JOIN tb_atendimentos_terminais term ON ( ate.atendimento_id = term.atendimento_id ) WHERE";

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
	
		if(isset($produtos_id)) 
		{
			$p1=1;
			foreach ($produtos_id as $selectedOption) 
			{
				if($p1==1)
					$sql.=" AND (prod.produto_id='$selectedOption'";	
				else
					$sql.=" OR prod.produto_id='$selectedOption'";		
				$p1++;
			}
			$sql.=")";
		}

		if(isset($terminais_id)) 
		{
			$t1=1;
			foreach ($terminais_id as $selectedOption) 
			{
				if($t1==1)
					$sql.=" AND (term.terminal_id='$selectedOption'";	
				else
					$sql.=" OR term.terminal_id='$selectedOption'";		
				$t1++;
			}
			$sql.=")";
		}
		
		$sql.= " ORDER BY ate.data, ate.atendimento_id";

		//var_dump( $sql );

		$atendimento = $db->get_results("{$sql}");
		$total_navios = 0;
		if( $atendimento != null ) {
			$total_navios=count($atendimento);
		}
	}

	//var_dump( $_POST, $_GET, "data:$data, data2:$data2, qdata:$qdata, qdata2:$qdata2, produtos_id:$produtos_id, terminais_id:$produtos_id, difmenor:$difmenor, difmaior:$difmaior, visualizar:$visualizar, geraexcel:$geraexcel, sql:$sql"); 
	//die();
?>
<!doctype html>
<html>
<head>
		<title>Standard Brazil - Administrative area</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<link href="css/bootstrap.min.css" type="text/css" rel="stylesheet" >
		<link href="css/padrao.css" type="text/css" rel="stylesheet"/>
	</head>

	<body>
		<?php include("menu.html"); ?>

		<main role="main" class="container-full">
			<div align="center">
				<span>
					<?php if (isset($msg)) echo "<script language=\"JavaScript\"> window.alert(\"$msg\");</script>" ?>
				</span>

				<?php if( !isset($visualizar)) { ?>
					
				<form action="<?php echo $PHP_SELF ?>" method="post" enctype="multipart/form-data">
					<table width="50%" border="0" cellspacing="0" cellpadding="7">
						<tr>
							<td align="right"><label for="data">Data inicial:</label></td>
							<td align="left"><input name="data" type="text" id="data" size="9" maxlength="15" class="required" placeholder="dd/mm/yyyy" value="<?php echo $data ?>"/></td>
						</tr>
						<tr>
							<td align="right"><label for="data2">Data final:</label></td>
							<td align="left"><input name="data2" type="text" id="data2" size="9" maxlength="15" class="required" placeholder="dd/mm/yyyy" value="<?php echo $data2 ?>"/></td>
						</tr>
						<tr>
							<td align="right">Produtos:</td>
							<td align="left">
								<?php 
								$prods = $db->get_results("SELECT produto_id, nome FROM tb_produtos ORDER BY nome");
								foreach( $prods as $prod) 
									echo '<input type="checkbox" name="produtos_id[]" value="'.$prod->produto_id.'"'.">&#32;".$prod->nome."&#32;&#124;&#32;";
								?>
							</td>
						</tr>
						<tr>
							<td align="right">Terminais:</td>
							<td align="left">
								<?php 
								$terms = $db->get_results("SELECT terminal_id, nome FROM tb_terminais ORDER BY nome");
								foreach($terms as $term) 
									echo '<input type="checkbox" name="terminais_id[]" value="'.$term->terminal_id.'"'.$outstr.">&#32;".$term->nome."&#32;&#124;&#32;";
								?>
							</td>
						</tr>
						<tr>
							<td align="right"><label for="difmenor">% falta:</label></td>
							<td align="left"><input name="difmenor" type="text" id="difmenor" size="5" maxlength="5" placeholder="0.00"/></td>
						</tr>
						<tr>
							<td align="right"><label for="difmaior">% excesso:</label></td>
							<td align="left"><input name="difmaior" type="text" id="difmaior" size="5" maxlength="5" placeholder="0.00"/></td>
						</tr>
						<tr>
							<td align="right"><input name="visualizar" type="submit" id="visualizar" value="Visualizar Relatório"/></td>
							<td align="left"><input name="geraexcel" type="submit" id="geraexcel" value="Gerar arquivo Excel"/></td>
						</tr>		
					</table>
				</form>

				<?php } else { ?>


				<table border="1" class="fullwidth">
					<THEAD> 
						<tr class="fullwidth">
							<td colspan="16" bgcolor="#00B050" class="fullwidth titulo">
								<?php echo "Atendimentos efetuados entre $data e $data2"; ?>
							</td>
						</tr>
						<tr>
							<td colspan="3" rowspan="2" align="center" bgcolor="#99CCFF" ><strong>DATA</strong></td>
							<td rowspan="2" align="center" bgcolor="#00FFFF" ><strong>NAVIO</strong></td>
							<td rowspan="2" align="center" bgcolor="#00FFFF" ><strong>PRODUTO</strong></td>
							<td rowspan="2" align="center" bgcolor="#99CCFF" ><strong>CARREGADO<br />POR BALANÇA</strong></td>
							<td rowspan="2" align="center" bgcolor="#00FFFF" ><strong>CARREGADO POR<br />ARQUEAÇÃO</strong></td>
							<td colspan="2" align="center" bgcolor="#99CCFF" ><strong>DIFERENÇA &#8482;</strong></td>
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
					<TBODY>
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
							$total_outras_partes2+=$atendimento->outras_partes2;
							$total_outras_partes3+=$atendimento->outras_partes3;

							//$fileList = glob("planos/plano_de_carga".$atendimento->atendimento_id.".*");
							//$fileId = sha1($fileList);
							//
							//unset($filename);
							//unset($filetype);
							//unset($pdffilename);
							//
							//foreach( $fileList as $fn ) {
							//	if( preg_match('/(\.[xX][lL][sS].*)$/', $fn, $matches) === 1 ) {
							//		$filename = $fn;
							//		$filetype = strtolower($matches[0]);
							//	}
							//
							//	if( preg_match('/(\.[pP][dD][fF])$/', $fn, $matches) === 1 ) {
							//		$pdffilename = $fn;
							//		$pdffiletype = strtolower($matches[0]);
							//	}
							//}
							//
							//if( isset($filename) ) {
							//	//echo '<tr id="'.$fileId.'" onclick="openModalWindow('."'../api/showexcel.php?mode=table&filename=../atendimentosv2/".$filename."'".')" >';
							//	echo '<tr id="'.$fileId.'" onclick="openModalWindow('."'../api/showexcel.php?mode=table&filename=".$atendimento->atendimento_id.$filetype."')".'">';
							//}else 
							//if( isset($pdffilename) ) {
							//	echo '<tr id="'.$fileId.'" onclick="openModalWindow('."'".$pdffilename.'?'.$fileId."'".')" >';
							//} else {
							//	echo '<tr id="'.$fileId.'">';
							//}
						
							echo '<tr id="'.$fileId.'" onclick="openModalWindow('."'./atendimentos_gerenciar_api.php?edit=false&atdid=".$atendimento->atendimento_id."'".')" >';

							?>
							<td align="center" style="border-right:none"><?php echo strftime( '%e', strtotime(date("$atendimento->data"))); ?></td>
							<td align="center" style="border-right:none"><?php echo strftime( '%b', strtotime(date("$atendimento->data"))); ?></td>
							<td align="center"><?php echo strftime( '%Y', strtotime(date("$atendimento->data"))); ?></td>
							<td align="center"><?php echo $atendimento->navio ?></td>
							<td align="center">
								<?php 
									$produto = $db->get_results("SELECT tb_produtos.nome FROM tb_produtos, tb_atendimentos_produtos WHERE tb_atendimentos_produtos.produto_id = tb_produtos.produto_id AND tb_atendimentos_produtos.atendimento_id=$atendimento->atendimento_id");
									$c = 0;
									if( $produto != null ) {
										$c=count($produto);
									}
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
									$c = 0;
									if( $terminal != null ) {
										$c=count($terminal);
									}
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
						<?php 
						} ?>
						<tr>
							<td colspan="4" align="center" bgcolor="#99CCFF">&nbsp;</td>
							<td align="center" bgcolor="#99CCFF"><strong><?php echo $total_navios." NAVIOS"; ?></strong></td>
							<td align="right" bgcolor="#99CCFF"><strong><?php echo number_format($total_balanca, 3, ',', '.'); ?></strong></td>
							<td align="right" bgcolor="#99CCFF"><strong><?php echo number_format($total_arqueacao, 3, ',', '.'); ?></strong></td>
							<td align="center" bgcolor="#00B050"><strong><?php echo number_format ($total_excesso, 3, ',', '.'); ?></strong></td>
							<td align="center" bgcolor="#FFFF00"><strong><?php echo number_format ($total_falta, 3, ',', '.'); ?></strong></td>
							<td align="center" <?php if($total_diferenca >0) $bg="#00B050"; else $bg="red"; ?>bgcolor="<?php echo $bg?>"><strong><?php echo number_format (($total_excesso-$total_falta), 3, ',', '.'); ?></strong></td>
							<td align="center" bgcolor="<?php echo $bg?>"><strong><?php if( $total_balanca > 0) echo round(((($total_excesso-$total_falta)*100)/$total_balanca), 2); //$total_diferenca ?>%</strong></td>
							<td align="center"><strong><?php echo number_format($total_comando_navio, 3, ',', '.'); ?></strong></td>
							<td align="center"><strong><?php echo number_format($total_perito_receita, 3, ',', '.'); ?></strong></td>
							<td align="center"><strong><?php if($total_outras_partes1 >0) echo number_format($total_outras_partes1, 3, ',', '.'); ?> </strong></td>
							<td align="center"><strong><?php if($total_outras_partes2 >0) echo number_format($total_outras_partes2, 3, ',', '.'); ?> </strong></td>
							<td align="center"><strong><?php if($total_outras_partes3 >0) echo number_format($total_outras_partes3, 3, ',', '.'); ?> </strong></td>
						</tr>
					</TBODY>
				</table>
				<br/>
				<br/>

				<?php } ?>

			</div>
		</main>
		<?php include_once("script.html"); ?>
	</body>
</html>
