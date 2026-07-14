<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=1; include("auth.php"); ?>
<?php

	//var_dump( $_POST );
	//var_dump( $_GET );
	//print_r($_GET);

	echo setlocale(LC_TIME,"US");
	
	$data_atu = getdate()['mday'].'/'.getdate()['mon'].'/'.getdate()['year'];
	$atd_data = NULL;

	if( isset($_GET['atendimento_id']) ) {
		$atendimento_id = intval($_GET['atendimento_id']);

		$file =  "http://".$_SERVER["SERVER_NAME"].$_SERVER["CONTEXT_PREFIX"]."/api/atendimentos.php?id=".$atendimento_id;
		//var_dump($file);
		$atd_data = file_get_contents($file);
		//$data = mb_substr($data, strpos($data, '{'));
		//$data = mb_substr($data, 0, -1);
		//$atd_data = json_decode($data, true);
		//print_r($atd_data);
		//var_dump($atd_data);
	}

	unset( $msg );

	if( isset($_GET["addnew"]) )
		$msg = $_GET["addnew"];

	if( !isset($msg) ) {
		get_POST_array( array( 'salvar', 'adicionar' ) );
		
		if (($salvar != NULL) || ($adicionar != NULL)) 
		{
			//var_dump( "if( salvar or adicionar )->", $_POST );

			$atendimento_id = intval($_POST['atendimento_id']);

			get_POST_array( array('data', 'codAtendimento', 'navio', 'arqueacao', 'balanca', 'comando_navio', 'perito_receita', 'outras_partes1', 'outras_partes1_id', 
				'outras_partes2', 'outras_partes2_id', 'outras_partes3', 'outras_partes3_id', 'produtos_id', 'terminais_id', 'files2Delete', 
				'poroesProduto', 'poroesTerminais', 'cubagem', 'fatEstiva', 'condPorao' ) );
			
			if( isset($files2Delete) ) {
				foreach( $files2Delete as $filetype ) {
					unlink( "planos/plano_de_carga".$atendimento_id.".".$filetype );
				}
			}
			
			//var_dump( $_POST );
			//var_dump( $_GET );
			
			if($_FILES['planodecarga']['name'] != '' ) {
				$newFileName = "planos/plano_de_carga".$atendimento_id.strtolower(substr($_FILES['planodecarga']['name'], strrpos($_FILES['planodecarga']['name'], ".")) );
				move_uploaded_file($_FILES['planodecarga']['tmp_name'], $newFileName );
			}

			if( !empty($data) )
				$data=date2mysql($data);
			
			$resultado=($arqueacao-$balanca);
			
			$r2=number_format(abs($resultado), 3, '.', ',');

			if($resultado >= 0) {
				$excesso= "$r2";
			} else {
				$falta= "$r2";					
			}

			$diferenca= round(($resultado / $balanca) * 100, 2); 

			if( $adicionar!=NULL ) {

				$sql = "INSERT INTO tb_atendimentos (data, codAtendimento, navio, balanca, arqueacao, comando_navio, perito_receita, outras_partes1, outras_partes1_id";
				$sql .= ", outras_partes2, outras_partes2_id, outras_partes3, outras_partes3_id, excesso, falta, diferenca)";
				$sql .= " VALUES ('".$data."', '".$codAtendimento."', '".$navio."', '".$balanca."', '".$arqueacao."', '".$comando_navio."', '".$perito_receita."'";
				$sql .= ", '".$outras_partes1."', '".$outras_partes1_id."', '".$outras_partes2."', '".$outras_partes2_id."', '".$outras_partes3."'";
				$sql .= ", '".$outras_partes3_id."', '".$excesso."', '".$falta."', '".$diferenca."')";
				
				$db->query( $sql );
				
				$msg = "Atendimento ".$atendimento_id." adicionado com sucesso!";

			} else {

				$sql = "UPDATE tb_atendimentos SET data='".$data."', codAtendimento='".$codAtendimento."', navio='".$navio."', balanca='".$balanca."', arqueacao='".$arqueacao."'";
				$sql .= ", comando_navio='".$comando_navio."', perito_receita='".$perito_receita."', outras_partes1='".$outras_partes1."'";
				$sql .= ", outras_partes1_id='".$outras_partes1_id."', outras_partes2='".$outras_partes2."', outras_partes2_id='".$outras_partes2_id."'";
				$sql .= ", outras_partes3='".$outras_partes3."', outras_partes3_id='".$outras_partes3_id."', excesso='".$excesso."', falta='".$falta."'";
				$sql .= ", diferenca='".$diferenca."' WHERE atendimento_id=".$atendimento_id;
				
				$db->query($sql);
		
				$msg = "Atendimento ".$atendimento_id." editado com sucesso!";
			}

			//var_dump( "Produtos & Terminais->", $produtos_id, $terminais_id); 


			// Atualiza os registros anteriores da tabela tb_atendimentos_produtos
			$db->query("DELETE FROM tb_atendimentos_produtos WHERE atendimento_id=$atendimento_id");
			
			foreach ($produtos_id as $selectedOption)
				$db->query("INSERT INTO tb_atendimentos_produtos (atendimento_id, produto_id) VALUES ('$atendimento_id', '$selectedOption')");
			
			$db->query("DELETE FROM tb_atendimentos_terminais WHERE atendimento_id=$atendimento_id");
			
			foreach ($terminais_id as $selectedOption2)
				$db->query("INSERT INTO tb_atendimentos_terminais (atendimento_id, terminal_id) VALUES ('$atendimento_id', '$selectedOption2')");
			

			// Atualiza os registros das tabelas tb_atendimentos_poroes e tb_atendimentos_poroes_terminais
			$terminais = $db->get_results("SELECT terminal_id FROM tb_terminais order by terminal_id");
			$arrTerminais = array();

			foreach( $terminais as $term ) {		
				array_push( $arrTerminais, $term->terminal_id );
			}

			//var_dump( $arrTerminais ); print_r("<br>");

			$db->query("DELETE FROM tb_atendimentos_poroes_terminais WHERE atendimento_id=$atendimento_id");
			
			$sql1 = "INSERT INTO tb_atendimentos_poroes_terminais (atendimento_id, porao, terminal_id, quantidade) VALUES (".$atendimento_id;

			for( $i=0, $t=0; $i<count($poroesTerminais); $i+=9, $t++) {
				for( $p=0; $p<9; $p++ ) {
					
					//print_r( 'i:'.$i.', t:'.$t.', p:'.$p.'<br>');
					//print_r( 'arrTerminais['.$t.']:'.$arrTerminais[$t] );
					//print_r( ', poroesTerminais['.$i+$p.']:'.$poroesTerminais[$i+$p]."<br>" );

					if( isset($poroesTerminais[$i+$p]) && $poroesTerminais[$i+$p] != "" ) {
						$sql2 = ", ".($p+1);
						$sql2 .= ", ".$arrTerminais[$t];
						$sql2 .= ", ".$poroesTerminais[$i+$p];
						$sql = $sql1.$sql2.")";
						//print_r( $sql.'<br>' );
						$db->query($sql);
					}
				}
			}

			$db->query("DELETE FROM tb_atendimentos_poroes WHERE atendimento_id=$atendimento_id");
			
			$sql1 = "INSERT INTO tb_atendimentos_poroes (atendimento_id, porao, produto_id, fatorestiva, cubagem, condicao) VALUES (".$atendimento_id;

			for( $p = 0; $p<9; $p++ ) {
				if( isset($poroesProduto) && isset($poroesProduto[$p]) && intval($poroesProduto[$p])>0 ) {
					$sql2  = ", ".($p+1);
					$sql2 .= ", ".$poroesProduto[$p];
					$sql2 .= ", ".$fatEstiva[$p];
					$sql2 .= ", ".$cubagem[$p];
					$sql2 .= ", '".$condPorao[$p]."'";
					$sql = $sql1.$sql2.")";
					//print_r($sql.'<br>');
					$db->query($sql);
				}
			}

			if( isset($_POST['adicionar']) )
				unset( $atendimento_id );
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
		<title>Standard Brazil - Administrative area</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<link href="css/bootstrap.min.css" type="text/css" rel="stylesheet" >
		<link href="css/padrao.css" type="text/css" rel="stylesheet"/>
	</head>
	<?php if (isset($msg) && strlen($msg)>0) echo "<script language=\"JavaScript\"> window.alert(\"".$msg."\");</script>"; ?>

	<body onload='document.form1.planodecarga.focus()'>
		<?php if( !isset($atendimento_id) ) include_once("menu.html"); ?>
		<main role="main" class="container">
			<form name="form1" action="<?php echo $PHP_SELF ?>" method="post" enctype="multipart/form-data">
				<table border="0" class="center-align">
					<tr>
						<td class="center-align" valign="top" bgcolor="#FFFFFF">
							<table border="0">
								<tr>
									<td colspan="2" class="center-align">
										<?php 

											if( isset($atd_data) ) {
												print_r( "<b>editar atendimento existente</b><br />");
												//var_dump($atd_data);	

											} else {
												print_r( "<b>novo atendimento</b><br />");
											}

											if( $atendimento_id > 0 ) {
												echo 'ATENDIMENTOS - EDITAR';
												$adicionar_atendimento = false;
												
												$atendimento = $db->get_row("SELECT *, DATE_FORMAT(data,'%d/%m/%Y') as data FROM tb_atendimentos WHERE atendimento_id=$atendimento_id"); 
												$data = $atendimento->data;
												
												if(count($atendimento) > 0) {
													$aux = $db->get_results("SELECT distinct terminal_id FROM tb_atendimentos_terminais WHERE atendimento_id=$atendimento_id order by terminal_id");
													$numTbTerminais = count($aux);
													
													$tbTerminais = array();
													foreach($aux as $aux) {
														array_push( $tbTerminais, $aux->terminal_id );
													}													
													
													$aux = $db->get_results("SELECT distinct produto_id FROM tb_atendimentos_produtos WHERE atendimento_id=$atendimento_id order by produto_id");
													$numTbProdutos = count($aux);

													$tbProdutos = array();
													foreach($aux as $aux) {
														array_push( $tbProdutos, $aux->produto_id );
													}
												}
											} else {

												echo 'ATENDIMENTOS - CRIAR NOVO';
												
												$data = $data_atu;
												$atendimento_id = intval($db->get_var( "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_NAME = 'tb_atendimentos'" ));
												
												$adicionar_atendimento = true;
											}

											$terminais = $db->get_results("SELECT terminal_id, nome FROM tb_terminais order by terminal_id");
											$numTerminais = count($terminais);

											$produtos = $db->get_results("SELECT produto_id, nome FROM tb_produtos order by produto_id");
											$numProdutos = count($produtos);
										?>
									</td>
								</tr>
								<tr>
									<td colspan="2"class="center-align">
										<b>Atendimento Id: <?php echo $atendimento_id; ?></b>
										<input name="atendimento_id" type="hidden" value="<?php echo $atendimento_id; ?>" />
									</td>
								</tr>
								<tr>
									<td class="right-align">Adicionar Plano de carga:</td>
									<td class="required left-align">
										<input name="planodecarga" type="file" id="planodecarga" size="30"/>
									</td>
								</tr>
								<tr>
									<td class="right-align">Cod.Atendimento:</td>
									<td class="left-align"><input name="codAtendimento" type="text" id="codAtendimento" value="<?php echo $atendimento->codAtendimento; ?>" size="15" maxlength="20"/></td>
								</tr>
								<tr>
									<td class="right-align">Data:</td>
									<td class="required left-align"><input name="data" class="data-imput" type="text" id="data" placeholder="dd/mm/yyyy" value="<?php echo $data; ?>" size="10" maxlength="10" /></td>
								</tr>
								<tr>
									<td class="right-align">Navio:</td>
									<td class="required left-align"><input name="navio" type="text" id="navio" value="<?php echo $atendimento->navio; ?>" size="30" maxlength="80" class="required"/></td>
								</tr>
								<tr>
									<td class="right-align">Carregado por balança:</td>
									<td class="required left-align"><input name="balanca" class="number-imput" type="text" id="balanca" value="<?php echo $atendimento->balanca; ?>" size="30" class="required"/></td>
								</tr>
								<tr>
									<td class="right-align">Carregado por arqueação:</td>
									<td class="required left-align"><input name="arqueacao" class="number-imput" type="text" id="arqueacao" value="<?php echo $atendimento->arqueacao; ?>" size="30" class="required"/></td>
								</tr>
								<tr>
									<td class="right-align">Comando Navio:</td>
									<td class="required left-align"><input name="comando_navio" class="number-imput" type="text" id="comando_navio" value="<?php echo $atendimento->comando_navio; ?>" size="30" /></td>
								</tr>
								<tr>
									<td class="right-align">Perito Receita:</td>
									<td class="required left-align"><input name="perito_receita" class="number-imput" type="text" id="perito_receita" value="<?php echo $atendimento->perito_receita; ?>" size="30" /></td>
								</tr>
								<tr>
									<td class="right-align">Outras Partes 1:</td>
									<td class="required left-align"><input name="outras_partes1" class="number-imput" type="text" id="outras_partes1" value="<?php echo $atendimento->outras_partes1; ?>" size="13" /> 
										<select name="outras_partes1_id" id="outras_partes1_id">
											<option value="Exportador" <?php if($atendimento->outras_partes1_id=='Exportador') echo 'selected'; ?> >Exportador</option>
											<option value="Armador" <?php if($atendimento->outras_partes1_id=='Armador') echo 'selected'; ?> >Armador</option>
											<option value="P&I Armador <?php if($atendimento->outras_partes1_id=='P&I Armador') echo 'selected'; ?> ">P&I Armador</option>
											<option value="Afretador" <?php if($atendimento->outras_partes1_id=='Afretador') echo 'selected'; ?> >Afretador</option>
											<option value="P&I Afretador" <?php if($atendimento->outras_partes1_id=='P&I Afretador') echo 'selected'; ?> >P&I Afretador</option>
											<option value="Comprador" <?php if($atendimento->outras_partes1_id=='Comprador') echo 'selected'; ?> >Comprador</option>
										</select>
									</td>
								</tr>
								<tr>
									<td class="right-align">Outras Partes 2:</td>
									<td class="required left-align"><input name="outras_partes2" class="number-imput" type="text" id="outras_partes2" value="<?php echo $atendimento->outras_partes2; ?>" size="13" />
										<select name="outras_partes2_id" id="outras_partes2_id">
											<option value="Exportador" <?php if($atendimento->outras_partes2_id=='Exportador') echo 'selected'; ?> >Exportador</option>
											<option value="Armador" <?php if($atendimento->outras_partes2_id=='Armador') echo 'selected'; ?> >Armador</option>
											<option value="P&I Armador <?php if($atendimento->outras_partes2_id=='P&I Armador') echo 'selected'; ?> ">P&I Armador</option>
											<option value="Afretador" <?php if($atendimento->outras_partes2_id=='Afretador') echo 'selected'; ?> >Afretador</option>
											<option value="P&I Afretador" <?php if($atendimento->outras_partes2_id=='P&I Afretador') echo 'selected'; ?> >P&I Afretador</option>
											<option value="Comprador" <?php if($atendimento->outras_partes2_id=='Comprador') echo 'selected'; ?> >Comprador</option>
										</select>
									</td>
								</tr>
								<tr>
									<td class="right-align">Outras Partes 3:</td>
									<td class="required left-align">
										<input name="outras_partes3" class="number-imput" type="text" id="outras_partes3" value="<?php echo $atendimento->outras_partes3; ?>" size="13" />
										<select name="outras_partes3_id" id="outras_partes3_id">
											<option value="Exportador" <?php if($atendimento->outras_partes3_id=='Exportador') echo 'selected'; ?> >Exportador</option>
											<option value="Armador" <?php if($atendimento->outras_partes3_id=='Armador') echo 'selected'; ?> >Armador</option>
											<option value="P&I Armador <?php if($atendimento->outras_partes3_id=='P&I Armador') echo 'selected'; ?> ">P&I Armador</option>
											<option value="Afretador" <?php if($atendimento->outras_partes3_id=='Afretador') echo 'selected'; ?> >Afretador</option>
											<option value="P&I Afretador" <?php if($atendimento->outras_partes3_id=='P&I Afretador') echo 'selected'; ?> >P&I Afretador</option>
											<option value="Comprador" <?php if($atendimento->outras_partes3_id=='Comprador') echo 'selected'; ?> >Comprador</option>
										</select>
									</td>
								</tr>
								<tr>
									<td align="right">Terminais:</td>
									<td align="left">
										<?php
		
										$terms = $db->get_results("SELECT terminal_id, nome FROM tb_terminais ORDER BY terminal_id");
										foreach($terms as $term) 
										{
											$outstr = '<input type="checkbox" name="terminais_id[]" id="tid'.$term->terminal_id.'" );" value="'.$term->terminal_id.'"';
											
											if(in_array($term->terminal_id, $tbTerminais))
												$outstr = $outstr.' checked ';
												
											$outstr = $outstr.'>&#32;'.$term->nome.'&#32;&#124;&#32;';
																						
											echo $outstr;
										} 
										?>
									</td>
								</tr>
								<tr>
									<td align="right">Produtos:</td>
									<td align="left">
										<?php 
										$prods = $db->get_results("SELECT produto_id, nome FROM tb_produtos ORDER BY produto_id");
										foreach($prods as $prod) 
										{
											$outstr = '<input type="checkbox" name="produtos_id[]" value="'.$prod->produto_id.'"';
											
											if(in_array($prod->produto_id, $tbProdutos))
												$outstr = $outstr.' checked ';
												
											$outstr = $outstr.'>&#32;'.$prod->nome.'&#32;&#124;&#32;';
																						
											echo $outstr;
										}
										?>
									</td>
							    </tr>
								<tr>
									<td class="right-align">Apagar planos salvos:</td>
									<td align="left" >
										<?php 
											$filelist = glob('planos/plano_de_carga'.$atendimento_id.'.*' );

											foreach( $filelist as $fileitem ) {
												if( preg_match('/\.(.*)$/', $fileitem, $match) === 1 ) {
													
													$filetype = $match[count($match)-1];
													$outstr = '<input type="checkbox" name="files2Delete[]" value="'.$filetype.'"';
													$outstr = $outstr.'>&#32;'.$filetype.'&#32;&#124;&#32;';
													echo $outstr;
												}
											}
										?>
									</td>
								</tr>
							</table>

							<p class="center-align">
								<?php
									if( $adicionar_atendimento )
										echo '<input name="adicionar" type="submit" id="adicionar" value="Adicionar Novo Atendimento"/>';
									else
										echo '<input name="salvar" type="submit" id="salvar" value="Salvar Altera&ccedil;&otilde;es"/>';
								?>
							</p>

						</form>
					</td>
			  	</tr>
			</table>
		</main>
		<?php include_once('script.html'); ?>
	</body>
</html>
