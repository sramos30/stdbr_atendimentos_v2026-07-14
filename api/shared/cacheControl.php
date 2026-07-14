<?php
include_once './shared/utilities.php';

class CacheControl{
    public $cacheDir;
    public $prefix;
    public $ttl;

    public $utilities;

    public function __construct($cacheDir, $prefix, $ttl=5) {
      $this->cacheDir = $cacheDir;
      $this->prefix = $prefix;
      $this->ttl = $ttl;

      $this->utilities = new Utilities(); 

      //==>debug
      //echo json_encode( array( 
      //  "cacheDir" =>  $this->cacheDir,
      //  "prefix" =>  $this->prefix,
      //  "ttl" =>  $this->ttl,
      //) );
      //die();
    }

    // verify if the fname is in the cache and 
    public function isInCache($fn) {
      $retfn = "";
      $fname = $this->prefix."-".$fn;

      if ( !file_exists($this->cacheDir) ) {
          mkdir($this->cacheDir, 0777);
          return $retfn;
      }
        
      $filelist = glob( $this->cacheDir."/*(*)" );

      if( count($filelist) == 0 ) {
        return $retfn;
      }

      $actualTs = idate("U", time())-$this->ttl;
        
      //==>debug
      //$actualTs = 1675361550-$this->ttl;

      foreach ($filelist as $fileinfo) {
        //==>debug
        //echo "fileinfo:".$fileinfo."<br>";

        if ( preg_match('/.+\((\d+)\)*$/', $fileinfo, $match) ) {
          $ts = number_format($match[1],0,"","");
              
          if( $ts < $actualTs ) {
            //==>debug
            //echo "$ts < $actualTs<br>";
            unlink($fileinfo);
          } else {
            //==>debug
            //echo "$ts >= $actualTs<br>";

            $s2 = stristr($fileinfo,$fname);
            $fname2 = substr($s2,0,strlen($fname));

            //==>debug
            //echo "s2:".$s2."<br>";
            //echo "fname2:".$fname2."<br>";

            if( strcasecmp($fname,$fname2) == 0 ) {
              //==>debug
              //echo "strcasecmp('$fname','$fname2') == 0<br>";
              
              $retfn = $fileinfo;
            }
          }
        }
      }          
      
      //==>debug
      //if( strlen($retfn) ) {
      //  echo json_encode( array( 
      //    "retfn" => $retfn,
      //  ) );
      //  die();
      //}
      return $retfn;
  }

  public function getNewFName($fname) {
    $fn = $this->isInCache($fname);
    
    if( isset($fn) && strlen($fn) > 0 ) {
      return $fn;
    }else {
      $fn = $this->cacheDir."/".$this->prefix."-".$fname."(".idate("U", time()).")";
      return $fn;
    }
  }

	public function downloadFile( $filename, $userfname, $data="", $contentType="application/json" ) {

    if( isset($data) && strlen($data) > 0 ) {
      $fp = fopen($filename, "w");
      if (!$fp) {
        return false;
      }

      fwrite($fp, $data);
    }

    $this->utilities->downloadFile( $filename, $userfname, $contentType);

		return true;
	}
}

?>
