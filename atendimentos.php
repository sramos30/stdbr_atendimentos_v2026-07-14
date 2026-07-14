<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// include database and object files
include_once './config/core.php';
include_once './shared/utilities.php';
include_once './config/database.php';

include_once './objects/atendimento.php';

// utilities
$utilities = new Utilities();

// instantiate database and product object
$database = new Database();
$db = $database->getConnection();

$atendimento = new Atendimento($db);

// get keywords
$kwId=isset($_GET["id"]) ? htmlspecialchars(strip_tags($_GET["id"])) : "";
$kwFalta=isset($_GET["falta"]) ? htmlspecialchars(strip_tags($_GET["falta"])) : "";
$kwExcesso=isset($_GET["excesso"]) ? htmlspecialchars(strip_tags($_GET["excesso"])) : "";
$kwD1=isset($_GET["d1"]) ? htmlspecialchars(strip_tags($_GET["d1"])) : "1900-01-01";
$kwD2=isset($_GET["d2"]) ? htmlspecialchars(strip_tags($_GET["d2"])) : "2100-12-31";

$kwMeta=isset($_GET["meta"]) ? strtolower(htmlspecialchars(strip_tags($_GET["meta"]))) : "";

$total_rows=$atendimento->count($kwId, $kwFalta, $kwExcesso, $kwD1, $kwD2);

//var_dump( "home_url:$home_url", "page:$page", "recsppage:$recsppage", "total_rows:$total_rows", "kwId:$kwId", "kwD1:$kwD1", "kwD2:$kwD2" );

$stmt = $atendimento->read($kwId, $kwFalta, $kwExcesso, $kwD1, $kwD2, $from_record_num, $recsppage);
$num = $stmt->rowCount();

//var_dump( "num:$num" );

// check if more than 0 record found
if($num>0) {
	
	$_paging="";
    
	//$_arr["paging"]=$paging;
    //$_arr["records"]=array();

	if( $kwMeta ) {
		// include paging
		//$page_url=$home_url."atendimentos.php?";
		$page_url="atendimentos.php?";

		if( $kwId ) 
			$page_url = $page_url."id={$kwId}&";
		
		if( $kwFalta )
			$page_url = $page_url."dif={$kwFalta}&";
		
		if( $kwExcesso )
			$page_url = $page_url."dif={$kwExcesso}&";
		
		if( $kwD1 ) 
			$page_url = $page_url."d1={$kwD1}&";
			
		if( $kwD2 ) 
			$page_url = $page_url."d2={$kwD2}";

		$_paging=$utilities->getPaging($page, $total_rows, $recsppage, $page_url);
	}
    
    $_records=array();

	if( $kwMeta != "s" )
	{
		$_columns=array();

		for ($i = 0; $i < $stmt->columnCount(); $i++) {
			$col = $stmt->getColumnMeta($i);
			$_columns[] = $col['name'];
		}

		array_push($_records, $_columns);

		// retrieve our table contents
		// fetch() is faster than fetchAll()
		// http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			
			$_item=array();

			foreach($row as $col) {
				array_push( $_item, $col );
			}

			array_push($_records, $_item);

			//array_push( $_records, $row );
			// extract row
			// this will make $row['name'] to
			// just $name only
			/* 
				extract($row);
				
				$_item=array(
					"atendimento_id" => $atendimento_id,
					"codAtendimento" => $codAtendimento,
					"data" => $data,
					"navio" => $navio,
					"balanca" => $balanca,
					"arqueacao" => $arqueacao,
					"comando_navio" => $comando_navio,
					"perito_receita" => $perito_receita,
					"outras_partes1" => $outras_partes1,
					"outras_partes1_id" => $outras_partes1_id,
					"outras_partes2" => $outras_partes2,
					"outras_partes2_id" => $outras_partes2_id,
					"outras_partes3" => $outras_partes3,
					"outras_partes3_id" => $outras_partes3_id,
					"excesso" => $excesso,
					"falta" => $falta,
					"diferenca" => $diferenca
				);

				//array_push($_arr["records"], $_item);
				array_push($_records, $_item);
		  	*/
		}
	}
	
	$_arr = array();
	
	if( $kwMeta == "b" ) {
		$_arr["paging"] = $_paging;
		$_arr["records"] = $_records;
	} else if( $kwMeta ) {
		$_arr = $_paging;
	}else {
		$_arr = $_records;
	}
	
	// set response code - 200 OK
	http_response_code(200);

	// show atendimento data in json format
	echo json_encode($_arr);

} else {
  
    // set response code - 404 Not found
    http_response_code(404);
  
    // tell the user no records found
    echo json_encode(
        array("error" => "No records found.")
    );
}
