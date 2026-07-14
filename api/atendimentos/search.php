<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
  
// include database and object files
include_once '../config/core.php';
include_once '../config/database.php';
include_once '../objects/atendimento.php';
  
// instantiate database and atendimento object
$database = new Database();
$db = $database->getConnection();
  
// initialize object
$atendimento = new Atendimento($db);
  
// get keywords
$keywords=isset($_GET["s"]) ? $_GET["s"] : "";
$data1keyword=isset($_GET["data1"]) ? $_GET["data1"] : "";
$data2keyword=isset($_GET["data2"]) ? $_GET["data2"] : "";

// query products
$stmt = $atendimento->search_keywords($keywords);
$num = $stmt->rowCount();
  
// check if more than 0 record found
if($num>0){
  
    // products array
    $_arr=array();
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
  
    // show products data
    echo json_encode($_arr);
}
  
else{
    // set response code - 404 Not found
    http_response_code(404);
  
    // tell the user no products found
    echo json_encode(
        array("message" => "No records found.")
    );
}
?>