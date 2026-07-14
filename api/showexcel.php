<?php
include_once './config/core.php';
include_once './shared/apiAuth.php';
include_once './shared/utilities.php';
include_once './shared/SimpleXLSX.php';

// utilities
$utilities = new Utilities();

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

//http://standardbrazil.com.br/api/showexcel.php?mode=json&filename=../atendimentosv2/planos/plano_de_carga3883.xlsm
//https://sramos.online/api/showexcel.php?mode=json&filename=../planos/plano_de_carga3883.xlsm
//http://standardbrazil.com.br/atendimentos/api/showexcel.php?mode=json&filename=3883.xlsm

//var_dump( $_FILES ); var_dump( $_POST ); var_dump( $_GET );

$kwMode=isset($_GET["mode"]) ? strtolower(htmlspecialchars(strip_tags($_GET["mode"]))) : "excel";

unset($filename);

if( isset($_GET["filename"]) ) {
	$filename = htmlspecialchars(strip_tags($_GET["filename"]));
}

if( !isset($filename) ) {
	if( !isset($_FILES["file"]) || $_FILES["file"]["error"] == 1 
		//|| $_FILES["file"]["type"] != "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
		) {
		
		// set response code - 404 Not found
		http_response_code(200);
		echo json_encode( array("error"=> 'tag "file" not found or file.error:'.$_FILES["file"]["error"]) );
		exit;
	} else {
		//$filename = $_FILES["file"]["name"];
		//$tmpfilename = $_FILES["file"]["tmp_name"];
		//move_uploaded_file($tmpfilename, $filename );

		$filename = $_FILES["file"]["tmp_name"];
		//http_response_code(200);
		//echo json_encode( array("file"=> 'file found: filename='.$filename) );
		//exit;
	}
} else {
	$filename = "../planos/plano_de_carga".$filename;
}

//$xlsx = new SimpleXLSX($filename);
//var_dump($xlsx);
//var_dump($filename);
//var_dump($xlsx->rowsEx() );
//var_dump($sheets = $xlsx->sheetNames());
//exit;


//echo '<h1>'.$filename.'</h1><pre>';
if ( $xlsx = SimpleXLSX::parse($filename) ) {
	$sheets = $xlsx->sheetNames();

	for( $i = 0; $i < count($sheets); $i++ ) {

		if( count($sheets) > 1 && preg_match('/([dD][cC][pP])/', $sheets[$i], $matches) === 0 ) {
			continue;
		}

		//print_r( "<b>Sheet: ".$sheets[$i]."</br><br>" );

		switch( $kwMode ) {
			case 'rows':
				echo '<pre>';			
				print_r( $xlsx->rows($i) );
				echo '</pre>';
				break;
			case 'table':
				header("Access-Control-Allow-Origin: *");
				header("Content-Type: text/html; charset=UTF-8");
				echo '<html>';
				echo '<header><title>'.$filename.'</title>';
				echo '<style>';
				echo 'body {background-color: white;}';
				echo 'table { border: 0px; }';
				echo 'th, td, input, select {';
				echo 'border: 0px solid black;';
				echo 'border-radius: 2px;';
				echo '}';
				echo '</style>';
				echo '</header>';

				echo '<body>';
				echo '<table border="0" cellpadding="3" >';
				foreach( $xlsx->rows($i) as $r ) {
					echo '<tr><td>'.implode('</td><td>', $r ).'</td></tr>';
				}
				echo '</table>';
				echo '</body>';
				echo '</html>';
				break;
			case 'cells':
				echo '<pre>';
				foreach ( $xlsx->rows($i) as $r => $row ) {
					foreach ( $row as $c => $cell ) {
						echo ($c > 0) ? ', ' : '';
						echo ( $r === 0 ) ? '<b>'.$cell.'</b>' : $cell;
					}
					echo '<br/>';
				}
				echo '</pre>';			
				break;
			case 'rowext':
				echo '<pre>';
				print_r( $xlsx->rowsEx($i) ); 
				echo '</pre>';			
				break;
			case 'html':
				echo( $xlsx->toHTML($i) );
				break;
			default: //json
				// required headers
				header("Access-Control-Allow-Origin: *");
				header("Content-Type: application/json; charset=UTF-8");
				
				$_rows = [];
				foreach ( $xlsx->rowsEx($i) as $r ) {
					$_row = [];
				
					foreach ( $r as $c ) {
						if( $c["value"] != "" ) {
							$c["c"] = ($xlsx->getIndex($c["name"])[0]+1);

							if( $c["type"] == "str" || $c["type"] == "s" ) {
								$c["tag"] = $utilities->toTag($c["value"]);
							}

							array_push( $_row, $c);
						}
					}

					if( count($_row) > 0 )
						array_push($_rows, $_row);
				}

				if( count($_rows) > 0 ) {
					echo json_encode($_rows);
				}
		}
	}
} else {
	http_response_code(500);
	$retval = array();
	$retval["error"] = SimpleXLSX::parseError();
	echo json_encode($retval);
	die();
}

?>
