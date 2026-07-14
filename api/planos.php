<?php
include_once './config/core.php';
include_once './shared/apiAuth.php';
include_once './shared/utilities.php';

// utilities
$utilities = new Utilities();

ini_set('always_populate_raw_post_data', '-1');

//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', true);

//$target_dir = "uploads/";
//$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
//$uploadOk = 1;
//$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
//// Check if image file is a actual image or fake image
//if(isset($_POST["submit"])) {
//  $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
//  if($check !== false) {
//    echo "File is an image - " . $check["mime"] . ".";
//    $uploadOk = 1;
//  } else {
//    echo "File is not an image.";
//    $uploadOk = 0;
//  }
//}

////$files = array_filter($_FILES['upload']['name']); //something like that to be used before processing files.
//// Count # of uploaded files in array
//$total = count($_FILES['upload']['name']);
//
//// Loop through each file
//for( $i=0 ; $i < $total ; $i++ ) {
//
//  //Get the temp file path
//  $tmpFilePath = $_FILES['upload']['tmp_name'][$i];
//
//  //Make sure we have a file path
//  if ($tmpFilePath != ""){
//    //Setup our new file path
//    $newFilePath = "./uploadFiles/" . $_FILES['upload']['name'][$i];
//
//    //Upload the file into the temp dir
//    if(move_uploaded_file($tmpFilePath, $newFilePath)) {
//
//      //Handle other code here
//
//    }
//  }
//}


//REST API Methods
//
//GET       Retrieve information about the REST API resource
//POST      Create a REST API resource
//PUT       Update a REST API resource
//DELETE    Delete a REST API resource or related component

//http://standardbrazil.com.br/api/planos.php?id=3883

$planosDir = '../planos';

$kwMode=isset($_GET["mode"]) ? strtolower(htmlspecialchars(strip_tags($_GET["mode"]))) : "json";
$kwExt = isset($_GET["ext"]) ? htmlspecialchars(strip_tags($_GET["ext"])) : "*";

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

$kwId=isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if( $kwId < 1 ) {
    http_response_code(404);
    echo json_encode( array("error"=> `"id:{${$_GET["id"]}}" not valid:` ) );
    exit;
}

function getPlanosList($id,$planosDir) {
    $_ret = array();

    $filewc = $planosDir."/plano_de_carga$id.*";
    $filelist = glob( $filewc );
    
    foreach( $filelist as $fileitem ) {
        $filename = basename( $fileitem );

        if( preg_match('/\.([pP][dD][fF])$/', $fileitem, $matches) === 1 ) {
            array_push( $_ret, $matches[count($matches)-1] );
        } else if( preg_match('/\.([xX][lL][sS].*)$/', $fileitem, $matches) === 1 ) {
            array_push( $_ret, $matches[count($matches)-1] );
        }
    }

    return $_ret;
}

$_out = array();
$_out["method"] = $requestMethod;
$_out["atdId"] = $kwId;
$_out["fileExt"] = $kwExt;

$numFilesSent = 0;

if( $kwId > 0 ) {

    switch( $requestMethod ) {
        case "DELETE": {
            $fileList = json_decode(file_get_contents('php://input'),true);

            $_out["filesDelete"] = $fileList;

            foreach( $fileList as $fileExt ) {
                $fileItem = $planosDir.'/plano_de_carga'.$kwId.".".$fileExt;
                $_out["rc-".$fileExt] = unlink($fileItem);
            }

            $_out["fileList"] = getPlanosList($kwId,$planosDir);

            http_response_code(200);
            echo json_encode($_out);
            break;
        }
        case "GET": {
			$filewc = $planosDir."/plano_de_carga$kwId.$kwExt";
            $filelist = glob( $filewc );
            $result = array();
			
			if( $kwMode == "raw" ) {
				if( sizeof($filelist) > 0 ) {
					$fileitem = $filelist[0];
					$fnuser = basename($fileitem);
                    $content_type = "";

					if( preg_match('/\.([pP][dD][fF])$/', $fileitem, $matches) === 1 ) {
						//array_push( $result, $fnuser );
						$content_type = "application/pdf";
					} else if( preg_match('/\.([xX][lL][sS])$/', $fileitem, $matches) === 1 ) {
						//array_push( $result, $fileitem );
						$content_type = "application/vnd.ms-excel";
					} else if( preg_match('/\.([xX][lL][sS][bB])$/', $fileitem, $matches) === 1 ) {
						$content_type = "application/vnd.ms-excel.sheet.binary.macroEnabled.12";
					} else if( preg_match('/\.([xX][lL][sS][xX])$/', $fileitem, $matches) === 1 ) {
						$content_type = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
					} else if( preg_match('/\.([xX][lL][sS][mM])$/', $fileitem, $matches) === 1 ) {
						$content_type = "application/vnd.ms-excel.sheet.macroEnabled.12";
					}

                    $utilities->downloadFile( $fileitem, $fnuser, $content_type );
				} else {
					array_push( $result, $filewc );
					http_response_code(400);
				}
			} else {
				foreach( $filelist as $fileitem ) {
					$filename = basename( $fileitem );

					if( preg_match('/\.([pP][dD][fF])$/', $fileitem, $matches) === 1 ) {
						array_push( $result, $matches[count($matches)-1] );
					} else if( preg_match('/\.([xX][lL][sS].*)$/', $fileitem, $matches) === 1 ) {
						array_push( $result, $matches[count($matches)-1] );
					}
				}

				http_response_code(200);
                echo json_encode($result);
			}		
            break;
        }
        case "POST": 
        case "PUT": {
            $numFiles = count($_FILES['files']['name']);

            //$_out["files"] = json_encode($_FILES, JSON_FORCE_OBJECT );
            $_out["files.count"] = count($_FILES['files']['name']);

            for( $i=0 ; $i < $numFiles ; $i++ ) {
                $file_error = $_FILES['files']['error'][$i];
                $file_size = $_FILES['files']['size'][$i];
                $file_type = $_FILES['files']['type'][$i];

                if( $file_error == 0 && $file_size > 0 ) {
            
                    $file_name = $_FILES['files']['name'][$i];
                    $file_tmp = $_FILES['files']['tmp_name'][$i];
            
                    $tmp = explode('.', $_FILES['files']['name'][$i]);
                    $file_ext = strtolower(end($tmp));
            
                    $file = $planosDir . "/plano_de_carga". $kwId . "." . $file_ext;
                    $_out["file_name-".$i] = $file;
                    $_out["file_tmp-".$i] = $file_tmp;
                    $_out["file_type-".$i] = $file_type;

                    $_out["move_uploaded_file rc-".$i] = move_uploaded_file($file_tmp, $file);
                    //copy($file_tmp, $file);
                    $numFilesSent += 1;
                } else {
                    $_out["file_error-".$i] = $file_error;
                    $_out["file_size-".$i] = $file_size;
                    $_out["file_type-".$i] = $file_type;
                }
            }

            $_out["numFilesSent"] = $numFilesSent;

            if( $numFilesSent > 0 ) {
                http_response_code(200);
            }else {
                $_out["error"] = "no valid files received!";
                http_response_code(500);
            }

            $_out["fileList"] = getPlanosList($kwId,$planosDir);

            echo json_encode( $_out );
            break;
        }
        default: {
            http_response_code(404);
            echo json_encode( array("error"=> '"action" invalid (get|put|delete):') );
            break;
        }
    }
}

die();
?>