<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=0; include("auth.php"); ?>

<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Relatório Gráfico</title>
<?php	
	$total_diferenca=0;
	$total_navios1=0;
	$total_navios2=0;
	$total_navios3=0;
	$total_navios_geral=0;
	$total_porcentagem=0;
	if($atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=2012 AND diferenca>=0.21")) {
	foreach($atendimento as $atendimento) {
	$total_diferenca+= $atendimento->diferenca;
	$total_navios1++;
	$total_navio_geral++;
	}
	echo "<h3>diferenca>'>0,21'</h3><br>Diferença total: ".$total_diferenca."%<br>Total navios: ".$total_navios1."<br><hr><br>";
	}
	
	$total_diferenca=0;
	if($atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=2012 AND diferenca<=-0.21")) {
	foreach($atendimento as $atendimento) {
	$total_diferenca+= $atendimento->diferenca;
	$total_navios2++;
	$total_navio_geral++;
	}
	echo "<h3>diferenca>'>-0,21'</h3><br>Diferença total: ".$total_diferenca."%<br>Total navios: ".$total_navios2."<br><hr><br>";
	}
	
	$total_diferenca=0;
	if($atendimento = $db->get_results("SELECT diferenca FROM tb_atendimentos WHERE YEAR(data)=2012 AND diferenca<=0.20 AND diferenca>=-0.20")) {
	foreach($atendimento as $atendimento) {
	$total_diferenca+= $atendimento->diferenca;
	$total_navios3++;
	$total_navio_geral++;
	}
	echo "<h3>diferenca='entre -0,20 e 0,20'</h3><br>Diferença total: ".$total_diferenca."%<br>Total navios: ".$total_navios3."<br>";
	echo "<br>TOTAL NAVIOS: ".$total_navio_geral;
	//echo "<br><br>Dif1: ".round(($total_navios1/$total_navio_geral*100));
	//echo "<br><br>Dif2: ".round(($total_navios2/$total_navio_geral*100));
	//echo "<br><br>Dif3: ".round(($total_navios3/$total_navio_geral*100));
	
	$dif1=round(($total_navios1/$total_navio_geral*100));
	$dif2=round(($total_navios2/$total_navio_geral*100));
	$dif3=round(($total_navios3/$total_navio_geral*100));
	}
?>
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
    <td width="70%">R. José Gomes 235 • Paranaguá - PR • Brazil • 83203-610  • Phones: 55(41) 3422-8239 / 9 9978-3748 </td>
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
                text: 'Comparação entre navios no ano de 2012'
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
                            return '<b>'+ this.point.name +'</b>: '+ this.percentage +' %';
                        }
                    }
               }
            },
            series: [{
                type: 'pie',
                name: 'Percentual',
                data: [
                    ['>0,21 - <? echo $total_navios1; ?> navios',  <?php echo $dif1; ?>],
                    ['>-0,21  - <? echo $total_navios2; ?> navios', <?php echo $dif2; ?>],
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
    
});
		</script>
</body>
</html>
