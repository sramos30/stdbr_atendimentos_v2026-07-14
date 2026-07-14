<?php

// se esta é uma versão mais antiga do PHP
// atribua o velho método $nomedavariavel para
// o novo método _GET e _POST _SESSION etc.:

if (!isset($_GET)) { $_GET = &$HTTP_GET_VARS;}
if (!isset($_POST)) { $_POST = &$HTTP_POST_VARS;}

if (!isset($_SESSION)) { $_SESSION = &$HTTP_SESSION_VARS;}

if (!isset($_SERVER)) { $_SERVER = &$HTTP_SERVER_VARS; }

if (!isset($_ENV)) { $_ENV = &$HTTP_ENV_VARS;}

if (!isset($_COOKIE)) { $_COOKIE = &$HTTP_COOKIE_VARS;}

if (!isset($_FILES)) { $_FILES = &$HTTP_POST_FILES;}

if (!isset($_REQUEST)) { $_REQUEST = &$_GET&$_POST&$_COOKIE&$_FILES;}

// Agora o script irá funcionar como se o
// register globals estive setado como on

if (isset($_GET)) { extract($_GET); }

if (isset($_POST)) { extract($_POST); }

if (isset($_SESSION)) { extract($_SESSION); }

if (isset($_SERVER)) { extract($_SERVER); }

if (isset($_ENV)) { extract($_ENV); }

if (isset($_COOKIE)) { extract($_COOKIE); }

if (isset($_FILES)) { extract($_FILES); }

if (isset($_REQUEST)) { extract($_REQUEST); }

?>
