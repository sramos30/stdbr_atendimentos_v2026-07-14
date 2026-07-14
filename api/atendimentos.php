<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// include database and object files
include_once './config/core.php';
include_once './config/database.php';
include_once './shared/utilities.php';
include_once './objects/atendimento.php';
include_once './shared/cacheControl.php';

require('./shared/xlsxwriter.class.php');

$writer = new XLSXWriter();

// utilities
$utilities = new Utilities();

// instantiate database and product object
$database = new Database($config);
$db = $database->getConnection();

$atendimento = new Atendimento($db,$utilities);

$planosDir = '../planos';
$cachePath = './cache';
$cachePrefix = 'Atend';

$cache = new CacheControl($cachePath, $cachePrefix, 5);

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

switch($requestMethod) {
	case "GET": {
		// get keywords 
		$kwId=isset($_GET["id"]) ? htmlspecialchars(strip_tags($_GET["id"])) : "";
		$kwCodAtd=isset($_GET["codatd"]) ? htmlspecialchars(strip_tags($_GET["codatd"])) : "";
		// atdtype=draft|ref
		$kwAtdType=isset($_GET["atdtype"]) ? htmlspecialchars(strip_tags($_GET["atdtype"])) : "";
		$kwNavio=isset($_GET["navio"]) ? htmlspecialchars(strip_tags($_GET["navio"])) : "";
		$kwFalta=isset($_GET["falta"]) ? (Float)$_GET["falta"] : "";
		$kwExcesso=isset($_GET["excesso"]) ? (Float)$_GET["excesso"] : "";
		$kwDifMenor=isset($_GET["difmenor"]) ? (Float)$_GET["difmenor"] : "";
		$kwDifMaior=isset($_GET["difmaior"]) ? (Float)$_GET["difmaior"] : "";
		$kwD1=isset($_GET["d1"]) ? htmlspecialchars(strip_tags($_GET["d1"])) : "";
		$kwD2=isset($_GET["d2"]) ? htmlspecialchars(strip_tags($_GET["d2"])) : "";
		$kwProds=isset($_GET["prods"]) ? strtolower(htmlspecialchars(strip_tags($_GET["prods"]))) : "";
		$kwTerms=isset($_GET["terms"]) ? strtolower(htmlspecialchars(strip_tags($_GET["terms"]))) : "";
		$kwMode=isset($_GET["mode"]) ? strtolower(htmlspecialchars(strip_tags($_GET["mode"]))) : "count";
		$kwExtFiles=isset($_GET["extfiles"]) ? strtolower(htmlspecialchars(strip_tags($_GET["extfiles"]))) : "s";
		//$page=page,$kwTitles=titles,$kwRecsPPage=recsppage
		
		$queryParms = array();

		if( strlen($kwId) > 0 ) 				$queryParms["Id"] = $kwId; 
		if( strlen($kwCodAtd) > 0 ) 		$queryParms["CodAtd"] = $kwCodAtd; 
		if( strlen($kwAtdType) > 0 ) 		$queryParms["AtdType"] = $kwAtdType; 
		if( strlen($kwNavio) > 0 ) 			$queryParms["Navio"] = $kwNavio; 
		if( strlen($kwFalta) > 0 ) 			$queryParms["Falta"] = $kwFalta; 
		if( strlen($kwExcesso) > 0 )		$queryParms["Excesso"] = $kwExcesso; 
		if( strlen($kwDifMenor) > 0 ) 	$queryParms["DifMenor"] = $kwDifMenor; 
		if( strlen($kwDifMaior) > 0 ) 	$queryParms["DifMaior"] = $kwDifMaior; 
		if( strlen($kwD1) > 0 ) 				$queryParms["D1"] = $kwD1; 
		if( strlen($kwD2) > 0 ) 				$queryParms["D2"] = $kwD2; 
		if( strlen($kwProds) > 0 ) 	  	$queryParms["Prods"] = $kwProds; 
		if( strlen($kwTerms) > 0 ) 	  	$queryParms["Terms"] = $kwTerms; 
		if( strlen($page) > 0 ) 	  		$queryParms["page"] = $page; 
		if( strlen($kwRecsPPage) > 0 )	$queryParms["RecsPPage"] = $kwRecsPPage; 

		// extensão para o arquivo de resposta
		switch( $kwMode ) {
			case "count": {
				$queryParms["RecsPPage"] = -1;
				$fext = "_count.json";
				break;
			}
			case "excel": {
				$fext = ".xlsx";
				$kwExtFiles = "s";
				$queryParms["RecsPPage"] = -1;
				break;
			}
			case "json": {
				$fext = ".json";
				break;
			}
			default:
				$fext = "_raw.json";
				break;
		}


		//printf("{$_SERVER["REQUEST_URI"]}\n");
		//$str = $utilities->array2Str( $queryParms );
		//printf("{$str}\n");
		//die();

		$userFilename = md5(json_encode($queryParms).$kwMode.$kwTitles);
		
		// verifica se já está no cache
		$fn = $cache->isInCache($userFilename.$fext);

		if( isset($fn) && strlen($fn) > 0 ) {
			http_response_code(200);
			$writer->downloadFile($fn, $cachePrefix."-".$userFilename."_".(microtime(true)*10000).$fext );
			exit;
		}

		// Apenas a qtd de regisros.
		if( $kwMode == "count" ) {
			$_out=array();
			//$_out["error"] = "ok";

			$_out["recs"] = $atendimento->count($queryParms);
			//$kwId, $kwCodAtd, $kwAtdType, $kwNavio, 
			//	$kwFalta, $kwExcesso, $kwDifMenor, $kwDifMaior, $kwD1, $kwD2, $kwProds, $kwTerms);
			
			$fname = $cache->getNewFName($userFilename.$fext);
			$data = json_encode($_out);
			
			// set response code - 200 OK
			http_response_code(200);

			// faz download do arquivo enquanto grava-o no cache
			$cache->downloadFile($fname, $userFilename."_".(microtime(true)*10000).$fext, $data );
			exit;			
		}

		// Carrega tabela tb_atendimentos
		$stmt = $atendimento->tb_atendimentos($queryParms);
			
			//$kwId, $kwCodAtd, $kwAtdType, $kwNavio, 
			//$kwFalta, $kwExcesso, $kwDifMenor, $kwDifMaior, $kwD1, $kwD2, $kwProds, 
			//$kwTerms, $page, $kwRecsPPage );

		$num = 0;

		if( $stmt ) {
			$num = $stmt->rowCount();
		}
		
		if( $num == 0 ) {
			// set response code - 404 Not found
			http_response_code(404);
			echo json_encode( array("error"=> "no records found!") );
			exit;
		}

		// recupera o metadados da tabela tb_atendimentos
		$_columns=$utilities->getColumnsMeta( $stmt );

		// não envia tabelas auxiliares se solicitado o não envio
		if( $kwExtFiles != "n" ) {

			$stmtProds = $atendimento->tb_atendimentos_produtos($queryParms);
				//$kwId, $kwCodAtd, $kwAtdType, $kwNavio, $kwFalta, $kwExcesso, 
				//$kwDifMenor, $kwDifMaior, $kwD1, $kwD2, $kwProds, $kwTerms, 
				//$page, $kwRecsPPage);

			$numProds = $stmtProds->rowCount();
			$_columnsProds=$utilities->getColumnsMeta( $stmtProds );

			$stmtTerms = $atendimento->tb_atendimentos_terminais($queryParms);
				//$kwId, $kwCodAtd, $kwAtdType, $kwNavio, $kwFalta, $kwExcesso, 
				//$kwDifMenor, $kwDifMaior, $kwD1, $kwD2, $kwProds, $kwTerms, 
				//$page, $kwRecsPPage);
			$numTerms = $stmtTerms->rowCount();
			$_columnsTerms=$utilities->getColumnsMeta( $stmtTerms );

			$stmtPorTerm = $atendimento->tb_atendimentos_poroes_terminais($queryParms);
				//$kwId, $kwCodAtd, $kwAtdType, $kwNavio, $kwFalta, $kwExcesso, 
				//$kwDifMenor, $kwDifMaior, $kwD1, $kwD2, $kwProds, $kwTerms, 
				//$page, $kwRecsPPage);
			$numPorTerm = $stmtPorTerm->rowCount();
			$_columnsPoroesTerminais=$utilities->getColumnsMeta( $stmtPorTerm );
		} else {
			$numProds = 0;
			$numTerms = 0;
			$numPorTerm = 0;
		}

		// Solicitado arquivo JSON
		if( $kwMode == "json") {
			$_atendimentos=array();
			//$_atendimentos["error"] = "ok";
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				//array_push($_atendimentos["rc"], '$row["atendimento_id"]'.$row["atendimento_id"] );

				if( (int)($row["atendimento_id"]) > 0  ) {
					if( !key_exists($row["atendimento_id"], $_atendimentos ) ) {

						$_atendimentos[$row["atendimento_id"]]["atdId"] = $row["atendimento_id"];
						$_atendimentos[$row["atendimento_id"]]["codAtendimento"] = $row["codAtendimento"];
						$_atendimentos[$row["atendimento_id"]]["data"] = $row["data"];
						$_atendimentos[$row["atendimento_id"]]["navio"] = $row["navio"];
						$_atendimentos[$row["atendimento_id"]]["balanca"] = sprintf("%.3f",$row["balanca"]);
						$_atendimentos[$row["atendimento_id"]]["arqueacao"] = sprintf("%.3f",$row["arqueacao"]);
						$_atendimentos[$row["atendimento_id"]]["comando_navio"] = sprintf("%.3f",$row["comando_navio"]);
						$_atendimentos[$row["atendimento_id"]]["perito_receita"] = sprintf("%.3f",$row["perito_receita"]);
						$_atendimentos[$row["atendimento_id"]]["outras_partes1_id"] = $row["outras_partes1_id"];
						$_atendimentos[$row["atendimento_id"]]["outras_partes1"] = sprintf("%.3f",$row["outras_partes1"]);
						$_atendimentos[$row["atendimento_id"]]["outras_partes2_id"] = $row["outras_partes2_id"];
						$_atendimentos[$row["atendimento_id"]]["outras_partes2"] = sprintf("%.3f",$row["outras_partes2"]);
						$_atendimentos[$row["atendimento_id"]]["outras_partes3_id"] = $row["outras_partes3_id"];
						$_atendimentos[$row["atendimento_id"]]["outras_partes3"] = sprintf("%.3f",$row["outras_partes3"]);
						$_atendimentos[$row["atendimento_id"]]["excesso"] = sprintf("%.3f",$row["excesso"]);
						$_atendimentos[$row["atendimento_id"]]["falta"] = sprintf("%.3f",$row["falta"]);
						$_atendimentos[$row["atendimento_id"]]["diferenca"] = sprintf("%.2f",$row["diferenca"]);
						$_atendimentos[$row["atendimento_id"]]["link"] = $row["link"];
						$_atendimentos[$row["atendimento_id"]]["cliente"] = $row["cliente"];

						$_atendimentos[$row["atendimento_id"]]["lstPlanos"] = array();

						$filelist = glob($planosDir.'/plano_de_carga'.$row["atendimento_id"].'.*' );
						
						foreach( $filelist as $fileitem ) {

							$filename = basename( $fileitem );

							//array_push( $_atendimentos[$row["atendimento_id"]]["lstPlanos"], $fileitem );

							if( preg_match('/\.([pP][dD][fF])$/', $fileitem, $matches) === 1 ) {
								array_push( $_atendimentos[$row["atendimento_id"]]["lstPlanos"], $matches[count($matches)-1] );
							} else if( preg_match('/\.([xX][lL][sS].*)$/', $fileitem, $matches) === 1 ) {
								array_push( $_atendimentos[$row["atendimento_id"]]["lstPlanos"], $matches[count($matches)-1] );
							}

							//array_push( $_atendimentos[$row["atendimento_id"]]["lstPlanos"], $fileitem );
						}
					}
				}
			}

			//array_push($_atendimentos["rc"], '$numProds:'.$numProds );


			//==>debug
			//echo json_encode( array( 
			//	"_atendimentos" => $_atendimentos,
			//));

			if($numProds>0) {

				while ($row = $stmtProds->fetch(PDO::FETCH_ASSOC)) {

					//array_push($_atendimentos["rc"], 'produtos:$row["atendimento_id"]:'.$row["atendimento_id"] );

					if( (int)($row["atendimento_id"]) > 0  ) {

						if( !key_exists("produtos", $_atendimentos[$row["atendimento_id"]] ) ) {
							$_atendimentos[$row["atendimento_id"]]["produtos"] = array();
						}

						//array_push($_atendimentos["rc"], 'produtos:$row["produto_id"]:'.$row["produto_id"] );
						
						array_push( $_atendimentos[$row["atendimento_id"]]["produtos"], $row["produto_id"] );

						//if( !key_exists($row["produto_id"], 
						//	$_atendimentos[$row["atendimento_id"]]["produtos"] ) ) {
						//
						//	$_atendimentos[$row["atendimento_id"]]["produtos"][$row["produto_id"]] = array();
						//	$_atendimentos[$row["atendimento_id"]]["produtos"][$row["produto_id"]]["nome"] = $row["nome"];
						//	$_atendimentos[$row["atendimento_id"]]["produtos"][$row["produto_id"]]["descricao"] = $row["descricao"];
						//	$_atendimentos[$row["atendimento_id"]]["produtos"][$row["produto_id"]]["tags"] = $row["tags"];
						//}
					}
				}
			}
			
			//array_push($_atendimentos["rc"], '$numTerms:'.$numTerms );

			if($numTerms>0) {
				while ($row = $stmtTerms->fetch(PDO::FETCH_ASSOC)) {

					//array_push($_atendimentos["rc"], 'terminais:$row["atendimento_id"]'.$row["atendimento_id"] );

					if( (int)($row["atendimento_id"]) > 0  ) {
						if( !key_exists("terminais", $_atendimentos[$row["atendimento_id"]] ) ) {
							$_atendimentos[$row["atendimento_id"]]["terminais"] = array();
							//array_push($_atendimentos["rc"], 'terminais: new array()' );
						}

						//array_push($_atendimentos["rc"], 'terminais:$row["terminal_id"]:'.$row["terminal_id"] );

						array_push( $_atendimentos[$row["atendimento_id"]]["terminais"], $row["terminal_id"] );

						//if( !key_exists($row["terminal_id"], 
						//	$_atendimentos[$row["atendimento_id"]]["terminais"] ) ) {
						//
						//	$_atendimentos[$row["atendimento_id"]]["terminais"][$row["terminal_id"]] = array();
						//	$_atendimentos[$row["atendimento_id"]]["terminais"][$row["terminal_id"]]["nome"] = $row["nome"];
						//	$_atendimentos[$row["atendimento_id"]]["terminais"][$row["terminal_id"]]["descricao"] = $row["descricao"];
						//	$_atendimentos[$row["atendimento_id"]]["terminais"][$row["terminal_id"]]["tags"] = $row["tags"];
						//}			
					}
				}
			}

			//array_push($_atendimentos["rc"], '$numPorTerm:'.$numPorTerm );

			if($numPorTerm>0) {
				while ($row = $stmtPorTerm->fetch(PDO::FETCH_ASSOC)) {

					//if( !key_exists($row["atendimento_id"], $_atendimentos) ) {
					//	//==>debug
					//	echo json_encode( array( 
					//	    "row['atendimento_id']" => $row["atendimento_id"],
					//	    "_atendimentos" => $_atendimentos,
					//	));
					//} 

					if( (int)($row["atendimento_id"]) > 0  && key_exists($row["atendimento_id"], $_atendimentos ) ) {

						if( !key_exists("poroes", $_atendimentos[$row["atendimento_id"]] ) ) {
							$_atendimentos[$row["atendimento_id"]]["poroes"] = array();
							//array_push($_atendimentos["rc"], 'poroes: new array()' );
						}

						//array_push($_atendimentos["rc"], 'PorTerm:$row["porao"]:'.$row["porao"] );

						if( !key_exists($row["porao"], $_atendimentos[$row["atendimento_id"]]["poroes"] ) ) {
							$_atendimentos[$row["atendimento_id"]]["poroes"][$row["porao"]] = array();
							$_atendimentos[$row["atendimento_id"]]["poroes"][$row["porao"]]["produto_id"] = intval($row["produto_id"]);
							$_atendimentos[$row["atendimento_id"]]["poroes"][$row["porao"]]["fatorestiva"] = sprintf("%.2f",$row["fatorestiva"]);
							$_atendimentos[$row["atendimento_id"]]["poroes"][$row["porao"]]["cubagem"] = intval($row["cubagem"]);
							$_atendimentos[$row["atendimento_id"]]["poroes"][$row["porao"]]["condicao"] = $row["condicao"];
							$_atendimentos[$row["atendimento_id"]]["poroes"][$row["porao"]]["terminais"] = array();
							$_atendimentos[$row["atendimento_id"]]["poroes"][$row["porao"]]["terminais"][$row["terminal_id"]]["quantidade"] = sprintf("%.3f",$row["quantidade"]);
						}else {
							$_atendimentos[$row["atendimento_id"]]["poroes"][$row["porao"]]["terminais"][$row["terminal_id"]]["quantidade"] = sprintf("%.3f",$row["quantidade"]);
						}
					}
				}
			}

			//==>debug
			//printf($utilities->array2Str($_atendimentos));
			//die();

			$fname = $cache->getNewFName($userFilename.$fext);			
			$data = json_encode($_atendimentos);

			// set response code - 200 OK
			http_response_code(200);

			// faz download do arquivo enquanto grava-o no cache
			$cache->downloadFile($fname, $userFilename."_".(microtime(true)*10000).$fext, $data );

			exit;			
		}

		// kwMode = excel | raw
		//
		$_atendimentos=array();
		
		// retrieve our table contents
		// fetch() is faster than fetchAll()
		// http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
		
		$rowNum = 0;

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			
			$_item=array();

			foreach($row as $key => $value ) {
				
				if( $key == "navio" ) {
					array_push( $_item, '=ABS(T'.($rowNum+2).')' );	
					array_push( $_item, '=YEAR(C'.($rowNum+2).')' );
					array_push( $_item, '=MONTH(C'.($rowNum+2).')' );	
				}

				array_push( $_item, $row[$key] );

			}

			$rowNum += 1;

			//foreach($row as $col) {
			//	array_push( $_item, $col );
			//}

			array_push($_atendimentos, $_item);
		}

		$_produtos=array();

		if($numProds>0) {
		
			// retrieve our table contents
			// fetch() is faster than fetchAll()
			// http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
			while ($row = $stmtProds->fetch(PDO::FETCH_ASSOC)) {
				
				$_item=array();

				foreach($row as $col) {
					array_push( $_item, $col );
				}

				array_push($_produtos, $_item);
			}
		}

		$_terminais=array();

		if($numTerms>0) {
		
			// retrieve our table contents
			// fetch() is faster than fetchAll()
			// http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
			while ($row = $stmtTerms->fetch(PDO::FETCH_ASSOC)) {
				
				$_item=array();

				foreach($row as $col) {
					array_push( $_item, $col );
				}

				array_push($_terminais, $_item);
			}
		}

		$_poroesTerminais=array();

		if($numPorTerm>0) {
		
			// retrieve our table contents
			// fetch() is faster than fetchAll()
			// http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
			while ($row = $stmtPorTerm->fetch(PDO::FETCH_ASSOC)) {
				
				$_item=array();

				foreach($row as $col) {
					array_push( $_item, $col );
				}

				array_push($_poroesTerminais, $_item);
			}
		}

		// kwMode == excel
		//
		if( $kwMode == "excel" ) {

			//require('./shared/SimpleXLSXGen.php');
			//$xlsx = new SimpleXLSXGen();
			//$xlsx->addSheet( $_records, 'atendimentos' );
			//$xlsx->saveAs('atendimentos.xlsx');
			//$xlsx->download();

			$writer->setAuthor('Standard Brazil - Marine Surveys & Services Ltda');

			if( $kwTitles != "n" ) {			
				$headerAtendimentos = array();

				for ($i = 0; $i < count($_columns['names']); $i++) {
					$_fmt = "";

					switch( $_columns['meta'][$i]['type'] )
					{
						case "DATE":
							$_fmt = "date";
							break;
						case "LONG":
							$_fmt = "#,##0";
							break;
						case "DECIMAL":
						case "FLOAT":
							$_fmt = "#,##0";
							if($_columns['meta'][$i]['precision']  > 0 )
								$_fmt .= ".".str_pad("0",$_columns['meta'][$i]['precision'],"0",STR_PAD_LEFT);
							break;
						default:
							$_fmt = "@";
							break;
					}

					$headerAtendimentos[$_columns['names'][$i]] = $_fmt;

					if( $_columns['names'][$i] == 'data') {						
						$headerAtendimentos['%Abs Dif'] = "#0.0000";
						$headerAtendimentos['Ano'] = "#0";
						$headerAtendimentos['Mes'] = "#0";
					}
				}

				//$writer->writeSheetHeader('Atendimentos', $headerAtendimentos );

				$writer->writeSheet($_atendimentos,'Atendimentos', $headerAtendimentos);
				//$writer->writeSheet($_atendimentos,'Atendimentos');

				if( $kwExtFiles != "n" ) {
					
					// Header line of _produtos
					$headerProdutos = array();
	
					for ($i = 0; $i < count($_columnsProds['names']); $i++) {
						$_fmt = "";
	
						switch( $_columnsProds['meta'][$i]['type'] )
						{
							case "DATE":
								$_fmt = "date";
								break;
							case "LONG":
								$_fmt = "#,##0";
								break;
							case "DECIMAL":
							case "FLOAT":
								$_fmt = "#,##0";
								if($_columnsProds['meta'][$i]['precision']  > 0 )
									$_fmt .= ".".str_pad("0",$_columnsProds['meta'][$i]['precision'],"0",STR_PAD_LEFT);
								break;
							default:
								$_fmt = "@";
								break;
						}
		
						$headerProdutos[$_columnsProds['names'][$i]] = $_fmt;
					}
					
					$writer->writeSheet($_produtos,'Produtos', $headerProdutos);
	
					// Header line of _terminais
					$headerTerminais = array();
	
					for ($i = 0; $i < count($_columnsTerms['names']); $i++) {
						$_fmt = "";
		
						switch( $_columnsTerms['meta'][$i]['type'] )
						{
							case "DATE":
								$_fmt = "date";
								break;
							case "LONG":
								$_fmt = "#,##0";
								break;
							case "DECIMAL":
							case "FLOAT":
								$_fmt = "#,##0";
								if($_columnsTerms['meta'][$i]['precision']  > 0 )
									$_fmt .= ".".str_pad("0",$_columnsTerms['meta'][$i]['precision'],"0",STR_PAD_LEFT);
								break;
							default:
								$_fmt = "@";
								break;
						}
		
						$headerTerminais[$_columnsTerms['names'][$i]] = $_fmt;
					}
		
					$writer->writeSheet($_terminais,'Terminais', $headerTerminais);
	
					// Header line of _columnsPoroesTerminais
					$headerPoroesTerminais = array();
	
					for ($i = 0; $i < count($_columnsPoroesTerminais['names']); $i++) {
						$_fmt = "";
	
						switch( $_columnsPoroesTerminais['meta'][$i]['type'] )
						{
							case "DATE":
								$_fmt = "date";
								break;
							case "LONG":
								$_fmt = "#,##0";
								break;
							case "DECIMAL":
							case "FLOAT":
								$_fmt = "#,##0";
								if($_columnsPoroesTerminais['meta'][$i]['precision']  > 0 )
									$_fmt .= ".".str_pad("0",$_columnsPoroesTerminais['meta'][$i]['precision'],"0",STR_PAD_LEFT);
								break;
							default:
								$_fmt = "@";
								break;
						}
	
						$headerPoroesTerminais[$_columnsPoroesTerminais['names'][$i]] = $_fmt;
					}
	
					$writer->writeSheet($_poroesTerminais,'PoroesTerminais', $headerPoroesTerminais);
	
				}

			} else {
				$writer->writeSheet($_atendimentos,'Atendimentos');

				if( $kwExtFiles != "n" ) {
					$writer->writeSheet($_produtos,'Produtos');
					$writer->writeSheet($_terminais,'Terminais');
					$writer->writeSheet($_poroesTerminais,'PoroesTerminais');
				}				
			}

			$fname = $cache->getNewFName($userFilename.".xlsx");
			
			// creates XLSX file in cache folder
			$writer->writeToFile($fname);

			// set response code - 200 OK
			http_response_code(200);
			$writer->downloadFile($fname, $userFilename."_".(microtime(true)*10000).".xlsx" );

			//echo json_encode( array( "Sent" => $userFilename.".xlsx (".filesize($fname)." bytes)" ) );

			exit;
			//echo "Sent $userFilename (".filesize($fname)." bytes)";
			//$_records = [ ['2021-04-20', 1, 27, '44.00', 'twig'],['2021-04-21',1, '=C1', '-44.00', 'refund'] ];
			//$styles2_reco$_records array( ['font-size'=>6],['font-size'=>8],['font-size'=>10],['font-size'=>16] );
			//$writer = new XLSXWriter();
			//$writer->setAuthor('Standard Brazil Marine Surveys & Services Ltda');
			//$writer->writeSheet($data1,'atendimentos', $header);
			//$writer->writeSheetRow('MySheet2', $rowdata = array(300,234,456,789), $styles2 );
			//$writer->writeToFile($fname);   // creates XLSX file (in current folder) 
			//echo "Wrote $fname (".filesize($fname)." bytes)";

			// ...or instead of creating the XLSX you can just trigger a
			// download by replacing the last 2 lines with:

			//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			//header('Content-Disposition: attachment;filename="'.$fname.'"');
			//header('Cache-Control: max-age=0');
			//$writer->writeToStdOut();

		} 

		// kwMode = raw
		$_outArr = array();

		if( $kwTitles != "n" ) {
			$_outArr["headers"] = array();

			$_outArr["headers"]["atendimentos"] = array();
			$_outArr["headers"]["produtos"] = array();
			$_outArr["headers"]["terminais"] = array();
			$_outArr["headers"]["poroesTerminais"] = array();

			array_push( $_outArr["headers"]["atendimentos"], $_columns['names']);
			array_push( $_outArr["headers"]["produtos"], $_columnsProds['names']);
			array_push( $_outArr["headers"]["terminais"], $_columnsTerms['names']);
			array_push( $_outArr["headers"]["poroesTerminais"], $_columnsPoroesTerminais['names']);
		}
		
		$_outArr["atendimentos"] = array();
		$_outArr["atendimentos"] = array_merge( $_outArr["atendimentos"], $_atendimentos );

		$_outArr["produtos"] = array();
		$_outArr["produtos"] = array_merge( $_outArr["produtos"], $_produtos );

		$_outArr["terminais"] = array();
		$_outArr["terminais"] = array_merge( $_outArr["terminais"], $_terminais );

		$_outArr["poroesTerminais"] = array();
		$_outArr["poroesTerminais"] = array_merge( $_outArr["poroesTerminais"], $_poroesTerminais );

		$fname = $cache->getNewFName($userFilename.$fext);			
		$data = json_encode($_outArr);

		// set response code - 200 OK
		http_response_code(200);

		// faz download do arquivo enquanto grava-o no cache
		$cache->downloadFile($fname, $userFilename."_".(microtime(true)*10000).$fext, $data );

		exit;		
		break;
	}
	case "POST": { // New
		$_out = array();
		$_out["method"] = $requestMethod;
		
		$atdId = $atendimento->getNewAtendimento();
		$objAtd = json_decode(file_get_contents('php://input'),true);

		$_out["atdId"] = $atdId;

		if( (int)$atdId > 0 ) {
			$_out["err_code"] = 0;
			$objAtd["atdId"] = $atdId;

			$_out["objAtd"] = $objAtd;
	
			$_out["inserted"] = $atendimento->insertNew($objAtd);

			// set response code - q00
			http_response_code(200);

		} else {
			$_out["err_code"] = -1;
			$_out["error"] = "create new atendimento.";
			
			// set response code - 404
			http_response_code(500);
		}

		echo json_encode( $_out );
		break;
	}
	case "PUT": { // update
		$objAtd = json_decode(file_get_contents('php://input'),true);

		$_out = array();
		$_out["atdId"] = $objAtd["atdId"];
		
		//$_out["objAtd"] = $objAtd;
		//$_out["dcpFileToDelete"] = $objAtd["dcpFileToDelete"];
		//$_out["changes"] = $objAtd["changes"];
		
		$_out["updated"] = $atendimento->update($objAtd);

		//$_out["lstPoroes"] = json_encode($objAtd["lstPoroes"]);
		//$_out["poroes"] = json_encode($objAtd["poroes"]);

		if( $_out["updated"]["err_code"] == 0 ) {
			// set response code - 200 OK
			http_response_code(200);
			echo json_encode( $_out );
		} else {
			$_out["error"] = "invalid return from query.";
	
			http_response_code(200);
			echo json_encode( $_out );
		}
		break;
	}
	default: {
		$_out = array();
		
		$_out["method"] = $requestMethod;
		$_out["err_code"] = -1;
		$_out["error"] = "invalid requestMethod.";
		
		// set response code - 404
		http_response_code(404);
		echo json_encode( $_out );
		break;
	}
}

/*
//$win2Open = 'planos/plano_de_carga'.$atendimento[$i]->atendimento_id;
//$fileCount = count(glob($win2Open.".*"));
//if( isset($files2Delete) ) {
//	foreach( $files2Delete as $filetype ) {
//		unlink( "planos/plano_de_carga".$atendimento_id.".".$filetype );
//	}
//}
//			
//if($_FILES['planodecarga']['name'] != '' ) {
//	$newFileName = "planos/plano_de_carga".$atendimento_id.strtolower(substr($_FILES['planodecarga']['name'], strrpos($_FILES['planodecarga']['name'], ".")) );
//	move_uploaded_file($_FILES['planodecarga']['tmp_name'], $newFileName );
//}

//$filelist = glob('planos/plano_de_carga'.$atendimento_id.'.*' );
//
//foreach( $filelist as $fileitem ) {
//	if( preg_match('/\.(.*)$/', $fileitem, $match) === 1 ) {
//		
//		$filetype = $match[count($match)-1];
//		$outstr = '<input type="checkbox" name="files2Delete[]" value="'.$filetype.'"';
//		$outstr = $outstr.'>&#32;'.$filetype.'&#32;&#124;&#32;';
//		echo $outstr;
//	}
//}
//



		//if( $kwMeta != "" && $kwMeta != "n" ) {
		//	$_meta = array();
		//
		//	$_meta["atendimentos"] = array();
		//	$_meta["produtos"] = array();
		//	$_meta["terminais"] = array();
		//	$_meta["poroesTerminais"] = array();
		//
		//	$_meta["atendimentos"]["recs"] = $num;
		//
		//	if( (int)$kwRecsPPage != (int)$num ) {
		//		$_meta["atendimentos"]["pages"] = $utilities->getTotalPages($num, $kwRecsPPage); 
		//		$_meta["atendimentos"]["recsppage"] = $kwRecsPPage;
		//	}
		//
		//	$_meta["atendimentos"]["columns"] = $_columns;
		//	$_meta["produtos"]["columns"] = $_columnsProds;
		//	$_meta["terminais"]["columns"] = $_columnsProds;
		//	$_meta["poroesTerminais"]["columns"] = $_columnsPoroesTerminais;
		//
		//	// set response code - 200 OK
		//	http_response_code(200);
		//	echo json_encode($_meta);
		//	exit;
		//}
*/