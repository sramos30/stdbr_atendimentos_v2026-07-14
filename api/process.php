<?php 
//https://github.com/taniarascia/upload
include_once './config/core.php';
include_once './shared/utilities.php';


ini_set('always_populate_raw_post_data', '-1');

// utilities
$utilities = new Utilities();

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

$kwId=isset($_GET["id"]) ? htmlspecialchars(strip_tags($_GET["id"])) : "";

$_out = array();
$_out["error"] = "ok";

$_out["method"] = $requestMethod;
$_out["atdId"] = $kwId;

//$_out['isset($_FILES["files"])'] = isset($_FILES['files']);
//$_out["files"] = json_encode($_FILES);
//$_out["files.count"] = count($_FILES);
//$_out["POST"] = json_encode($_POST);

//$_out["body_input"] = file_get_contents('php://input');

//$files = array_filter($_FILES['upload']['name']); 
//$_out["files"] = json_encode($files);
//$_out["numFiles"] = count($_FILES['upload']['name']);
        
// set response code - 200 OK

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['files'])) {
        $errors = [];
        $path = 'cache/';

	    $extensions = ['pdf', 'xlsx', 'xlsm' ];
		
        $all_files = count($_FILES['files']['tmp_name']);

        $_out["numFiles"] = count($_FILES['files']['tmp_name']);
        $_out["files"] = array();

        for ($i = 0; $i < $all_files; $i++) {  
            $file_name = $_FILES['files']['name'][$i];
            $file_tmp = $_FILES['files']['tmp_name'][$i];
            $file_type = $_FILES['files']['type'][$i];
            $file_size = $_FILES['files']['size'][$i];
            $file_error = $_FILES['files']['error'][$i];
            $tmp = explode('.', $_FILES['files']['name'][$i]);
            $file_ext = strtolower(end($tmp));

            $file = $path . "/plano_de_carga". $kwId . "." . $file_ext;

            array_push($_out["files"], 
                array( 
                    "file_name" => $file_name,
                    "file_tmp" => $file_tmp,
                    "file_type" => $file_type,
                    "file_size" => $file_size,
                    "file_ext" => $file_ext,
                    "file_error" => $file_error,
                    "file" => $file
                 )
            );

            if (!in_array($file_ext, $extensions)) {
                $errors[] = 'Extension not allowed: '.$file_name.' '.$file_type.'('.$file_ext.')';
            }

            if (empty($errors)) {
                move_uploaded_file($file_tmp, $file);
            }
        }

    	if ($errors) 
            $_out["errors"] = json_encode($errors);
    }
}

http_response_code(200);
echo json_encode( $_out );

exit;

?>