<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=0; include("auth.php"); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Standard Brazil - Área Administrativa</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
	<link href="padrao.css" rel="stylesheet" type="text/css" />
<style type="text/css" media="print">
</style>
</head>
<body>
	<?php include("menu.html"); ?>
		<main role="main" class="container">
			<?php
				$ano="2018";
				setlocale(LC_ALL, 'pt_BR');
				include "ez_sql.php";
				$m = array(1 =>"Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"); 
				for ($i = 1; $i <= 12; ++$i)
				{	
				if($atendimento = $db->get_results("SELECT * FROM tb_atendimentos WHERE MONTH(data)=$i ORDER BY data, atendimento_id")) {
				$total_navios=count($atendimento);
			?>

			<table width="100%" border="0" class="page-break">
			  <THEAD>
			  <tr>
				<td colspan="16" align="center" bgcolor="#00B050" class="titulo"><?php echo $m[$i]."/".$ano;?></td>
			  </tr>
			  <tr>
				<td colspan="3" rowspan="2" align="center" bgcolor="#99CCFF"><strong>DATA</strong></td>
				<td rowspan="2" align="center" bgcolor="#00FFFF"><strong>NAVIO</strong></td>
				<td rowspan="2" align="center" bgcolor="#00FFFF"><strong>PRODUTO</strong></td>
				<td rowspan="2" align="center" bgcolor="#99CCFF"><strong>CARREGADO<br />
				  POR BALANÇA</strong></td>
				<td rowspan="2" align="center" bgcolor="#00FFFF"><strong>CARREGADO POR<br />
				  ARQUEAÇÃO</strong></td>
				<td colspan="2" align="center" bgcolor="#99CCFF"><strong>DIFERENÇA &#8482;</strong></td>
				<td rowspan="2" align="center"><strong>TERMINAIS</strong></td>
				<td rowspan="2" align="center" bgcolor="#00CCFF"><strong>PERC</strong></td>
				<td rowspan="2" align="center"><strong>COMANDO NAVIO</strong></td>
				<td rowspan="2" align="center"><strong>PERITO RECEITA</strong></td>
				<td rowspan="2" align="center"><strong>OUTRAS PARTES 1</strong></td>
				<td rowspan="2" align="center"><strong>OUTRAS PARTES 2</strong></td>
				<td rowspan="2" align="center"><strong>OUTRAS PARTES 3</strong></td>
			  </tr>
			  <tr>
				<td align="center" bgcolor="#CCFFCC"><strong>EXCESSO</strong></td>
				<td align="center" bgcolor="#FFFF00"><strong>FALTA</strong></td>
			  </tr>
			  </THEAD>
			  <?
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
				
				foreach($atendimento as $atendimento) {	
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
			  <tr>
				<td align="center" style="border-right:none"><?php echo strftime( '%e', strtotime(date("$atendimento->data"))); ?></td>
				<td align="center" style="border-right:none"><?php echo strftime( '%b', strtotime(date("$atendimento->data"))); ?></td>
				<td align="center"><?php echo strftime( '%Y', strtotime(date("$atendimento->data"))); ?></td>
				<td align="center"><?php echo $atendimento->navio ?></td>
				<td align="center"><?php 
				$produto = $db->get_results("SELECT tb_produtos.nome FROM tb_produtos, tb_atendimentos_produtos WHERE tb_atendimentos_produtos.produto_id = tb_produtos.produto_id AND tb_atendimentos_produtos.atendimento_id=$atendimento->atendimento_id");
				$c=count($produto);
				$b=1;
				foreach($produto as $produto) {
				echo $produto->nome;
				if($b < $c) echo "/";
				$b++;
				}
				?></td>
				<td align="right"><?php echo number_format($atendimento->balanca, 3, ',', '.');  ?></td>
				<td align="right"><?php echo number_format($atendimento->arqueacao, 3, ',', '.');  ?></td>
				<td align="center" bgcolor="#D8E4BC"><?php if($atendimento->excesso >0) echo number_format ($atendimento->excesso, 3, ',', '.');  ?></td>
				<td align="center" bgcolor="#FFFF00"><?php if($atendimento->falta >0) echo number_format ($atendimento->falta, 3, ',', '.'); ?></td>
				<td align="center" class="teminais"><?php 
				$terminal = $db->get_results("SELECT tb_terminais.nome FROM tb_terminais, tb_atendimentos_terminais WHERE tb_atendimentos_terminais.terminal_id = tb_terminais.terminal_id AND tb_atendimentos_terminais.atendimento_id=$atendimento->atendimento_id");
				$c=count($terminal);
				$b=1;
				foreach($terminal as $terminal) {
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
				<td colspan="4" align="center" bgcolor="#99CCFF"><strong><?php echo $m[$i]."/".$ano;?></strong></td>
				<td align="center" bgcolor="#99CCFF"><strong><?php echo $total_navios." NAVIOS"; ?></strong></td>
				<td align="right" bgcolor="#99CCFF"><strong><?php echo number_format($total_balanca, 3, ',', '.'); ?></strong></td>
				<td align="right" bgcolor="#99CCFF"><strong><?php echo number_format($total_arqueacao, 3, ',', '.'); ?></strong></td>
				<td align="center" bgcolor="#00B050"><strong><?php echo number_format ($total_excesso, 3, ',', '.'); ?></strong></td>
				<td align="center" bgcolor="#FFFF00"><strong><?php echo number_format ($total_falta, 3, ',', '.'); ?></strong></td>
				<td align="center" <?php if($total_diferenca >0) $bg="#00B050"; else $bg="red"; ?>bgcolor="<?php echo $bg?>"><strong><?php echo number_format (($total_excesso-$total_falta), 3, ',', '.'); ?></strong></td>
				<td align="center" bgcolor="<?php echo $bg?>"><strong><?php echo round(((($total_excesso-$total_falta)*100)/$total_balanca), 2); //$total_diferenca ?>%</strong></td>
				<td align="center"><strong><?php echo number_format($total_comando_navio, 3, ',', '.'); ?></strong></td>
				<td align="center"><strong><?php echo number_format($total_perito_receita, 3, ',', '.'); ?></strong></td>
				<td align="center"><strong><?php if($atendimento->total_outras_partes1 >0) echo number_format($total_outras_partes1, 3, ',', '.'); ?></strong></td>
				<td align="center"><strong><?php if($atendimento->total_outras_partes2 >0) echo number_format($total_outras_partes2, 3, ',', '.'); ?></strong></td>
				<td align="center"><strong><?php if($atendimento->total_outras_partes3 >0) echo number_format($total_outras_partes3, 3, ',', '.'); ?></strong></td>
			  </tr>
			  </TBODY>
			</table>
			<?php 	} } ?>
			<br />
			<br />
			<?
			/*
			$start = $month = strtotime('2012-02-01');
			$end = strtotime('2011-01-01');
			while($month < $end)
			{
				 echo date('F Y', $month)."<br>";
				 $month = strtotime("+1 month", $month);
			}
			*/
			?>
		</main>
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
		
	</body>
</html>
