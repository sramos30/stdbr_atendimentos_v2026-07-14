<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=0; include("auth.php"); ?>

<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Relatório Mensal Gráfico</title>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
		<link href="css/padrao.css" rel="stylesheet" type="text/css" />		
        <?php
		
	$m = array(1 =>"Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"); 
	$mes=str_replace("0","",$mes);

	$total_diferenca=0;
	$total_navios1=0;
	$total_navios2=0;
	$total_navios3=0;
	$total_navios4=0;
	$total_navios5=0;
	$total_navio_geral=0;
	$total_porcentagem=0;

	$atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=$ano AND MONTH(data)=$mes AND diferenca>=0.21 AND diferenca<=0.99 AND ((cliente IS NULL) OR (LENGTH(cliente) < 1))" );

	if($atendimento) {
		foreach($atendimento as $atendimento) {
			$total_diferenca+= $atendimento->diferenca;
			$total_navios1++;
			$total_navio_geral++;
		}
	//echo "<h3>diferenca>'>0,21'</h3><br>Diferença total: ".$total_diferenca."%<br>Total navios: ".$total_navios1."<br><hr><br>";
	}
	
	if($atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=$ano AND MONTH(data)=$mes AND diferenca<=-0.21 AND diferenca>=-0.99")) {
		foreach($atendimento as $atendimento) {
			$total_diferenca += $atendimento->diferenca;
			$total_navios2++;
			$total_navio_geral++;
		}
	//echo "<h3>diferenca>'>-0,21'</h3><br>Diferença total: ".$total_diferenca."%<br>Total navios: ".$total_navios2."<br><hr><br>";
	}
	
	if($atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=$ano AND MONTH(data)=$mes AND diferenca<=0.20 AND diferenca>=-0.20")) {
		foreach($atendimento as $atendimento) {
			$total_diferenca+= $atendimento->diferenca;
			$total_navios3++;
			$total_navio_geral++;
		}	
	}
	
	if($atendimentos = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=$ano AND MONTH(data)=$mes AND diferenca>0.99")) 
	{
		foreach($atendimentos as $atendimento) 
		{
			$total_diferenca+= $atendimento->diferenca;
			$total_navios4++;
			$total_navio_geral++;
		}
	}
	
	if($atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=$ano AND MONTH(data)=$mes AND diferenca<-0.99")) {
		foreach($atendimento as $atendimento) {
			$total_diferenca+= $atendimento->diferenca;
			$total_navios5++;
			$total_navio_geral++;
		}	
	}
	
	$dif1= 0;
	$dif2= 0;
	$dif3= 0;
	$dif4= 0;
	$dif5= 0;

	if( $total_navio_geral > 0 ) {
		$dif1=round(($total_navios1/$total_navio_geral*100));
		$dif2=round(($total_navios2/$total_navio_geral*100));
		$dif3=round(($total_navios3/$total_navio_geral*100));
		$dif4=round(($total_navios4/$total_navio_geral*100));
		$dif5=round(($total_navios5/$total_navio_geral*100));
	}
?>

    <style type="text/css">
		body {
			margin-top: 20px;
		}
		body,td,th {
			font-family: Arial, Helvetica, sans-serif;
			font-size: 12px;
		}
    </style>
	</head>
	<body>
	<?php include("menu.html"); ?>

	<main role="main" class="container">

<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="30%"><img src="imagens/logo_print.gif" width="350" height="76"></td>
    <td width="70%">R. José Gomes 235 • Paranaguá - PR • Brazil • 83203-610  • Phones: 55(41) 3422-8239 / 9 9978-3748</td>
  </tr>
</table>
<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>
	</main>
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>

		<script src="scripts/highcharts.js"></script>
		<script src="scripts/highcharts.modules.exporting.js"></script>

		<script type="text/javascript">
			$(function () {
				var chart;
				$(document).ready(function() {
						chart = new Highcharts.Chart({
							chart: {
								renderTo: 'container',
								plotBackgroundColor: null,
								plotBorderWidth: null,
								plotShadow: false
							},
							title: {
								text: 'Comparação das diferenças entre terra e bordo - <?php echo $m[$mes]."/".$ano;?> - <?php echo $total_navio_geral." navios"; ?> '
							},
							subtitle: {
								text: 'Diferença percentuais entre arqueação e balança'
							},
							tooltip: {
								pointFormat: '{series.name}: <b>{point.percentage}%</b>',
								percentageDecimals: 1
							},
							credits: {
								enabled: false
							},
							plotOptions: {
								pie: {
									allowPointSelect: true,
									cursor: 'pointer',
									dataLabels: {
										enabled: true,
										color: '#000000',
										connectorColor: '#000000',
										formatter: function() {
											return '<b>'+ this.point.name +'</b>: '+ this.percentage.toPrecision(2) +' %';
										}
									}
							   }
							},
							series: [{
								type: 'pie',
								name: 'Percentual',
								data: [
									['>0,21 - <?php echo ((int)$total_navios1); ?> navios',  <?php echo $dif1; ?>],
									['>-0,21  - <?php echo ((int)$total_navios2); ?> navios', <?php echo $dif2; ?>],
									['>1  - <?php echo ((int)$total_navios4); ?> navios', <?php echo $dif4; ?>],
									['>-1  - <?php echo ((int)$total_navios5); ?> navios', <?php echo $dif5; ?>],	
									{
										name: '-0,20 até 0,20 - <?php echo ((int)$total_navios3); ?> navios',
										y: <?php echo $dif3; ?>,
										sliced: true,
										selected: true
									}
									]								
							}]
						});
				});
				
			});

			// if( $total_navio_geral > 0 ) {}

		</script>
	</body>
</html>
