<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// include database and object files
include_once './config/core.php';
include_once './config/database.php';
include_once './shared/utilities.php';
include_once './objects/usuario.php';

// utilities
$utilities = new Utilities();

// instantiate database and product object
$database = new Database($config);
$db = $database->getConnection();

$usuarios = new Usuario($db,$utilities);

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

$_out = array();

switch($requestMethod) {
	case "GET": {
    $kwId=isset($_GET["id"]) ? htmlspecialchars(strip_tags($_GET["id"])) : "";
    $kwEmail=isset($_GET["email"]) ? htmlspecialchars(strip_tags($_GET["email"])) : "";
    $kwSenha=isset($_GET["senha"]) ? htmlspecialchars(strip_tags($_GET["senha"])) : "";
    $from_record_num=isset($_GET["from"]) ? $_GET["from"] : "1";

    if( strlen($kwSenha) > 0) {
      $_out["verificaSenha"] = $usuarios->verificaSenha($kwEmail,$kwSenha);
    } else {

      $stmt = $usuarios->tb_usuarios($kwId, $kwEmail, $from_record_num, $kwRecsPPage);
      
      $num = $stmt->rowCount();

      $_columns=$utilities->getColumnsMeta( $stmt );

      if( $num == 0 ) {
        // set response code - 404 Not found
        http_response_code(404);
        $_out["err_code"] = -1;
        $_out["error"] = "no records found!";
        echo json_encode( $_out );

        die();
      }

      $_out["data"] = array();

      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        //nome, email, senha, ultimoacesso, nivel
        array_push( $_out["data"], $row);
      }
    }

    // set response code - 200 OK
    http_response_code(200);
    echo json_encode($_out);
    exit;
        
    break;
	}
	case "POST": { // New
    break;
  }
  case "PUT": { // update
    break;
  }
  default: {
		$_out["err_code"] = -1;
		$_out["error"] = "invalid requestMethod.";
		
		// set response code - 404
		http_response_code(404);
		echo json_encode( $_out );

    break;
  }
}

/*
	

} else {

    $_outArr = array();

    if( $kwTitles != "n" )
        array_push( $_outArr, $_columns['names']);

	if($num>0) {
	
		// retrieve our table contents
		// fetch() is faster than fetchAll()
		// http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$_item=array();

			array_push($_item, $row["nome"] );
			array_push($_item, $row["email"] );
			array_push($_item, $row["ultimoacesso"] );
			array_push($_item, $row["nivel"] );
			
			array_push($_outArr, $_item);
		}
	}
    
  // set response code - 200 OK
  http_response_code(200);
  echo json_encode($_outArr);
}
*/

?>