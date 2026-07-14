<?php
// Plumbing puro - toda a lógica de autenticação (ECDH+AEAD, validação de senha,
// emissão de token) vive em Cadastro::processarLogin() (api/objects/usuario.php).
// Este arquivo só: (GET) serve a página HTML; (POST) repassa o corpo bruto da
// requisição pra Cadastro e devolve o resultado já pronto. Nenhum sodium_*,
// nenhuma comparação de senha, nenhuma montagem de payload de token aqui.

include_once dirname(__FILE__).'/api/config/core.php';
include_once dirname(__FILE__).'/api/config/database.php';
include_once dirname(__FILE__).'/api/objects/usuario.php';

// Contexto: acesso direto (sem ctx) só autentica usuário real (tb_cadastro).
// ctx=qr (chegando de uma rota do mundo QR) tenta primeiro tb_usuarios_qr,
// restrito ao atendimento_id informado, e só cai para tb_cadastro se não achar -
// é assim que um usuário real (ex: Viewer) consegue acessar pela mesma rota QR.
$ctx = isset($_GET['ctx']) ? $_GET['ctx'] : '';
$atd = isset($_GET['atd']) ? (int) $_GET['atd'] : 0;
$next = isset($_GET['next']) && strlen($_GET['next']) > 0 ? $_GET['next'] : 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$database = new Database($config);
	$pdo = $database->getConnection();
	$cadastro = new Cadastro($pdo, null);

	$contexto = ['ctx' => $ctx, 'atendimento_id' => $atd, 'next' => $next];
	$resposta = $cadastro->processarLogin(file_get_contents('php://input'), $contexto);

	header('Content-Type: application/json; charset=UTF-8');
	http_response_code($resposta['httpStatus']);
	echo json_encode($resposta['corpo']);
	exit;
}
?>

<!doctype html>
<html lang="en">
	<head>
		<title>Standard Brazil - Administrative area</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<link href="css/bootstrap.min.css" type="text/css" rel="stylesheet" >
		<link href="css/padrao.css" type="text/css" rel="stylesheet"/>
	</head>
	<body >
		<main role="main" class="container" style="width: 400px;">
			<form class="form-signin" name="formSignin" id="formSignin" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
				<p id="errorarea"></p>
					<img class="mb-4" src="imagens/standardbrazilLogo.png" width="100%">
					<p align="center"><?PHP echo $_SERVER['REMOTE_ADDR']; ?></p>
					<label for="inputEmail" class="sr-only">Email address</label>
					<input name="inputEmail" type="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
					<label for="inputPassword" class="sr-only">Password</label>
					<input name="inputPassword" type="password" id="inputPassword" class="form-control" placeholder="Password" required>
					<button class="btn btn-lg btn-primary btn-block" type="submit" id="btnLogin">Sign in</button>
			</form>
		</main>
		<?php include_once('script.html'); ?>
		<script type="module" src="js/login.bundle.js?suid=<?php echo (microtime(true) * 10000.); ?>"></script>
	</body>
</html>
