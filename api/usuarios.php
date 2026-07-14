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
include_once './objects/usuario.php';
include_once './shared/cacheControl.php';

$cachePath = './cache';
$cachePrefix = 'User';

$cache = new CacheControl($cachePath, $cachePrefix, 5);

// utilities
$utilities = new Utilities();

// $pdo/$cadastro/$tokenPayload já foram criados por shared/apiAuth.php - reusa
// em vez de abrir uma segunda conexão.

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

switch($requestMethod) {
	case "GET": {
		// get keywords
		$kwId=isset($_GET["id"]) ? htmlspecialchars(strip_tags($_GET["id"])) : "";
		$kwEmail=isset($_GET["email"]) ? htmlspecialchars(strip_tags($_GET["email"])) : "";
		$kwMode=isset($_GET["mode"]) ? strtolower(htmlspecialchars(strip_tags($_GET["mode"]))) : "count";
		//$page=page,$kwTitles=titles,$kwRecsPPage=recsppage
		$userFilename = md5($kwId.$kwEmail.$kwMode.$page.$kwRecsPPage.$kwTitles);

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
			$_out["recs"] = $cadastro->count($kwId,$kwEmail);

			$fname = $cache->getNewFName($userFilename.$fext);
			$data = json_encode($_out);

			// set response code - 200 OK
			http_response_code(200);

			// faz download do arquivo enquanto grava-o no cache
			$cache->downloadFile($fname, $userFilename."_".(microtime(true)*10000).$fext, $data );

			exit;			
		}

		$stmt = $cadastro->tb_cadastro($kwId, $kwEmail, $page, $kwRecsPPage);
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
				$_outArr[$row["cadastro_id"]]["nome"] = $row["nome"];
				$_outArr[$row["cadastro_id"]]["email"] = $row["email"];
				$_outArr[$row["cadastro_id"]]["ultimoacesso"] = $row["ultimoacesso"];
				$_outArr[$row["cadastro_id"]]["nivel"] = $row["nivel"];
				$_outArr[$row["cadastro_id"]]["ativo"] = $row["ativo"];
				$_outArr[$row["cadastro_id"]]["redefineSenha"] = $row["redefineSenha"];
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
				
				$_outArr[$row["cadastro_id"]] = array();
								
				array_push($_outArr[$row["cadastro_id"]], $row["nome"] );
				array_push($_outArr[$row["cadastro_id"]], $row["email"] ); 
				array_push($_outArr[$row["cadastro_id"]], $row["ultimoacesso"] ); 
				array_push($_outArr[$row["cadastro_id"]], $row["nivel"] ); 
				array_push($_outArr[$row["cadastro_id"]], $row["ativo"] );
				array_push($_outArr[$row["cadastro_id"]], $row["redefineSenha"] );
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
		
		$id = $cadastro->getNewRecordId();
		$objCadastro = json_decode(file_get_contents('php://input'), true);

		$_out["cadastro_id"] = $id;

		if( (int)$id > 0 ) {
			$_out["err_code"] = 0;
			$objCadastro["cadastro_id"] = $id;

			$_out["objCadastro"] = $objCadastro;
	
			$_out["inserted"] = $cadastro->insertNew($objCadastro, $tokenPayload);

			if( $_out["inserted"]["rc"] == true ) {
				// set response code - 200
				http_response_code(200);
			} else if( $_out["inserted"]["err_code"] == -403 ) {
				http_response_code(403);
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
		$objCadastro = json_decode(file_get_contents('php://input'),true);

		$_out = array();
		$_out["cadastro_id"] = $objCadastro["cadastro_id"];
		$_out["updated"] = $cadastro->update($objCadastro, $tokenPayload);

		if( $_out["updated"]["rc"] == true ) {
			// set response code - 200 OK
			http_response_code(200);
			echo json_encode( $_out );
		} else if( $_out["updated"]["err_code"] == -403 ) {
			http_response_code(403);
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