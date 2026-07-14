<?php
    function strtourl($str)
	{
        $text = strtr(trim(html_entity_decode($str)),
            'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ-',
            'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby ');

        return preg_replace('/([^.a-z0-9\-]+)/i', '-', strtolower($text));
    }
    
    function my_Sql_regcase($str)
    {

		$res = "";

		$chars = str_split($str);
		foreach($chars as $char){
			if(preg_match("/[A-Za-z]/", $char))
				$res .= "[".mb_strtoupper($char, 'UTF-8').mb_strtolower($char, 'UTF-8')."]";
			else
				$res .= $char;
		}

		return $res;
	}

	function date2mysql($date){

		if( strchr($date,'/') >= 0 )  
			$splitArray = explode("/",$date);
		else if( strchr( $date, '-') >= 0 )
			$splitArray = explode("-",$date);
		else if( strchr( $date, '.') >= 0 )
			$splitArray = explode(".",$date);

		$newDate = $splitArray[2] . "-" . $splitArray[1] . "-" . $splitArray[0];  
		return $newDate; 
	}  
	
	// $expected=array('username','age','city','street');
	function get_POST_array( array $expected ) {
		foreach($expected as $key){
			if(!empty($_POST[$key])){
				${$key}=$_POST[$key];
			} 
			//else {
			//	${key}=NULL;
			//}
		}
	}
	
	function get_GET_array( array $expected ) {
	foreach($expected as $key){
			if(!empty($_GET[$key])){
				${$key}=$_GET[$key];
			} 
			//else {
			//	${key}=NULL;
			//}
		}
	}
	
	function debug_to_console($data) {
		$output = $data;
		if (is_array($output))
			$output = implode(',', $output);

		echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
	}
	
	function file_get_contents_curl($url) {
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_URL, $url);
		
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data;
	}
	
	//$my_tables = $db->get_results("SHOW TABLES",ARRAY_N);

    //$db->debug();

	//foreach ( $my_tables as $table )
	//{
	//	$db->get_results("DESC $table[0]");
	//	$db->debug();
	//}

	//$rows = $db->query("SELECT * FROM acessos LIMIT 25 OFFSET 0");

	//print_r($rows);
	
	error_reporting(1); ini_set('display_errors', 1);
?>