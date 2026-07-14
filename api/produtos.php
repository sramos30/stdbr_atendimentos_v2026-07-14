<?php
//throw new ErrorException($message);

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// include database and object files
include_once './config/core.php';
include_once './shared/apiAuth.php';
include_once './config/database.php';
include_once './shared/utilities.php';
include_once './objects/produto.php';
include_once './shared/cacheControl.php';

$cachePath = './cache';
$cachePrefix = 'Prod';

$cache = new CacheControl($cachePath, $cachePrefix, 5);

// utilities
$utilities = new Utilities();

// instantiate database and product object
$database = new Database($config);
$db = $database->getConnection();

$produto = new Produto($db,$utilities);

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

switch($requestMethod) {
	case "GET": {
		// get keywords
		$kwId=isset($_GET["id"]) ? htmlspecialchars(strip_tags($_GET["id"])) : "";
		$kwNome=isset($_GET["nome"]) ? htmlspecialchars(strip_tags($_GET["nome"])) : "";
		$kwMode=isset($_GET["mode"]) ? strtolower(htmlspecialchars(strip_tags($_GET["mode"]))) : "count";
		//$page=page,$kwTitles=titles,$kwRecsPPage=recsppage
		$userFilename = md5($kwId.$kwNome.$kwMode.$page.$kwRecsPPage.$kwTitles);

		// extensão para o arquivo de resposta
		switch( $kwMode ) {
			case "count": {
				$fext = "_count.json";
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

		// verifica se já está no cache
		$fn = $cache->isInCache($userFilename.$fext);

		if( isset($fn) && strlen($fn) > 0 ) {
			http_response_code(200);
			$cache->downloadFile($fn, $cachePrefix."-".$userFilename."_".(microtime(true)*10000).$fext );
			exit;
		}
	
		// Apenas a qtd de regisros.
		if( $kwMode == "count" ) {
			$_out=array();
			//$_out["error"] = "ok";
			$_out["recs"] = $produto->count($kwId);

			$fname = $cache->getNewFName($userFilename.$fext);
			$data = json_encode($_out);

			// set response code - 200 OK
			http_response_code(200);

			// faz download do arquivo enquanto grava-o no cache
			$cache->downloadFile($fname, $userFilename."_".(microtime(true)*10000).$fext, $data );

			exit;			
		}

		$stmt = $produto->tb_produtos($kwId, $kwNome, $page, $kwRecsPPage);
		$num = 0;

		if( $stmt ) {
			$num = $stmt->rowCount();
		}

		if( $num == 0 ) {
			// set response code - 404 Not found
			http_response_code(404);
			echo json_encode( array("error"=> "no records found!") );
			die();
		} 

		$_columns=$utilities->getColumnsMeta( $stmt );

		// Solicitado formato JSON
		if( $kwMode == "json") {
			$_outArr=array();
			//$_outArr["error"] = "ok";
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$_outArr[$row["produto_id"]]["nome"] = $row["nome"];
				$_outArr[$row["produto_id"]]["descricao"] = $row["descricao"];
				$_outArr[$row["produto_id"]]["tags"] = $utilities->toTags(
					$row["nome"].", ".$row["descricao"].", ".$row["tags"]
				);
			}
		
			$fname = $cache->getNewFName($userFilename.$fext);			
			$data = json_encode($_outArr);

			// set response code - 200 OK
			http_response_code(200);

			// faz download do arquivo enquanto grava-o no cache
			$cache->downloadFile($fname, $userFilename."_".(microtime(true)*10000).$fext, $data );
			
			exit;
		}

		// solicitado format RAW
		$_outArr = array();

		if( $kwTitles != "n" ) {
			$_outArr["headers"] = array();
			
			array_push( $_outArr["headers"], $_columns['names']);
		}

		if($num>0) {
	
			// retrieve our table contents
			// fetch() is faster than fetchAll()
			// http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				
				$_outArr[$row["produto_id"]] = array();
								
				array_push($_outArr[$row["produto_id"]], $row["nome"] );
				array_push($_outArr[$row["produto_id"]], $row["descricao"] );
				array_push($_outArr[$row["produto_id"]], 
					$utilities->toTags($row["nome"].", ".$row["descricao"].", ".$row["tags"])
				);
			}

			$fname = $cache->getNewFName($userFilename.$fext);
			$data = json_encode($_outArr);

			// set response code - 200 OK
			http_response_code(200);

			// faz download do arquivo enquanto grava-o no cache
			$cache->downloadFile($fname, $userFilename."_".(microtime(true)*10000).$fext, $data );
		}

		exit;
	}
	case "POST": { // New
		$_out = array();
		$_out["method"] = $requestMethod;
		
		$id = $produto->getNewRecordId();
		$objProduto = json_decode(file_get_contents('php://input'), true);

		$_out["produto_id"] = $id;

		if( (int)$id > 0 ) {
			$_out["err_code"] = 0;
			$objProduto["produto_id"] = $id;

			$_out["objProduto"] = $objProduto;
	
			$_out["inserted"] = $produto->insertNew($objProduto);

			if( $_out["inserted"]["rc"] == true ) {
				// set response code - 200
				http_response_code(200);
			} else {
				// set response code - 500
				http_response_code(500);
			}

		} else {
			$_out["err_code"] = -1;
			$_out["error"] = "cant create new cadastro.";
			
			// set response code - 404
			http_response_code(500);
		}

		echo json_encode( $_out );
		exit;
	}
	case "PUT": { // update
		$objProduto = json_decode(file_get_contents('php://input'),true);

		$_out = array();
		$_out["produto_id"] = $objProduto["produto_id"];
		$_out["updated"] = $produto->update($objProduto);

		if( $_out["updated"]["err_code"] == 0 ) {
			// set response code - 200 OK
			http_response_code(200);
			echo json_encode( $_out );
		} else {
			$_out["error"] = "invalid return from query.";
			http_response_code(500);
			echo json_encode( $_out );
		}
		exit;
	}
	default: {
		$_out = array();
		
		$_out["method"] = $requestMethod;
		$_out["err_code"] = -1;

		$_out["error"] = "invalid requestMethod.";
		
		// set response code - 404
		http_response_code(404);
		echo json_encode( $_out );
		exit;
	}
}

?>