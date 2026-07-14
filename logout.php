<?php
	include_once dirname(__FILE__).'/tokenAuth.php';

	limparCookieToken();

	ob_start();
	header("Location: login.php");
	ob_end_flush();
	die();
?>
