<?php

// show error reporting
//ini_set('display_errors', 0);
//error_reporting(E_ALL);
//dirname(__FILE__) . '/../config.php'
//realpath()

include_once dirname(__FILE__).'/../../exceptions.php';

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

$config_path = dirname(__FILE__).'/../../';

$config = array();

$config_filename = "$config_path"."dbstdbrz2.ini";
$config_dev_filename = "$config_path".".dev-env";

if ( file_exists($config_dev_filename) ) {
    $env = parse_ini_file($config_dev_filename);
    
    if( isset($env) ) {
        $config = $env;
    }
} else { 
    $env = parse_ini_file($config_filename);
    
    if( isset($env) ) {
        $config = $env;
    }
}


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$kwTitles=isset($_GET["titles"]) ? strtolower(htmlspecialchars(strip_tags($_GET["titles"]))) : "s";

// set number of records per page
$kwRecsPPage=isset($_GET["recsppage"]) ? (int)$_GET["recsppage"] : 500;

//$cacheTimeToLive = (1); // 1s

?>
