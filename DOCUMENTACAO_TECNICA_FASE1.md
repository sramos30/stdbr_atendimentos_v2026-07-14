# Documentação Técnica — Sistema de Atendimentos (Standard Brazil)
## Estado da versão ao final da Fase 1 (segurança de autenticação)

Data: 2026-07-14 · Tag git: `v0.1.0-fase1-auth` (commit `d2cc44b`)

Este documento descreve em detalhe o estado técnico atual do sistema depois da
Fase 0 (correções emergenciais) e da Fase 1 (segurança de autenticação:
hash de senha, ECDH+AEAD no login, CRUD administrativo com allowlist). É
complementar ao `CONTEXTO_SESSAO.md` (que registra o histórico de decisões e
o "porquê" de cada uma) — aqui o foco é o "o quê" e o "como", de forma
consolidada.

---

## 1. O Plano Aprovado

Este foi o plano de implementação aprovado antes de qualquer código ser
escrito nesta fase. Reproduzido na íntegra abaixo — o restante deste
documento descreve o resultado real depois de implementado e testado (que
inclui pequenos ajustes de protocolo descobertos durante a implementação,
sinalizados no texto).

> ### Contexto
>
> Depois de fechar a Fase 0 (vulnerabilidades emergenciais) e desenhar o
> modelo de autorização por bitmap `grupos`, ficaram dois problemas reais em
> aberto:
>
> 1. `Cadastro::update()` (`api/objects/usuario.php`) aceitava `UPDATE`
>    dinâmico em qualquer campo do JSON recebido, sem checar quem estava
>    chamando — um usuário autenticado de baixo privilégio conseguia se
>    autopromover a admin.
> 2. Senhas trafegavam e eram armazenadas em texto puro (`WHERE senha =
>    '$x'`), sem cifra em trânsito além do TLS e sem hash em repouso.
>
> Decisões-chave que orientaram o plano: toda operação se baseia no bitmap
> `grupos` do chamador; `senha`/`ultimoacesso` são propriedade exclusiva do
> sistema de login (nunca editáveis via CRUD admin); `redefineSenha` é
> compartilhado (admin escreve, login lê/zera); troca de senha (self-service
> e forçada) deve ser genérica para usuário real (`tb_cadastro`) e QR
> (`tb_usuarios_qr`); todo o escopo de token vira métodos da classe
> `Cadastro` (a forma-alvo da futura interface de middleware); e a troca de
> credenciais no login ganha uma camada adicional de cifra (ECDH+AEAD via
> libsodium), sobre o mecanismo de token que já existia, sem PKI/certificado.
>
> ### Abordagem
>
> **1. Migração de schema** — alarga `senha`/`redefineSenha` para
> `VARCHAR(255)` nas duas tabelas (bcrypt = 60 chars; `tb_cadastro.redefineSenha`
> era `varchar(30)`, pequeno demais), adiciona `tb_usuarios_qr.ultimo_acesso`
> (não existia) e cria `tb_login_replay` (dedup de nonce anti-replay).
> Decisão explícita: não converter `tb_cadastro.ultimoacesso` (varchar(20),
> 15 anos de valores livres incluindo o sentinela `'0000-00-00 00:00:00'`)
> para `DATETIME` — risco desnecessário, sem ganho pedido.
>
> **2. Script de rehash** (`scripts/rehash_senhas.php`, CLI-only) — lê as
> duas tabelas, hasheia (`password_hash`/`PASSWORD_DEFAULT`) todo valor de
> `senha`/`redefineSenha` não vazio que ainda não comece com `$2y$`/`$argon2`,
> idempotente.
>
> **3. `Cadastro` (`api/objects/usuario.php`) — métodos novos e
> `update()`/`insertNew()` corrigidos**:
> - `config($tipo)`: parametrização por tabela (`tb_cadastro`/`tb_usuarios_qr`).
> - `validarSenha($tipo, $identificador, $senhaInformada, $contexto)`:
>   `password_verify()` contra `senha` OU `redefineSenha`; mitigação de
>   timing com hash-dummy quando o usuário não existe.
> - `gerarToken()`/`renovarToken()`: únicos métodos que tocam
>   `ultimo_acesso`/`ultimoacesso`, sempre calculado internamente, nunca
>   passado como parâmetro; best-effort (não derruba a renovação do cookie se
>   o banco falhar).
> - `atualizarSenha($tipo, $usuarioId, $senhaNova, $tokenPayloadChamador)`:
>   self-service, só a própria linha, genérico por tipo.
> - `update($objCadastro, $tokenPayloadChamador)`: exige
>   `GRUPO_GERENCIAR_USUARIOS` checado **dentro da classe**; allowlist
>   (`nome, email, nivel, grupos, cliente, ativo, redefineSenha`); bloqueio
>   redundante explícito de `senha`/`ultimoacesso`/`ultimo_acesso`;
>   `redefineSenha` hasheado antes de gravar.
> - `insertNew()`: mesma exigência de bit; hasheia `redefineSenha` inicial.
> - `autorizar($gruposExigidos)`: único ponto de entrada de autorização —
>   valida assinatura do token (via `tokenAuth.php`), forma do payload e
>   bitmap, tudo num método só. `auth.php`/`apiAuth.php` passam a chamar só
>   isso, nunca `tokenAuth.php` diretamente.
> - `tokenAuth.php` encolhe para só criptografia/transporte genérico
>   (HMAC, cookie) — `GRUPO_*`/`possuiGrupos()` migram para dentro de
>   `Cadastro`.
>
> **4. Endpoints e includes existentes** — `auth.php`/`apiAuth.php` passam a
> chamar só `Cadastro::autorizar()`/`renovarToken()`; `alterar_senha.php`
> generalizado por tipo via `Cadastro::atualizarSenha()`; `api/usuarios.php`
> sem checagem própria de bit (delega inteiramente a `Cadastro`).
>
> **5. ECDH+AEAD no login** — princípio: nenhuma função de segurança roda
> fora do JS do cliente e da classe `Cadastro`; nenhum endpoint toca
> `sodium_*` diretamente. Primitivos: `crypto_kx_*` (handshake, deriva
> chaves rx/tx a partir de X25519 via BLAKE2b) + `crypto_aead_xchacha20poly1305_ietf`
> (AEAD, nonce de 192 bits, seguro para gerar aleatoriamente por mensagem
> sem contador/estado). Arquivo auxiliar de baixo nível `loginKx.php`
> (mesmo padrão de `tokenAuth.php`, chamado só por `Cadastro`): gera/rotaciona
> o par de chaves efêmero do servidor (rotação a cada 15min, janela de graça
> de +15min, estado em `.login_kx_keys.json`), deriva chaves de sessão,
> cifra/decifra. Endpoint `login_chave.php` (público) só chama
> `Cadastro::chavePublicaAtual()`. `login.php` vira plumbing puro — todo o
> protocolo (decifrar, validar `ts`/`nonce`, chamar `validarSenha()`/
> `gerarToken()`, cifrar resposta) fica em `Cadastro::processarLogin()`.
> Anti-replay em duas camadas: timestamp (rejeita se `abs(now-ts) > 60s`) +
> dedup de nonce em `tb_login_replay`. Sempre HTTP 200 quando o envelope é
> bem formado (erro vem no corpo cifrado, não vaza por status); 400/409 só
> para falhas de protocolo antes de tocar dado de usuário.
>
> **6. Front-end** — `webpack/src/login.js` reescrito com
> `libsodium-wrappers` (o antigo usava `forge`+MD5/SHA1, código morto
> desconectado do form atual). O POST nunca é um submit nativo de
> formulário — o form intercepta `submit` com `preventDefault()`, valida
> campos no cliente, e só então cifra e chama `fetch()`. WebSocket foi
> descartado (exigiria processo PHP daemon separado, incompatível com
> hospedagem compartilhada tipo Hostinger, sem necessidade real aqui).
>
> **7. Ordem de implementação** (minimiza janela quebrada): migração de
> schema (aditiva) → métodos novos em `Cadastro` (código morto até aqui) →
> fechar autopromoção → renovação de token via `Cadastro` → chave ECDH →
> **ponto sensível único**: rodar rehash + publicar código que espera hash,
> o mais próximo possível no tempo (não há modo de aceitar texto puro OU
> hash simultaneamente) → login cifrado + front-end + rebuild → testes fim a
> fim.
>
> ### Verificação planejada
>
> Container descartável (`phpmsqlphpadmindocker-laravel:latest`, `php -S
> 0.0.0.0:8090`): seed de usuários de teste → rehash (checar idempotência) →
> simulação de cliente via script Node com `libsodium-wrappers` cobrindo
> login normal, senha errada, senha temporária, troca forçada, login QR,
> replay, regressão de autopromoção, reset de senha por admin,
> `ultimo_acesso` avançando, resiliência sem banco → smoke test manual no
> navegador.

**Ajustes feitos durante a implementação** (o plano evoluiu nestes pontos
específicos, com justificativa técnica — detalhado nas seções seguintes):
- O campo `aad` do envelope cifrado **não é transmitido** — é recalculado
  de forma independente por cliente e servidor a partir de `kid` +
  `clientPublicKey` (ver seção 5).
- A extensão `sodium` do PHP exige o keypair **combinado**
  (`secretKey.publicKey`, 64 bytes) em `sodium_crypto_kx_server_session_keys()`,
  diferente do que a documentação genérica de outras bindings sugere (ver
  seção 9).
- O build ESM de `libsodium-wrappers` não funciona direto com webpack;
  precisou de `resolve.alias` para a variante CommonJS (ver seção 9).
- Um bug de resiliência foi encontrado e corrigido: `atualizarUltimoAcesso()`
  só capturava `PDOException`, não `Error` (ver seção 9).

---

## 2. Contexto de Negócio

Sistema de pesagem/divergência de carga em navios da Standard Brazil,
rodando há **15 anos** sobre uma pilha PHP legada, sem framework
(`ez_sql_*` como wrapper de banco direto, sem ORM). O sistema é mais antigo
que os próprios conceitos de autenticação robusta, flag de cliente e QR
code — todos foram adicionados por cima de um histórico de dados que não os
tinha.

`codAtendimento` (ex: `"PGUA/DS/003/26"`) não está sob controle do sistema e
tem duplicidade conhecida no banco — problema de dados aceito como não
corrigível agora. Todo o design de identificação usa `atendimento_id`
(chave primária real, única) como desempate, nunca `codAtendimento` sozinho.

**Decisão arquitetural que revogou trabalho anterior**: havia uma tentativa
de reescrita completa em Laravel, totalmente abandonada — a direção
escolhida foi evoluir **de dentro do sistema legado atual**,
incrementalmente, sem downtime. Princípio de longo prazo (ainda não
construído): nenhuma camada de aplicação deveria falar direto com SQL — a
visão é um middleware de dados na frente do banco, trocável de motor,
exportando uma interface não-relacional. As classes `Entidade::method()`
(`Cadastro`, `Atendimento`) já são desenhadas como a forma-alvo dessa
interface interna — só o SQL direto por dentro delas é provisório.

---

## 3. Modelo de Dados e Autorização

### 3.1 Duas tabelas de usuário, nunca misturadas

| | `tb_cadastro` | `tb_usuarios_qr` |
|---|---|---|
| Quem | Usuário real/permanente (staff) | Usuário temporário (ex: capitão do navio) |
| Chave | `cadastro_id` | `usuario_qr_id` |
| Identificador de login | `email` | `identificacao` |
| Escopo de dado | `cliente` (dimensão separada) | `atendimento_id` (1:1/poucos) |
| `grupos` | Bitmap completo, qualquer combinação | Sempre `VER`=1 na criação — mas é a **mesma coluna/checagem** que usuário real, não há dois códigos de validação |
| Senha | `senha` + `redefineSenha` (hash bcrypt) | idem |
| Último acesso | `ultimoacesso` (varchar, legado) | `ultimo_acesso` (datetime, nova) |

### 3.2 `grupos` — bitmap de capacidades

Definido em `api/objects/usuario.php` (migrado de `tokenAuth.php` na Fase 1):

```
GRUPO_VER                 = 1
GRUPO_CRIAR                = 2
GRUPO_ALTERAR               = 4
GRUPO_EXCLUIR                = 8
GRUPO_GERENCIAR_CATALOGO      = 16
GRUPO_CADASTRAR_USUARIO_QR     = 32
GRUPO_GERENCIAR_USUARIOS        = 64
```

Migrado do `nivel` antigo (0/1/2 hierárquico): `0→1` (Viewer, só VER),
`1→63` (Operador, tudo exceto gerenciar usuários), `2→127` (Admin, tudo).

### 3.3 `cliente` — dimensão de visibilidade de dado

Totalmente separada do bitmap `grupos` (não confundir: `grupos` = **o quê**
o usuário pode fazer; `cliente` = **qual dado** ele enxerga). Regra: usuário
real vê atendimentos onde `atendimento.cliente == usuario.cliente`. Hoje só
existe `ATEXP` com usuários reais; `cliente` em branco num atendimento é
estado transitório (histórico sem esse conceito), tratado como equivalente
a `ATEXP` — intencional, não bug.

### 3.4 Dois "mundos" de rota

Usuário real acessa livremente as rotas do mundo QR (útil para um operador
testar QR, ou um Viewer acessar via link QR sem precisar de cadastro
temporário). Usuário QR **nunca** acessa rotas do mundo normal. Login com
contexto QR (`?ctx=qr&atd=X`) busca primeiro em `tb_usuarios_qr` (escopado a
esse `atendimento_id`); só cai para `tb_cadastro` se não achar. Login direto
(sem `ctx`) só autentica usuário real.

---

## 4. Arquitetura de Autenticação — Visão Geral

### 4.1 Camadas, de baixo para cima

```
┌─────────────────────────────────────────────────────────────┐
│  Endpoints (login.php, auth.php, apiAuth.php, api/usuarios.php) │
│  → só chamam métodos de Cadastro, nunca tocam banco/cripto direto │
├─────────────────────────────────────────────────────────────┤
│  Cadastro (api/objects/usuario.php)                            │
│  → dono de TODO o escopo de autenticação/autorização/senha      │
│    validarSenha · gerarToken · renovarToken · atualizarSenha      │
│    autorizar · update/insertNew (allowlist) · chavePublicaAtual   │
│    · processarLogin (protocolo ECDH+AEAD completo)                │
├──────────────────────┬──────────────────────────────────────┤
│  tokenAuth.php          │  loginKx.php                          │
│  (baixo nível: HMAC,      │  (baixo nível: primitivos sodium,      │
│  cookie — sem saber o que  │  rotação de chave efêmera —            │
│  tem no payload)             │  sem regra de negócio)                 │
└──────────────────────┴──────────────────────────────────────┘
```

Princípio central (decidido explicitamente durante o design): **nenhum
endpoint HTTP processa lógica de segurança por conta própria** — nem
checagem de bit (`&` bitwise), nem chamada a `sodium_*`, nem comparação de
senha. Tudo isso vive em `Cadastro`, que por sua vez usa dois arquivos
auxiliares puramente técnicos (`tokenAuth.php` para HMAC/cookie,
`loginKx.php` para os primitivos de criptografia de curva elíptica) — nunca
o contrário.

### 4.2 O token (`atd_token`, cookie httponly)

HMAC-SHA256 assinado, autocontido (sem estado no servidor além da
assinatura). Payload:

```json
{
  "usuario_id": 43,
  "tipo": "real",
  "nome": "...",
  "email": "...",
  "cliente": null,
  "grupos": 127,
  "precisaTrocarSenha": false,
  "exp": 1784064563
}
```

(Para `tipo: "qr"`, `email`/`cliente` são substituídos por
`atendimento_id`.) Renovado (nova expiração) a cada requisição autenticada
válida — expiração deslizante. Segredo HMAC em `.dev-env`
(`token_secret=...`), só local.

### 4.3 `Cadastro::autorizar($gruposExigidos)` — o único ponto de entrada

```
1. $payload = obterTokenAtual()      → tokenAuth.php: lê cookie, verifica
                                        assinatura HMAC + expiração
2. payloadValido($payload)?          → forma correta (usuario_id, tipo
                                        ∈ {real,qr}, grupos inteiro)
3. possuiGrupos($payload, $bits)?    → (grupos & bits) === bits
4. Falhou em qualquer passo          → limparCookieToken() + devolve null
   Passou                            → devolve o payload
```

`auth.php` (interface legada `$permissao` 0/1/2, mantida por compatibilidade
com ~30 arquivos) e `api/shared/apiAuth.php` (gate de API, aceita
`$apiAuthGruposExigidos`) chamam só isso — nunca `tokenAuth.php` direto.

---

## 5. Protocolo ECDH + AEAD no Login (em detalhe)

### 5.1 Por que essa camada existe

Camada **adicional** de defesa em profundidade sobre o mecanismo de token
(que já é seguro em trânsito via TLS) — protege contra logs/proxies
intermediários capturando o corpo da requisição em texto claro. Não
substitui TLS. Rejeitou-se PKI/mTLS explicitamente (carga operacional de
manter ciclo de vida de certificado que o cliente final não sustenta) em
favor de chaves efêmeras autocontidas, geradas em tempo real, com
primitivas de biblioteca padrão (libsodium).

### 5.2 Primitivos escolhidos

| Primitivo | Uso | Por quê |
|---|---|---|
| `crypto_kx_*` (X25519 + BLAKE2b) | Handshake cliente/servidor, deriva duas chaves por direção (rx/tx) | Evita HKDF manual e reuso acidental de chave entre os dois sentidos |
| `crypto_aead_xchacha20poly1305_ietf` | Cifra autenticada do payload | Nonce de 192 bits — seguro gerar aleatoriamente por mensagem sem contador/estado (ao contrário de ChaCha20-IETF/AES-GCM comuns, nonce de 96 bits); não depende de suporte a AES-NI em hardware do cliente final |

### 5.3 Descoberta de chave do servidor

`GET /login_chave.php` (público, sem autenticação) → `Cadastro::chavePublicaAtual()`
→ `loginKx.php`:

```json
{"kid": "1784062893-d47d1fbb", "publicKey": "base64...", "exp": 1784064693}
```

### 5.4 Rotação da chave efêmera do servidor (sem `$_SESSION`)

Estado em `.login_kx_keys.json` (raiz, dot-prefixed, gitignored),
array de entradas `{kid, publicKey, secretKey, createdAt, expiresAt}`,
escrito sob `flock()` exclusivo com `rename()` atômico. Rotaciona a cada
**15 minutos**; cada chave permanece válida (para decifrar, não para novas
trocas) por até **30 minutos** desde a criação — janela de graça para
logins iniciados perto da rotação. `kid` não encontrado/expirado → `409
{"erro":"chave_expirada"}`; o cliente refaz a busca e tenta de novo uma
vez.

### 5.5 Envelope da requisição

Corpo do POST (`Content-Type: application/json`):

```json
{
  "kid": "1784062893-d47d1fbb",
  "clientPublicKey": "base64( 32 bytes X25519 do cliente )",
  "aeadNonce": "base64( 24 bytes )",
  "ciphertext": "base64( XChaCha20-Poly1305(payload) )"
}
```

Payload cifrado (antes de cifrar):

```json
{
  "email": "usuario@exemplo.com",
  "senha": "texto digitado no form",
  "senhaNova": null,
  "ts": 1784062900,
  "nonce": "base64( 16 bytes aleatórios )"
}
```

`senhaNova` fica reservado no backend (`processarLogin()` já trata) para um
futuro fluxo de login que já envia senha nova junto — **não exposto na UI
nesta fase**.

### 5.6 Ajuste de protocolo: `aad` recalculado, não transmitido

O design original prescrevia um campo `aad` separado no envelope. Durante a
implementação, percebeu-se que isso permitiria (em tese) que alguém trocasse
`kid`/`clientPublicKey` do envelope sem invalidar um `aad` solto que não
fosse cruzado contra esses campos. A solução mais simples e correta:
**ambos os lados recalculam** o AAD como

```
aad = kid + "|" + base64(clientPublicKey)
```

a partir dos campos que **já estão** no envelope — nunca transmitido à
parte. Isso amarra o ciphertext a `kid`+`clientPublicKey` de forma
criptográfica (a tag de autenticação do AEAD só bate se o AAD usado na
decifragem for byte-a-byte igual ao usado na cifragem) sem precisar confiar
em nada adicional vindo do cliente.

### 5.7 Resposta cifrada

Reutiliza a chave `tx` derivada, nonce **novo e nunca reaproveitado**:

```json
{"aeadNonce": "base64( 24 bytes, novo )", "ciphertext": "base64(...)"}
```

Corpo decifrado, em caso de sucesso:

```json
{"ok": true, "next": "index.php", "precisaTrocarSenha": false}
```

Ou, em caso de falha:

```json
{"ok": false, "erro": "Usuário ou senha inválida"}
```

**Sempre HTTP 200** quando o envelope está bem formado e foi possível
decifrar — o resultado (sucesso/erro) vem só no corpo cifrado, nunca no
código de status (evita vazar informação por status HTTP a um observador
passivo). `400` (envelope malformado) e `409` (`kid` expirado) só ocorrem
**antes** de qualquer dado de usuário ser tocado — são falhas de protocolo,
não de autenticação.

O cookie `Set-Cookie: atd_token=...` continua indo em **header HTTP puro**
— headers não são cifrados por esta camada de aplicação (limitação
documentada, não uma falha do design: a proteção do cookie em trânsito
continua sendo exclusivamente do TLS, como já era antes).

### 5.8 Anti-replay — duas camadas

A rotação de chave sozinha **não basta**: um envelope capturado dentro da
janela de 30 minutos ainda decifraria e reprocessaria como login válido se
reenviado bit a bit.

1. **Timestamp**: `ts` no payload decifrado — rejeita se `abs(now - ts) >
   60` segundos.
2. **Dedup de nonce**: tabela `tb_login_replay(nonce_hash CHAR(64) PRIMARY
   KEY, criado_em DATETIME)`. Antes de cada tentativa: `DELETE FROM
   tb_login_replay WHERE criado_em < NOW() - INTERVAL 2 MINUTE` (auto-limpeza
   barata), depois `INSERT` do hash SHA-256 do `nonce` recebido — colisão de
   chave primária (ou qualquer erro) = replay confirmado, rejeita.

Testado explicitamente: reenviar o **mesmo envelope válido** duas vezes —
primeira aceita, segunda rejeitada, mesmo com o `kid` ainda dentro da
janela de validade.

### 5.9 `Cadastro::processarLogin()` — fluxo completo

```
1. Decodifica o envelope JSON (400 se malformado)
2. Busca a chave do servidor pelo kid (409 se não encontrada/expirada)
3. Deriva [rx, tx] via crypto_kx_server_session_keys (keypair combinado —
   ver seção 9.1)
4. Decifra o payload com rx + aad recalculado (400 se falhar)
5. Valida ts (janela de 60s) e nonce (dedup em tb_login_replay)
6. Se ctx=qr: tenta validarSenha('qr', ...) primeiro; senão/se falhar,
   tenta validarSenha('real', ...)
7. Se autenticado: gerarToken() (seta cookie, atualiza ultimo_acesso)
8. Se veio senhaNova E precisaTrocarSenha: atualizarSenha() + gerarToken()
   de novo (token atualizado sem a flag)
9. Cifra a resposta com tx + aad recalculado, sempre HTTP 200
```

`login.php` em si, depois dessa refatoração, não contém **nenhuma** lógica
de autenticação — só le `php://input`, repassa para
`Cadastro::processarLogin()` e devolve o JSON:

```php
$contexto = ['ctx' => $_GET['ctx'] ?? '', 'atendimento_id' => (int)($_GET['atd'] ?? 0), 'next' => $_GET['next'] ?? 'index.php'];
$resposta = $cadastro->processarLogin(file_get_contents('php://input'), $contexto);
http_response_code($resposta['httpStatus']);
echo json_encode($resposta['corpo']);
```

### 5.10 Fluxo no front-end (`webpack/src/login.js`)

```
1. addEventListener('submit', ...) com preventDefault()
   → nunca deixa o navegador fazer POST nativo de formulário
2. Valida email/senha não vazios no cliente
3. await sodium.ready
4. fetch('login_chave.php') → chave pública + kid do servidor
5. sodium.crypto_kx_keypair() → par efêmero do cliente (só esta tentativa)
6. sodium.crypto_kx_client_session_keys(...) → {sharedRx, sharedTx}
7. Monta payload {email, senha, senhaNova:null, ts, nonce}
8. Cifra com sharedTx + aad = kid + '|' + base64(clientPublicKey)
9. fetch(mesma URL da página, {method:'POST', body: envelope})
   → se 409: refaz passo 4 e tenta de novo (uma vez só)
10. Decifra a resposta com sharedRx
11. ok:true → window.location = next
    ok:false → mostra erro em #errorarea
```

Reescrita completa — o arquivo antigo usava a lib `forge` (MD5/SHA1,
remanescente de uma era pré-token, ligada a um `sessionId` que nem existe
mais) e **estava desconectado do form atual** (procurava um elemento
`#btnLogin` que não existia) — zero risco de regressão real.

---

## 6. Gestão de Senhas

### 6.1 Duas operações, nunca confundidas

| | Trocar senha (self-service) | Resetar senha (admin) |
|---|---|---|
| Quem chama | O próprio usuário, autenticado | Admin com `GRUPO_GERENCIAR_USUARIOS` |
| Método | `Cadastro::atualizarSenha()` | `Cadastro::update()` (campo `redefineSenha`) |
| Escopo | Só a própria linha (`tipo`+`usuario_id` do token batem com o alvo) | Qualquer outro usuário |
| Coluna afetada | `senha` (grava), `redefineSenha` (zera) | `redefineSenha` (grava, hasheada) |
| Genérico por tipo? | Sim — `tb_cadastro` e `tb_usuarios_qr` | Sim, mas hoje só há UI para `tb_cadastro` via `api/usuarios.php` |

`senha` e `ultimoacesso`/`ultimo_acesso` são propriedade **exclusiva** do
sistema de login — bloqueadas explicitamente em `Cadastro::update()`, mesmo
para um chamador com `GRUPO_GERENCIAR_USUARIOS`. `redefineSenha` é a única
coluna compartilhada entre os dois caminhos (admin escreve, login lê/zera)
— código desacoplado, um não chama o outro.

### 6.2 Armazenamento — hash, não texto puro

`password_hash($valor, PASSWORD_DEFAULT)` — no ambiente de teste (PHP
8.3), produz bcrypt (`$2y$`, 60 caracteres). Verificação via
`password_verify()`. Migração de dados existentes: `cli/rehash_senhas.php`
(CLI-only, `php_sapi_name() === 'cli'` obrigatório), idempotente — pula
valores já hasheados (prefixo `$2y$`/`$argon2`) e valores vazios/nulos
(nunca hasheia string vazia, fechando um gap onde `senha=''` poderia
colidir com um POST de senha vazia).

### 6.3 Mitigação de enumeração de usuário / timing

`Cadastro::validarSenha()`: se a linha não é encontrada, ainda roda
`password_verify()` contra um hash-dummy fixo (computado uma vez por
request, via `static` local) — mantém o tempo de resposta comparável entre
"usuário não existe" e "senha errada", prática padrão recomendada junto com
`password_verify`.

### 6.4 Troca de senha forçada (`precisaTrocarSenha`)

Marcada no token quando o login bateu contra `redefineSenha` (não `senha`).
`auth.php` redireciona para `alterar_senha.php` sempre que essa flag está
true, **sem filtrar por tipo de usuário** — por isso não foi necessário
construir uma tela nova para o mundo QR: bastou `validarSenha()` marcar a
flag corretamente para os dois tipos (o que já faz, por construção
genérica) e `alterar_senha.php` chamar `Cadastro::atualizarSenha()` com o
`tipo` do token.

---

## 7. CRUD Administrativo — `Cadastro::update()` / `insertNew()`

### 7.1 A vulnerabilidade original

Antes da Fase 1, `update()` montava um `UPDATE` dinâmico aceitando
**qualquer** campo presente no JSON recebido — incluindo `nivel`/`grupos` —
protegido só por exigir autenticação (qualquer token válido, não uma
capacidade específica). Um usuário Viewer autenticado podia enviar `PUT
/api/usuarios.php {"cadastro_id": <próprio_id>, "grupos": 127}` e virar
admin.

### 7.2 A correção

```php
function update($objCadastro, array $tokenPayloadChamador) {
    if (!$this->possuiGrupos($tokenPayloadChamador, GRUPO_GERENCIAR_USUARIOS)) {
        return ['err_code' => -403, 'rc' => false, 'err_msg' => 'não autorizado'];
    }

    if (array_key_exists('senha', $objCadastro) || array_key_exists('ultimoacesso', $objCadastro)
        || array_key_exists('ultimo_acesso', $objCadastro)) {
        return ['err_code' => -400, 'rc' => false, 'err_msg' => 'campo não permitido'];
    }

    $allowlist = ['nome', 'email', 'nivel', 'grupos', 'cliente', 'ativo', 'redefineSenha'];
    $campos = array_intersect_key($objCadastro, array_flip($allowlist));

    if (array_key_exists('redefineSenha', $campos) && strlen((string)$campos['redefineSenha']) > 0) {
        $campos['redefineSenha'] = password_hash($campos['redefineSenha'], PASSWORD_DEFAULT);
    }
    // ... UPDATE via PDO prepared statement, só sobre os campos da allowlist
}
```

Dois níveis de defesa: a checagem de bit e o bloqueio de `senha`/`ultimoacesso`
acontecem **dentro da classe**, não no endpoint HTTP — `api/usuarios.php`
não faz nenhuma checagem própria, só chama `update()`/`insertNew()` e
traduz o resultado estruturado (`rc`/`err_code`) para o código HTTP (`200`
sucesso, `403` se `err_code === -403`, `500` outro erro).

Efeito colateral positivo da reescrita: o `UPDATE` original construía a
query por concatenação de string com `strip_tags()` (que não escapa aspas —
risco de SQL injection preexistente); a versão nova usa **prepared
statements com parâmetros nomeados** para todos os valores.

`insertNew()` recebeu a mesma exigência de bit (criar usuário é parte do
mesmo CRUD administrativo) e passou a hashear o `redefineSenha` inicial
antes do INSERT.

---

## 8. Referência de Arquivos

### 8.1 Novos

| Arquivo | Papel |
|---|---|
| `loginKx.php` | Primitivos sodium de baixo nível (ECDH+AEAD) — chamado só por `Cadastro` |
| `login_chave.php` | Endpoint público, só chama `Cadastro::chavePublicaAtual()` |
| `cli/rehash_senhas.php` | Script CLI one-shot, migra senha texto-puro → hash |
| `migrations/2026_07_14b_hash_senhas_e_acesso_qr.sql` | Alarga colunas de senha, adiciona `ultimo_acesso` em QR, cria `tb_login_replay` |
| `docker-compose.test.yml` | Controla o container de teste descartável |
| `.login_kx_keys.json` | Estado da chave efêmera do servidor (runtime, gitignored) |

### 8.2 Reescritos por completo

| Arquivo | Mudança principal |
|---|---|
| `api/objects/usuario.php` (`Cadastro`) | Ganhou ~12 métodos novos; `update()`/`insertNew()` corrigidos; passou a ser dono de `GRUPO_*`/`possuiGrupos()` |
| `tokenAuth.php` | Encolhido para só HMAC + cookie; perdeu `GRUPO_*`/`possuiGrupos()` |
| `auth.php` | Delega tudo para `Cadastro::autorizar()`/`renovarToken()` |
| `api/shared/apiAuth.php` | Idem |
| `login.php` | Virou plumbing puro — POST repassa pra `Cadastro::processarLogin()` |
| `webpack/src/login.js` | Reescrito com `libsodium-wrappers` (era `forge`, código morto) |

### 8.3 Ajustados pontualmente

| Arquivo | Mudança |
|---|---|
| `alterar_senha.php` | Chama `Cadastro::atualizarSenha()` em vez de SQL direto — genérico por tipo |
| `api/usuarios.php` | Reusa `$cadastro`/`$pdo` de `apiAuth.php`; `insertNew`/`update` passam `$tokenPayload`; sem checagem própria de bit |
| `webpack/package.json` | + `libsodium-wrappers` |
| `webpack/webpack.config.js` | + `resolve.alias` (ver seção 9.2) |
| `.gitignore` | + `_dev-env`, `dbstdbrz2.ini.main`, `.login_kx_keys.json`, `logs/` |

---

## 9. Bugs Reais Encontrados e Corrigidos

Três problemas concretos apareceram só durante a implementação/teste — não
eram previsíveis a partir do desenho no papel. Documentados aqui em
detalhe porque são armadilhas específicas deste stack (PHP 8.3 + sodium +
webpack) que provavelmente reapareceriam se o trabalho fosse refeito do
zero sem essa memória.

### 9.1 `sodium_crypto_kx_server_session_keys()` espera keypair combinado

A assinatura real desta função na extensão `sodium` do PHP (confirmado via
`ReflectionFunction` em PHP 8.3.32) é:

```php
sodium_crypto_kx_server_session_keys(string $server_key_pair, string $client_key): array
```

— **dois** argumentos, o primeiro sendo o keypair **combinado**
(`secretKey . publicKey`, 64 bytes), não três argumentos com pk/sk
separados como a documentação genérica de outras bindings libsodium
sugeriria. Chamar com 3 argumentos separados falha com `expects exactly 2
arguments, 3 given`. Correção em `loginKx.php`:

```php
$serverKeyPair = $serverSk.$serverPk;  // ordem confirmada empiricamente: sk antes de pk
return sodium_crypto_kx_server_session_keys($serverKeyPair, $clientPublicKeyRaw);
```

(A ordem `sk.pk` foi confirmada comparando `sodium_crypto_kx_keypair()`
contra a concatenação de `sodium_crypto_kx_publickey()`/`secretkey()`.) O
lado JS (`libsodium-wrappers`) **não** tem essa peculiaridade — usa a
assinatura de 3 argumentos separados normalmente.

### 9.2 Build do webpack quebra com `libsodium-wrappers`

O build ESM do pacote (`dist/modules-esm/libsodium-wrappers.mjs`) importa
um `./libsodium.mjs` relativo que **não existe dentro do próprio pacote**
— só existe em `node_modules/libsodium` (dependência separada). Webpack
falha com `Module not found`. Correção: forçar a variante CommonJS via
`resolve.alias`, usando **caminho de arquivo absoluto** (apontar pelo
specifier do pacote, ex. `'libsodium-wrappers/dist/modules/...'`, falha
por esbarrar no campo `exports` do `package.json`, que restringe quais
subcaminhos podem ser importados):

```js
resolve: {
  alias: {
    'libsodium-wrappers$': path.resolve(__dirname, 'node_modules/libsodium-wrappers/dist/modules/libsodium-wrappers.js'),
  },
},
```

### 9.3 Resiliência: `atualizarUltimoAcesso()` derrubava requests com banco fora do ar

O design previa que a atualização de `ultimo_acesso` fosse *best-effort*
(nunca deveria impedir a renovação do cookie/acesso a uma página já
autenticada). A primeira versão só capturava `PDOException`:

```php
try {
    $stmt = $this->conn->prepare(...);
    ...
} catch (PDOException $e) { error_log(...); }
```

Mas quando o banco está inacessível, `Database::getConnection()` devolve
`null` (captura a exceção de conexão internamente) — e chamar
`$this->conn->prepare()` sobre `null` lança um `Error` do PHP (`Call to a
member function prepare() on null`), **não** uma `PDOException`. Esse
`Error` não era capturado, e uma página protegida com cookie válido
quebrava (a aplicação devolvia um JSON de erro, ainda com status 200, em
vez de simplesmente servir a página). Corrigido para capturar
`\Throwable` e adicionar uma guarda explícita:

```php
if (!$cfg || !$this->conn) { return; }
try { ... } catch (\Throwable $e) { error_log(...); }
```

Confirmado com um teste real: apontar `.dev-env` temporariamente para um
hostname de banco inválido e verificar que uma página protegida com cookie
válido continuava respondendo (o erro que sobrava era só do `index.php`
buscando **seu próprio** conteúdo de negócio via `ez_sql`, uma dependência
de banco legítima e não relacionada à autenticação).

---

## 10. Testes Realizados

Metodologia: script Node (`libsodium-wrappers`) simulando um cliente real
(monta o handshake ECDH, cifra o envelope, decifra a resposta) contra o
container de teste descartável rodando o código real via `php -S`. `curl`
puro não serve para simular o cliente (não sabe cifrar), mas foi usado para
testes de transporte/status/resiliência isolados.

| # | Cenário | Resultado esperado |
|---|---|---|
| 1 | Login normal (admin) | `200`, `ok:true`, cookie setado |
| 2 | Senha errada | `200` (não vaza por status), `ok:false`, sem cookie |
| 3 | Página protegida, cookie válido | `200` |
| 4 | Página protegida, sem cookie | `401` |
| 5 | Regressão de autopromoção (viewer → admin) | `403`, valores inalterados no banco |
| 6 | Admin tenta colar `senha` num PUT | Rejeitado mesmo com `GRUPO_GERENCIAR_USUARIOS` |
| 7 | Admin reseta senha de outro via `redefineSenha` | `200`, grava hash (não texto puro) |
| 8 | Login com a senha temporária do reset | `ok:true`, `precisaTrocarSenha:true` |
| 9 | Troca de senha forçada (self-service) | Sucesso; senha antiga para de funcionar |
| 10 | Login QR (`ctx=qr&atd=N`) | `ok:true`, cookie setado |
| 11 | Replay (mesmo envelope 2x) | Primeira aceita, segunda rejeitada |
| 12 | `kid` inexistente | `409 chave_expirada` |
| 13 | `ultimo_acesso`/`ultimoacesso` | Avança em login e em requisição autenticada comum, nas duas tabelas |
| 14 | Resiliência sem banco | Página com cookie válido continua respondendo |

Todos os 14 cenários (25 asserções individuais) passaram na rodada final.
Também confirmado por smoke test manual: `login_chave.php` estável entre
chamadas dentro da janela de rotação, bundle `login.bundle.js` servido e
funcional no navegador.

---

## 11. Ambiente de Teste

### 11.1 `docker-compose.test.yml`

Controla só o container do app (`atendimentos-legado-test`) — **não**
gerencia `mysqldb`/`phpmyadmin`, que são infraestrutura compartilhada com
projetos irmãos na rede `phpmsqlphpadmindocker_SrrpolacoNet` (referenciada
como `external: true`, nunca criada/recriada por este compose).

```yaml
services:
  app:
    image: phpmsqlphpadmindocker-laravel:latest
    container_name: atendimentos-legado-test
    working_dir: /var/www/html
    command: ["php", "-S", "0.0.0.0:8090"]
    volumes: [".:/var/www/html"]
    ports: ["8090:8090"]
    networks: [phpmsqlphpadmindocker_SrrpolacoNet]
```

```
docker compose -f docker-compose.test.yml up -d       # sobe
docker compose -f docker-compose.test.yml down         # derruba
docker compose -f docker-compose.test.yml restart app  # após editar .dev-env
```

### 11.2 Usuários de teste no banco local

`teste.admin@local.test` (cadastro_id=43, grupos=127),
`teste.viewer@local.test` (cadastro_id=44, grupos=1, senha alterada durante
o teste), `teste.qr@local.test` (`tb_usuarios_qr`, atendimento_id=2) —
senhas em hash, criados via SQL direto para teste (não via API). Podem ser
removidos ou reaproveitados.

---

## 12. Controle de Versão

Git inicializado nesta sessão (não existia antes). Commit raiz único
`d2cc44b`, tag anotada `v0.1.0-fase1-auth`, branch `master`, sem remoto
configurado.

`.gitignore` cobre (todos confirmados um a um antes do primeiro commit,
por conterem credenciais reais ou dado operacional de produção):

```
node_modules
logs/
.dev-env
_dev-env
.sessions
_dbstdbrz2.ini
dbstdbrz2.ini
dbstdbrz2.ini.main
.login_kx_keys.json
```

`logs/` foi removida do commit depois de identificado que continha um log
operacional revelando o caminho de produção (`/home/u210527770/domains/...`)
— sem credenciais, mas dado que não pertence a controle de versão de
código.

---

## 13. Estado Atual — Pronto vs. Em Aberto

### 13.1 Pronto e testado

- Fase 0: vulnerabilidades emergenciais (phpinfo exposto, SQL injection,
  endpoints sem autenticação, QR sem validação de token).
- Fase 1: autopromoção de privilégio fechada; senha em hash (trânsito e
  repouso); token/autorização centralizados em `Cadastro`; login protegido
  por ECDH+AEAD; troca de senha forçada genérica (real + QR); front-end de
  login funcional.

### 13.2 Em aberto (não construído)

| Item | Observação |
|---|---|
| Migração de produção | Rehash + deploy do código novo ainda só rodou no banco de teste local — é o "passo sensível único" do plano, precisa ser feito junto, fora de horário de uso |
| Isolamento por credencial de banco dedicada ao login | Hoje o isolamento é só de código (`Cadastro` é o único chamador); a ideia original também previa uma credencial MySQL com `GRANT` restrito só a `senha`/`redefineSenha`/`ultimo_acesso` |
| `register_globals.php` | Continua recriando a superfície de injeção antiga; `alterar_senha.php` ainda depende dele para `$senha2`/`$adicionar` |
| Front-end do link QR público | `webpack/src/atendimentos_api.js` ainda usa `tk` antigo (crc32); precisa apontar para `api/atendimento_publico.php` — **diferente do login**, não tocado nesta fase |
| Entrega de arquivos grandes (até 600GB) | Nenhum mecanismo hoje; direção futura é link em nuvem (ex. OneDrive) |
| Escopo de privilégio do `saUser` | Aceito como intencional para dev; produção já usa credencial escopada (`u210527770_dbstdbrz2`) |
| Visão de middleware de dados | Só a peça de autenticação está pronta; `Cadastro`/`Atendimento` são a forma-alvo da interface, mas o SQL direto por dentro delas ainda não foi trocado por chamadas a um middleware (que também não existe ainda) |

---

## 14. Decisões de Design — Porquês

Resumo das decisões que não seriam óbvias só lendo o código:

- **Por que `Cadastro` e não um serviço de auth separado?** O dono do
  sistema já havia decidido que `Cadastro`/`Atendimento` são a forma-alvo
  da futura interface de middleware — centralizar autenticação ali é
  consistente com essa direção, em vez de criar uma terceira camada que
  precisaria ser desfeita depois.
- **Por que `tokenAuth.php`/`loginKx.php` continuam separados, se "só
  `Cadastro` processa segurança"?** São bibliotecas de criptografia pura
  (HMAC, primitivos sodium), sem noção de payload/domínio — chamadas
  exclusivamente por `Cadastro`, nunca por um endpoint. O princípio é sobre
  *quem decide e orquestra*, não sobre proibir toda função auxiliar.
  Vantagem paralela: ambas as camadas já são, por construção, a base que
  uma futura extração para um serviço/processo separado (ex.: isolamento
  por credencial de banco, item em aberto) reaproveitaria sem redesenho.
- **Por que POST via `fetch()` e não WebSocket?** WebSocket exigiria um
  processo PHP daemon persistente (Ratchet/Swoole/Workerman), incompatível
  com hospedagem compartilhada típica (Hostinger) e sem necessidade real
  aqui (não há troca contínua depois da autenticação — é um único
  request/response).
  Foi cogitado inicialmente por preferência de evitar POST nativo de
  formulário; a resolução final foi manter POST, mas garantir que é sempre
  disparado por JS depois de validação no cliente, nunca por um `<button
  type="submit">` fazendo POST nativo do navegador.
- **Por que sempre HTTP 200 nas respostas de login?** Reduz vazamento de
  informação por status HTTP para um observador passivo (proxy/log
  intermediário) — o resultado real (`ok`/`erro`) só existe dentro do corpo
  cifrado, que só quem tem a chave de sessão consegue ler.
- **Por que `aad` deixou de ser transmitido?** Ver seção 5.6 — recalculá-lo
  dos dois lados a partir de campos que já estão no envelope é mais simples
  e mais correto do que confiar num campo solto que poderia ser adulterado
  sem invalidar a cifra.
