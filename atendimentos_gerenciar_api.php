<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=1; include("auth.php"); ?>
<!DOCTYPE html>
<html>

<head>
    <title>Administrative area-Atendimetos</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/padrao.css" type="text/css" rel="stylesheet"/>
    <script type="module" src="js/atend.bundle.js?suid=<?php echo (microtime(true) * 10000.); ?>"></script>
</head>

<body>
    <div id="MainApp"></div>
	<p id="writeToconsole"></p>
</body>

