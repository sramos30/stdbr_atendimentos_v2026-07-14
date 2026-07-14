<?php

class Utilities{

    public $range = 1; // range of links to show
    
    public function getTotalPages(&$total_rows, &$records_per_page) {

        if( $total_rows < 1 )
            $total_rows = 1;

        if( $records_per_page > $total_rows )
            $records_per_page = $total_rows;
    
        // count all products in the database to calculate total pages
        $total_pages = ceil($total_rows / $records_per_page);

        return $total_pages;
    }

    public function getColumnsMeta( $stmt ) {
        $_columns = array();
        $_columns['names'] = array();
        $_columns['meta'] = array();

        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $colMeta = $stmt->getColumnMeta($i);

            array_push( $_columns['names'], $colMeta['name'] );

            $_col = array();

            $_type = $colMeta['native_type'];
            switch( $_type )
            {
                case "NEWDECIMAL":
                    $_type = "DECIMAL";
                    break;
                case "VAR_STRING":
                    $_type="STRING";
                    break;
            }

            $_col['type'] = $_type;
            $_col['len'] = $colMeta['len'];
            $_col['precision'] = $colMeta['precision'];
            

            array_push( $_columns['meta'], $_col );
        }

        return $_columns;
    }
    
    public function getPaging($page, $total_rows, $records_per_page, $page_url){
        // paging array
        $paging_arr=array();
        $paging_arr['pages']=array();
        $page_count=0;

        $total_pages = $this->getTotalPages($total_rows, $records_per_page);

		$paging_arr['recsppage'] = $records_per_page;
		
        if( $total_pages > 1 ) {
            // button for first page
            $paging_arr["first"] = $page>1 ? "{$page_url}page=1" : "";
  
            // display links to 'range of pages' around 'current page'
            $initial_num = $page - $this->range;
            $condition_limit_num = ($page + $this->range)  + 1;
          
            for($x=$initial_num; $x<$condition_limit_num; $x++){
                // be sure '$x is greater than 0' AND 'less than or equal to the $total_pages'
                if(($x > 0) && ($x <= $total_pages)){
                    $paging_arr['pages'][$page_count]["page"]=$x;
                    $paging_arr['pages'][$page_count]["url"]="{$page_url}page={$x}";
                    $paging_arr['pages'][$page_count]["current_page"] = $x==$page ? "yes" : "no";
    
                    $page_count++;
                }
            }
        
            // button for last page
            $paging_arr["last"] = $page<$total_pages ? "{$page_url}page={$total_pages}" : "";
        }

        $paging_arr["#pages"]=$total_pages;
        $paging_arr["#recs"]=$total_rows;
  
        // json format
        return $paging_arr;
    }
 
    function utf8_to_iso8859_1(string $string): string {
        $s = (string) $string;
        $len = \strlen($s);
    
        for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
            switch ($s[$i] & "\xF0") {
                case "\xC0":
                case "\xD0":
                    $c = (\ord($s[$i] & "\x1F") << 6) | \ord($s[++$i] & "\x3F");
                    $s[$j] = $c < 256 ? \chr($c) : '?';
                    break;
    
                case "\xF0":
                    ++$i;
                    // no break
    
                case "\xE0":
                    $s[$j] = '?';
                    $i += 2;
                    break;
    
                default:
                    $s[$j] = $s[$i];
            }
        }
    
        return substr($s, 0, $j);
    }

    public function toAscii($str) {
        $asciiTable = "";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\x61\x61\x61\x61\x61\x61\x61\x63\x65\x65\x65\x65\x69\x69\x69\x69";
        $asciiTable .= "\x64\x6e\x6f\x6f\x6f\x6f\x6f\x78\x30\x75\x75\x75\x75\x79\xa6\x62";
        $asciiTable .= "\x61\x61\x61\x61\x61\x61\x61\x63\x65\x65\x65\x65\x69\x69\x69\x69";
        $asciiTable .= "\xa6\x6e\x6f\x6f\x6f\x6f\x6f\xa6\x30\x75\x75\x75\x75\x79\xa6\x79";

        //$str = utf8_decode($str);
        $str = iconv('UTF-8', 'ISO-8859-1', $str); 

        $ret = "";

        //print_r( "<br>".$str."<br>");

        for( $i=0; $i<strlen($str); $i++ ) {
            $v = ord($str[$i]);

            if( $v < 0x20 )
                $chr = 0xa6;
            else if( $v > 0x40 && $v < 0x5b )
                $chr = chr( $v+0x20 );
            else if( $v > 0x7f )
                $chr = $asciiTable[$v-0x80];
            else 
                $chr = $str[$i];

            //print_r( "str[$i]:$str[$i] ($v), chr:$chr <br>" );

            if( $chr != "\xa6" )
                $ret .= $chr;
        }

        return $ret;
    }

    public function toTagId($str) {
        $asciiTable = "";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\x41\x41\x41\x41\x41\x41\x41\x43\x45\x45\x45\x45\x49\x49\x49\x49";
        $asciiTable .= "\x44\x4e\x4f\x4f\x4f\x4f\x4f\x58\x30\x55\x55\x55\x55\x59\xa6\x42";
        $asciiTable .= "\x41\x41\x41\x41\x41\x41\x41\x43\x45\x45\x45\x45\x49\x49\x49\x49";
        $asciiTable .= "\xa6\x4e\x4f\x4f\x4f\x4f\x4f\xa6\x30\x55\x55\x55\x55\x59\xa6\x59";

        $str = iconv('UTF-8', 'ISO-8859-1', $str); 

        $ret = "";

        //print_r( "<br>".$str."<br>");

        for( $i=0; $i<strlen($str); $i++ ) {
            $v = ord($str[$i]);
            $chr = "\xa6";

            if( ($v >= 0x30 && $v <= 0x39) || ( $v >= 0x41 && $v <= 0x5a ) )
                $chr =  chr($v);
            else if( $v >= 0x61 && $v <= 0x7a )
                $chr =  chr($v-0x20);
            else if( $v > 0x7f ) 
                $chr = $asciiTable[$v-0x80];

            //print_r( "str[$i]:$str[$i] ($v), chr:$chr <br>" );

            if( $chr != "\xa6" )
                $ret .= $chr;
        }

        return $ret;
    }

    public function toTags($str) {
        $arrTags = array(); 

        foreach (explode(",", $str) as $_tag) {
            $tag = $this->toTag($_tag);

            if( !in_array($tag,$arrTags) )
                array_push( $arrTags, $tag);
        }

        return implode(",", $arrTags);
    }

    public function toTag($str) {
        $asciiTable = "";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6\xa6";
        $asciiTable .= "\x61\x61\x61\x61\x61\x61\x61\x63\x65\x65\x65\x65\x69\x69\x69\x69";
        $asciiTable .= "\x64\x6e\x6f\x6f\x6f\x6f\x6f\x78\x30\x75\x75\x75\x75\x79\xa6\x62";
        $asciiTable .= "\x61\x61\x61\x61\x61\x61\x61\x63\x65\x65\x65\x65\x69\x69\x69\x69";
        $asciiTable .= "\xa6\x6e\x6f\x6f\x6f\x6f\x6f\xa6\x30\x75\x75\x75\x75\x79\xa6\x79";

        //$str = utf8_decode($str);
        $str = iconv('UTF-8', 'ISO-8859-1', $str); 

        $ret = "";

        //print_r( "<br>".$str."<br>");

        for( $i=0; $i<strlen($str); $i++ ) {
            $v = ord($str[$i]);

            if( $v >= 0x40 && $v <= 0x5a )
                $chr = chr( $v+0x20 );
            else if( $v <= 0x2f 
                || ($v >= 0x3a && $v <= 0x40) 
                || ($v >= 0x5b && $v <= 0x60) 
                || ($v >= 0x7b && $v <= 0x7f) )
                $chr = "\xa6";
            else if( $v > 0x7f )
                $chr = $asciiTable[$v-0x80];
            else 
                $chr = $str[$i];

            //print_r( "str[$i]:$str[$i] ($v), chr:$chr <br>" );

            if( $chr != "\xa6" )
                $ret .= $chr;
        }

        return $ret;
    }

    public function debug_to_console($data) {
        $output = $data;
        if (is_array($output))
            $output = implode(',', $output);
    
        echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from 
     * $params are are in the same order as specified in $query
     *
     * @param string $query The sql query with parameter placeholders
     * @param array $params The array of substitution parameters
     * @return string The interpolated query
     */
    public function interpolateQuery($query, $params) {
        $keys = array();

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:'.$key.'/';
            } else {
                $keys[] = '/[?]/';
            }
        }

        $query = preg_replace($keys, $params, $query, 1, $count);

        trigger_error('replaced '.$count.' keys');

        return $query;
    }

    public function downloadFile( $filename, $userfname, $contentType="application/json" ) {

        $fp = fopen('php://memory','wb');

        if (!$fp) {
            return false;
        }
    
        $data = file_get_contents($filename);
        $size = strlen($data);
        fclose($fp);

        //fwrite( $fp, $data );
        
        header('Content-type: '.$contentType);
        //header('Content-Disposition: attachment; filename="'.$userfname.'"');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T' , time() ));
        header('Content-Length: '.$size);
        
        echo $data;

        //while( ob_get_level() ) {
        //    ob_end_clean();
        //}
        //    
        //fseek($fp,0);
        //fpassthru( $fp );
        //
        //fclose($fp);

        return true;
    }

    public function array2Str( $arrIn ) {

		if( gettype($arrIn) === "array" ) {
			$strOut = "[";
			foreach ($arrIn as $key => $value){
				if( strlen($strOut) > 1 )
					$strOut .= ", ";	
				$strOut .= "$key=" . $this->array2Str($value);
			}

			$strOut .= "]";
		} else {
			$strOut = $arrIn;
		}

        return $strOut;
    }

    //$strOut .= "{$key}=". this->array2Str($value);

    // verify if the fname is in the cache and 
   //public function isInCache($cacheDir, $fname, $ttl=5 ) {
   //    if ( !file_exists($cacheDir) ) {
   //        mkdir($cacheDir, 0770);
   //        return FALSE;
   //    }
   //    
   //    // clear cache
   //    $filelist = glob( $cacheDir."/*(*)" );

   //    if( count($filelist) == 0 ) {
   //        return FALSE;
   //    }

   //    $actualTs = idate("U", time())-$ttl;
   //    
   //    //==>debug
   //    //$actualTs = 1675361550-$ttl;

   //    $retfname = "";

   //    foreach ($filelist as $fileinfo) {
   //        //==>debug
   //        //echo "fileinfo:".$fileinfo."<br>";

   //        if ( preg_match('/.+\((\d+)\)*$/', $fileinfo, $match) ) {
   //            $ts = number_format($match[1],0,"","");
   //            
   //            if( $ts < $actualTs ) {
   //                //==>debug
   //                //echo "$ts < $actualTs<br>";
   //                unlink($fileinfo);
   //            } else {
   //                //==>debug
   //                //echo "$ts >= $actualTs<br>";

   //                $s2 = stristr($fileinfo,$fname);
   //                $fname2 = substr($s2,0,strlen($fname));

   //                //==>debug
   //                //echo "s2:".$s2."<br>";
   //                //echo "fname2:".$fname2."<br>";

   //                if( strcasecmp($fname,$fname2) == 0 ) {
   //                    //==>debug
   //                    //echo "strcasecmp('$fname','$fname2') == 0<br>";
   //                    
   //                    $retfname = $fileinfo;
   //                }
   //            }
   //        }
   //    }          
	//
   //    if( strlen($fexists) > 0 ) {
   //        return $fexists;
   //    }else {
   //        return $fname."(".idate("U", time()).")";
   //    }

   //}
}
?>
