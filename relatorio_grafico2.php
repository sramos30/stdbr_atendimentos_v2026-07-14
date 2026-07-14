<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=0; include("auth.php"); ?>

<!doctype html>
<html lang="en">
	<head>
		<title>Standard Brazil - Administrative area</title>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
		<link href="padrao.css<?php echo '?v='.microtime(true); ?>" rel="stylesheet" type="text/css" />

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
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td width="30%"><img src="imagens/logo_print.gif" width="350" height="76"></td>
					<td width="70%">
						R. José Gomes 235 • Paranaguá - PR • Brazil • 83203-610  • Phones: 55(41) 3422-8239 / 9 9978-3748 
					</td>
				</tr>
			</table>
			<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>
		</main>
		
		<!-- <script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script> -->
		<!-- <script src="https://code.jquery.com/jquery-3.3.1.min.js"  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script> -->
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
		
		<script src="scripts/highcharts.js"></script>
		<script src="scripts/highcharts.modules.exporting.js"></script>

		<script type="text/javascript">
			<?php
	
				//$ano=2012;
	
				for ($i = 1; $i <= 12; ++$i)
				{	
				if($atendimento = $db->get_results("SELECT * FROM tb_atendimentos WHERE MONTH(data)=$i AND YEAR(data)=$ano AND ((cliente IS NULL) OR (LENGTH(cliente) < 1)) ORDER BY data, atendimento_id")) {
								
				$total_balanca = 0;
				$total_arqueacao = 0;
				$total_excesso = 0;
				$total_falta = 0;
				$total_diferenca = 0;	
				
				foreach($atendimento as $atendimento) {	
					$total_balanca+=$atendimento->balanca;
					$total_arqueacao+=$atendimento->arqueacao;
					$total_excesso+=$atendimento->excesso;
					$total_falta+=$atendimento->falta;
					$total_diferenca+=$atendimento->diferenca;	
				}
					$total_dif_anual[] = round(((($total_excesso-$total_falta)*100)/$total_balanca), 2);;
					//$total_balanca_anual[] = number_format($total_balanca, 3, ',', '.');
				} 
				
				} 
				
				//echo '<pre><code>';
				//var_dump($total_balanca_anual);
				//print_r($total_dif_anual);
				//echo '</code></pre>';
				
				$comma_separated = implode(",", $total_dif_anual);
				//echo $comma_separated;
				
			?>

			$(function () {
				var chart;
				$(document).ready(function() {
					
					
							// Radialize the colors
					Highcharts.getOptions().colors = $.map(Highcharts.getOptions().colors, function(color) {
						return {
							linearGradient: { cx: 0, cy: 0, r: 0 },
							stops: [
								[0, color],
								[1, Highcharts.Color(color).brighten(-0.3).get('rgb')] // darken
							]
						};
					});
					
					chart = new Highcharts.Chart({
						chart: {
							renderTo: 'container',
							type: 'column'
						},
						title: {
							text: 'Diferenças (entre arqueações e balanças) JAN/<?php echo $ano ?> - DEZ/<?php echo $ano ?>'
						},
						subtitle: {
							text: 'Standard Brazil Marine Surveys & Services Ltd.'
						},

						xAxis: {
							categories: [
								'Janeiro',
								'Fevereiro',
								'Março',
								'Abril',
								'Maio',
								'Junho',
								'Julho',
								'Agosto',
								'Setembro',
								'Outubro',
								'Novembro',
								'Dezembro'
							]
						},
						yAxis: {
							title: {
								text: 'Diferenças',
							},				
							labels: {
								formatter: function() {
									   return this.value + "%";
									}
							}				
						},			
						credits: {
							enabled: false
						},
						legend: {
							layout: 'vertical',
							backgroundColor: '#FFFFFF',
							align: 'center',
							verticalAlign: 'bottom',
							shadow: true
						}, 	
						tooltip: {
							enabled: true,
							formatter: function() {
								return ''+
									this.x +': '+ this.y +' %';
							}
						},
						plotOptions: {
							column: {
								dataLabels: {
									enabled: true,
									color: '#FFFFFF',
									formatter: function() {
									   return this.y +' %';
									}
								},
								pointPadding: 0,
								borderWidth: 0,
								stacking: 'normal'					
							}
						},			
							series: [{
							showInLegend: true,
							data: [<?php echo $comma_separated ?>],
							name: 'Diferenças (em %)'       
						}]
					});
				});
				
			});
			</script>
	</body>
</html>
