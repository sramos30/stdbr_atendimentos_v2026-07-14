<?php
  include_once './shared/cacheControl.php';

  $cache = new CacheControl('./cache',1);

  echo "<br>isInCache: ".(($cache->isInCache("Atend0123456789abcdef0123456789abcdf0.xlsx")) ? "Yes" : "No")."<br>";
  echo "<br>getNewFName: ".$cache->getNewFName("Atend0123456789abcdef0123456789abcdf0.xlsx")."<br>";
  
  ?>