<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=0; include("auth.php"); ?>

<!doctype html>
<html lang="en">
	<head>
		<title>Standard Brazil - Administrative area</title>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
		<link href="css/padrao.css" rel="stylesheet" type="text/css" />
	</head>

	<body>
		<?php include("menu.html"); ?>
	
		<main role="main" class="container">
			
			<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>

			<?php
				$total_diferenca=0;
				$total_navios1=0;
				$total_navios2=0;
				$total_navios3=0;
				$total_navio_geral=0;
				$total_porcentagem=0;
				
				if($atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=$ano AND diferenca>=0.21 AND ((cliente IS NULL) OR (LENGTH(cliente) < 1))")) {
					foreach($atendimento as $atendimento) {
						$total_diferenca+= $atendimento->diferenca;
						$total_navios1++;
						$total_navio_geral++;
					}
				//echo "<h3>diferenca>'>0,21'</h3><br>Diferença total: ".$total_diferenca."%<br>Total navios: ".$total_navios1."<br><hr><br>";
				}
				
				if($atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=$ano AND diferenca<=-0.21 AND ((cliente IS NULL) OR (LENGTH(cliente) < 1))")) {
					foreach($atendimento as $atendimento) {
						$total_diferenca+= $atendimento->diferenca;
						$total_navios2++;
						$total_navio_geral++;
					}
				//echo "<h3>diferenca>'>-0,21'</h3><br>Diferença total: ".$total_diferenca."%<br>Total navios: ".$total_navios2."<br><hr><br>";
				}
				
				if($atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=$ano AND diferenca<=0.20 AND diferenca>=-0.20 AND ((cliente IS NULL) OR (LENGTH(cliente) < 1))")) {
					foreach($atendimento as $atendimento) {
						$total_diferenca+= $atendimento->diferenca;
						$total_navios3++;
						$total_navio_geral++;
					}
				//echo "<h3>diferenca='entre -0,20 e 0,20'</h3><br>Diferença total: ".$total_diferenca."%<br>Total navios: ".$total_navios3."<br>";
				//echo "<br>TOTAL NAVIOS: ".$total_navio_geral;
				//echo "<br><br>Dif1: ".round(($total_navios1/$total_navio_geral*100));
				//echo "<br><br>Dif2: ".round(($total_navios2/$total_navio_geral*100));
				//echo "<br><br>Dif3: ".round(($total_navios3/$total_navio_geral*100));
				
					$dif1=0;
					$dif2=0;
					$dif3=0;

					if( $total_navio_geral > 0 ) {
						$dif1=round(($total_navios1/$total_navio_geral*100));
						$dif2=round(($total_navios2/$total_navio_geral*100));
						$dif3=round(($total_navios3/$total_navio_geral*100));
					}
				}
			?>
		</main>
		<!-- <script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script> -->
		<!-- <script src="https://code.jquery.com/jquery-3.3.1.min.js"  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script> -->
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
                text: 'Comparação entre navios no ano de <?php echo $ano; ?>'
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
                            return '<b>'+ this.point.name +'</b>: '+ this.percentage.toPrecision(4) +' %';
                        }
                    }
               }
            },
            series: [{
                type: 'pie',
                name: 'Percentual',
                data: [
                    ['>0,21 - <?php echo $total_navios1; ?> navios',  <?php echo $dif1; ?>],
                    ['>-0,21  - <?php echo $total_navios2; ?> navios', <?php echo $dif2; ?>],
                    {
                        name: '-0,20 até 0,20 - <?php echo $total_navios3; ?> navios',
                        y: <?php echo $dif3; ?>,
	                    sliced: true,
                        selected: true
                    }
                ]
            }]
        });
    });
    
});		</script>

	</body>
</html>

