<?php
  function exception_handler(Throwable $exception) {
      echo json_encode( array("error" => $exception->getMessage()." @".
      $exception->getFile()."(".$exception->getLine().")" ) );
      die();
  }

  function exceptions_error_handler($severity, $message, $filename, $lineno) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }

  set_error_handler('exceptions_error_handler');
  set_exception_handler('exception_handler');
?>
