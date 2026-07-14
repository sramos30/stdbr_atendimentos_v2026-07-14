<?php
	include_once "exceptions.php";

  // The next two lines are used for debugging ...
	error_reporting(E_ALL);
	ini_set('display_errors', 'on');

	include_once("ez_sql_core.php");
	include_once("ez_sql_mysqli.php");
	include_once("register_globals.php");
	include_once("auxfunctions.php");

	if( file_exists('.dev-env') ) {
		$env = parse_ini_file('.dev-env');
		if( isset($env) ) {
			$config = $env;
		}
	} else {
		$config = parse_ini_file('dbstdbrz2.ini');
	}
	
	//print_r( "$config:" ); var_dump( $config );

	$db = null;
	
	try {
		$db = new ezSQL_mysqli($config['username'],$config['password'],$config['dbname'],$config['hostname']);	
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
	
	//var_dump( "db:", $db );
?>