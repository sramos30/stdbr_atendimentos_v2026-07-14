<?php include_once "conecta.php"; $refid=basename($_SERVER['PHP_SELF']); $permissao=2; include("auth.php"); ?>

<!DOCTYPE html>
<html>

<head>
    <title>Area Administrativa-Produtos</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script type="module" src="js/prod.bundle.js?suid=<?php echo (microtime(true) * 10000.); ?>"></script>
</head>

<body>
    <div id="MainApp"></div>
    <p id="writeToconsole"></p>
</body>

