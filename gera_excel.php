	<?PHP
    echo setlocale(LC_TIME,"US");
    
    if( isset($_POST['excel']) ) {

		$url = $_SERVER["SERVER_NAME"].$_SERVER["CONTEXT_PREFIX"]."/api/atendimentos.php?excel=s";

		if( isset($qdata) ) 
			$url.="&d1=".$qdata;

		if( isset($qdata2) ) 
			$url.="&d2=".$qdata2;

		if( isset($_POST['difmenor']) ) 
			$url.="&falta=".$_POST['difmenor'];

		if( isset($_POST['difmaior']) ) 
			$url.="&excesso=".$_POST['difmaior'];

		if( isset($produtos_id) ) {
			$url.="&prods=";

			$first = TRUE;
			foreach ($produtos_id as $id) 
			{
				if($first==FALSE)
					$url.=",";
				$url.=$id;
				$first=FALSE;
			}
		}

		if( isset($terminais_id) ) { 
			$url.="&terms=";

			$first = TRUE;
			foreach ($terminais_id as $id) 
			{
				if($first==FALSE)
					$url.=",";
				$url.=$id;				
				$first=FALSE;
			}
		}

		$fp = fopen('php://memory','wb');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
		curl_exec($ch);
		curl_close($ch);
	
		$size = ftell($fp);

		header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="atendimentos.xlsx"');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T' , time() ));
		header('Content-Length: '.$size);

		while( ob_get_level() ) {
			ob_end_clean();
		}
		fseek($fp,0);
		fpassthru( $fp );

		fclose($fp);

		header('Location: ' . $_SERVER['HTTP_REFERER']);
		die();
	}
    ?>