import sodium from 'libsodium-wrappers';

// Login via ECDH+AEAD (libsodium): protege a troca de credenciais no login em
// si, camada adicional sobre o mecanismo de token (que já é seguro em trânsito
// via TLS) - defesa em profundidade contra logs/proxies intermediários. Nenhuma
// lógica de autorização aqui - só monta/desmonta o envelope cifrado; quem
// valida e decide tudo é Cadastro::processarLogin() no backend.

function b64(bytes) {
  return sodium.to_base64(bytes, sodium.base64_variants.ORIGINAL);
}

function unb64(str) {
  return sodium.from_base64(str, sodium.base64_variants.ORIGINAL);
}

async function buscarChaveServidor() {
  const resp = await fetch('login_chave.php');

  if (!resp.ok) {
    throw new Error('Não foi possível obter a chave do servidor.');
  }

  return resp.json();
}

async function tentarLogin(email, senha, jaTentouNovamente) {
  await sodium.ready;

  const chaveServidor = await buscarChaveServidor();
  const serverPublicKey = unb64(chaveServidor.publicKey);

  const parCliente = sodium.crypto_kx_keypair();
  const { sharedRx, sharedTx } = sodium.crypto_kx_client_session_keys(
    parCliente.publicKey, parCliente.privateKey, serverPublicKey
  );

  const nonceRequisicao = sodium.randombytes_buf(sodium.crypto_aead_xchacha20poly1305_ietf_NPUBBYTES);

  const payload = {
    email: email,
    senha: senha,
    senhaNova: null,
    ts: Math.floor(Date.now() / 1000),
    nonce: b64(sodium.randombytes_buf(16)),
  };

  // aad = kid+clientPublicKey, recalculado (nunca transmitido) - o servidor faz
  // o mesmo cálculo a partir dos campos já presentes no envelope.
  const aad = sodium.from_string(chaveServidor.kid + '|' + b64(parCliente.publicKey));

  const ciphertext = sodium.crypto_aead_xchacha20poly1305_ietf_encrypt(
    sodium.from_string(JSON.stringify(payload)), aad, null, nonceRequisicao, sharedTx
  );

  const envelope = {
    kid: chaveServidor.kid,
    clientPublicKey: b64(parCliente.publicKey),
    aeadNonce: b64(nonceRequisicao),
    ciphertext: b64(ciphertext),
  };

  const resposta = await fetch(window.location.pathname + window.location.search, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(envelope),
  });

  if (resposta.status === 409) {
    if (jaTentouNovamente) {
      throw new Error('Não foi possível autenticar - tente novamente.');
    }
    // Chave expirou entre a busca e o envio - refaz a busca e tenta uma vez.
    return tentarLogin(email, senha, true);
  }

  const envelopeResposta = await resposta.json();

  if (!envelopeResposta.aeadNonce || !envelopeResposta.ciphertext) {
    throw new Error(envelopeResposta.erro || 'Falha na comunicação com o servidor.');
  }

  const planoResposta = sodium.crypto_aead_xchacha20poly1305_ietf_decrypt(
    null, unb64(envelopeResposta.ciphertext), aad, unb64(envelopeResposta.aeadNonce), sharedRx
  );

  return JSON.parse(sodium.to_string(planoResposta));
}

function mostrarErro(mensagem) {
  const areaErro = document.getElementById('errorarea');

  if (areaErro) {
    areaErro.textContent = mensagem;
  }
}

async function onSubmit(evt) {
  evt.preventDefault();

  const inputEmail = document.getElementById('inputEmail');
  const inputSenha = document.getElementById('inputPassword');
  const botao = document.getElementById('btnLogin');

  mostrarErro('');

  if (!inputEmail.value.trim()) {
    inputEmail.focus();
    return;
  }

  if (!inputSenha.value) {
    inputSenha.focus();
    return;
  }

  if (botao) botao.disabled = true;

  try {
    const resultado = await tentarLogin(inputEmail.value.trim(), inputSenha.value, false);

    if (resultado.ok) {
      window.location.href = resultado.next || 'index.php';
    } else {
      mostrarErro(resultado.erro || 'Usuário ou senha inválida.');
    }
  } catch (e) {
    console.error(e);
    mostrarErro('Erro ao tentar autenticar. Tente novamente.');
  } finally {
    if (botao) botao.disabled = false;
  }
}

(function iniciar() {
  const form = document.getElementById('formSignin');

  if (form) {
    form.addEventListener('submit', onSubmit);
  }
})();
