<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// include database and object files
include_once '../config/core.php';
include_once '../shared/utilities.php';
include_once '../config/database.php';
include_once '../objects/atendimento.php';


// utilities
$utilities = new Utilities();

// instantiate database and product object
$database = new Database();
$db = $database->getConnection();

$atendimento = new Atendimento($db);
  
// get keywords
$kwId=isset($_GET["id"]) ? $_GET["id"] : "";
$kwD1=isset($_GET["d1"]) ? $_GET["d1"] : "";
$kwD2=isset($_GET["d2"]) ? $_GET["d2"] : "";

$total_rows=$atendimento->count($kwId, $kwD1, $kwD2);

$stmt = $atendimento->read($kwId, $kwD1, $kwD2, $from_record_num, $records_per_page);
$num = $stmt->rowCount();

// check if more than 0 record found
if($num>0){
    // products array
    $_arr=array();

    // include paging
    //$page_url="{$home_url}atendimentos/read.php?";
    $page_url="/atendimentos/api/atendimentos/read.php?";

    if( $kwId ) 
        $page_url = $page_url."id={$kwId}&";
    
    if( $kwD1 ) 
        $page_url = $page_url."d1={$kwD1}&";
        
    if( $kwD2 ) 
        "d2={$kwD2}&";

    $paging=$utilities->getPaging($page, $total_rows, $records_per_page, $page_url);
    
    $_arr["paging"]=$paging;

    $_arr["records"]=array();

    // retrieve our table contents
    // fetch() is faster than fetchAll()
    // http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        // extract row
        // this will make $row['name'] to
        // just $name only
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

        array_push($_arr["records"], $_item);
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
        array("message" => "No records found.")
    );
}
