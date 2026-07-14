# Contexto da sessão de design/implementação — Atendimentos (Standard Brazil)

Este documento resume uma sessão longa de design e implementação. Leia por completo
antes de continuar qualquer trabalho neste projeto — várias decisões aqui contradizem
o que pareceria "óbvio" olhando só pro código.

## Contexto de negócio

Sistema de pesagem/divergência de carga em navios (Standard Brazil), rodando há
**15 anos** sobre uma pilha PHP legada, sem framework (`ez_sql_*` como wrapper de
banco). O sistema é mais antigo que os próprios conceitos de autenticação, flag de
cliente e QR code — todos foram adicionados bem depois, por cima de um histórico de
dados que não os tinha.

`codAtendimento` (ex: "PGUA/DS/003/26") **não está sob controle do sistema e tem
duplicidade no banco** — isso é um problema de dados conhecido, aceito como não
corrigível agora. Todo o design de identificação usa `atendimento_id` (chave real,
única) como desempate, não `codAtendimento`.

## Decisão arquitetural principal (revogou trabalho anterior)

Havia uma tentativa de reescrita completa em Laravel
(`branch_producao/atendimentos-laravel/`). **Essa tentativa foi totalmente
abandonada nesta sessão — não deve ser reaproveitada, nem como referência de
comportamento.** A direção escolhida foi evoluir **de dentro do próprio sistema
legado** (esta pasta, `branch-producao/`), incrementalmente, sem downtime.

Princípios da arquitetura alvo (ainda em construção, não completa):
- Nenhuma camada de aplicação deve falar direto com SQL — a visão de longo prazo é um
  middleware de dados (JSON) na frente do banco, trocável de motor. Isso **ainda não
  foi construído** — só a peça de autenticação está pronta.
- API deve ser orientada a operações nomeadas e escopadas, nunca CRUD genérico por
  campo/tabela (isso foi a causa raiz de uma vulnerabilidade real encontrada e ainda
  não totalmente fechada — ver abaixo).

## O que já foi corrigido nesta sessão (Fase 0 — vulnerabilidades reais confirmadas)

1. `api/atendimentos/index.php` — era um `phpinfo()` cru → removido (404 agora).
2. `login.php` — SQL injection (`$email`/`$senha` interpolados direto na query) →
   corrigido com `$db->escape()`.
3. `api/usuarios.php`, `produtos.php`, `terminais.php`, `planos.php`, `showexcel.php`
   — não tinham autenticação nenhuma → agora exigem token válido
   (`api/shared/apiAuth.php`).
4. QR code público (`atendimentos/link?tag=X&tk=Y`) — o `tk` nunca era validado (nem
   client nem server-side) → resolvido pelo endpoint novo dedicado (ver abaixo).

## O que AINDA NÃO foi corrigido (não presumir que está resolvido)

- `saUser` (credencial usada em `.dev-env`, ambiente Docker local) tem privilégio
  equivalente a root no MySQL inteiro (`SUPER`, `CREATE USER`, `SHUTDOWN`, etc.),
  compartilhado com um banco de outro projeto (`u210527770_DfUai`). **Isso é
  intencional/aceitável para o ambiente de desenvolvimento interno** — não é a
  credencial de produção. A credencial real de produção é `u210527770_dbstdbrz2`
  (usuário e banco com o mesmo nome, propriamente escopado a esse único banco —
  ver `dbstdbrz2.ini.main`/`_dbstdbrz2.ini`), mapeada no `.env` de produção. O ponto
  em aberto de verdade não é "trocar o `saUser`", e sim architetural: **nenhuma rota
  da aplicação deve falar com o banco diretamente** — só o middleware de dados
  (ainda não construído, ver item 8 abaixo) deve enxergar qualquer credencial de
  banco, seja `saUser` em dev ou `u210527770_dbstdbrz2` em produção. Hoje isso não é
  verdade: `conecta.php` é incluído diretamente por rotas da app.
- `register_globals.php` continua recriando manualmente o `register_globals` do PHP
  antigo (superfície de injeção ampla). Não removido. `alterar_senha.php` ainda lê
  `$senha2`/`$adicionar` por esse mecanismo (não convertido pra `$_POST`/JSON
  direto nesta rodada — deliberado, escopo mínimo).
- Entrega dos arquivos sensíveis por atendimento (até 600GB — fotos, planilhas) —
  **confirmado que não existe mecanismo nenhum hoje.** A direção futura é um link
  criado em algum serviço de nuvem (ex.: OneDrive), não um upload/download pelo
  próprio sistema. Nada disso foi mapeado ou construído ainda.
- **Isolamento por credencial de banco pro processo de login** (distinto do
  isolamento por código, que este ponto de FECHADO — ver Fase 1 abaixo): a ideia
  original também incluía uma credencial MySQL própria, com `GRANT` restrito só a
  `senha`/`redefineSenha`/`ultimo_acesso`, como defesa adicional caso haja um bug
  dentro da própria `Cadastro`. **Isso não foi implementado** — hoje `Cadastro`
  usa a mesma credencial/conexão PDO de qualquer outro código (`u210527770_dbstdbrz2`
  em produção). O isolamento que existe hoje é só de código (nenhum endpoint chama
  SQL/sodium diretamente, só `Cadastro`), não de permissão de banco.

## Modelo de autorização (desenhado e parcialmente implementado)

Duas tabelas de usuário, **nunca misturadas**:

- **`tb_cadastro`** (usuários reais/permanentes: staff). Tem `grupos` (bitmap,
  substituiu `nivel`) e `cliente` (campo separado).
- **`tb_usuarios_qr`** (usuários temporários — ex: capitão do navio). Tem `grupos`
  (sempre `VER`=1 na criação, nunca outra coisa, mas usa a MESMA coluna/checagem que
  usuário real — não há dois códigos de validação) e `atendimento_id` (a qual
  atendimento tem acesso).

**`grupos` (bitmap) = O QUE o usuário pode fazer** (ver/criar/alterar/excluir/etc) —
definido em `tokenAuth.php`:
```
GRUPO_VER=1, GRUPO_CRIAR=2, GRUPO_ALTERAR=4, GRUPO_EXCLUIR=8,
GRUPO_GERENCIAR_CATALOGO=16, GRUPO_CADASTRAR_USUARIO_QR=32, GRUPO_GERENCIAR_USUARIOS=64
```
Migrado do `nivel` antigo: 0→1 (Viewer), 1→63 (Operador), 2→127 (Admin).

**`cliente` = QUAL DADO o usuário real enxerga** (dimensão totalmente separada do
bitmap — não confundir as duas, esse foi um erro que cometi e corrigi durante o
design). Regra: usuário real vê atendimentos onde `atendimento.cliente ==
usuario.cliente`. Hoje só existe `ATEXP` com usuários reais. `cliente` em branco num
atendimento é um estado transitório (histórico de 15 anos sem esse conceito),
equivalente a ATEXP — isso é **intencional**, o dono do sistema confirmou, não é bug.

**Usuário QR** é escopado por `atendimento_id` direto (não por `cliente`, não por
grupo compartilhado) — é uma relação 1-pra-um/poucos por natureza.

**Dois "mundos" de rota**: usuário real acessa livremente as rotas do mundo QR
(útil pra operador testar QR, ou Viewer acessar via QR sem precisar de cadastro
temporário). Usuário QR NUNCA acessa rotas do mundo normal. Login com contexto QR
(`login.php?ctx=qr&atd=X`) busca primeiro em `tb_usuarios_qr` (escopado a esse
`atendimento_id`), só cai pro `tb_cadastro` se não achar. Login direto (sem `ctx`) só
autentica usuário real.

## Autenticação (token, substituiu `$_SESSION` por completo)

O dono do sistema pediu explicitamente pra eliminar o `$_SESSION` de arquivo o quanto
antes — feito nesta sessão, não foi adiado.

- `tokenAuth.php` (novo, raiz de `branch-producao/`): token HMAC-SHA256 assinado,
  autocontido, num cookie httponly (`atd_token`). Payload: `usuario_id`, `tipo`
  ('real'|'qr'), `grupos`, `cliente` (real) ou `atendimento_id` (qr), `nome`, `exp`.
  **Renovado (nova expiração) a cada requisição válida** — expiração deslizante,
  exatamente como pedido.
- Segredo em `.dev-env` (`token_secret=...`) — só local, `.gitignore`já cobre. Valor
  atual é um placeholder óbvio ("teste-local-nao-usar-em-producao...") — trocar antes
  de qualquer coisa real.
- `$_SESSION` está **completamente eliminado** do código vivo (confirmado por grep).
  Só sobra em `menu.php`, que é código morto confirmado (nada o inclui — o menu real
  é `menu.html`, sem dependência de sessão).
- `auth.php` foi reescrito pra usar token, mas **manteve a mesma interface externa**
  (`$refid`, `$permissao` 0/1/2) que os ~30 arquivos existentes já usam
  (`$permissao=N; include("auth.php");`) — isso foi deliberado, pra não precisar
  tocar em cada um desses 30 arquivos individualmente. `$permissao` é traduzido
  internamente pros bits de grupo exigidos.
- **Troca de senha forçada**: token carrega `precisaTrocarSenha` (true quando o
  login bateu com `redefineSenha`, não `senha`). `auth.php` redireciona pra
  `alterar_senha.php` quando true. `alterar_senha.php` agora grava a senha nova em
  `senha` (não mais em `redefineSenha`, como o código legado original fazia — isso
  causaria um loop infinito de troca forçada) e zera `redefineSenha`.
  **Troca de senha forçada é regra de negócio obrigatória e vale pra qualquer
  usuário, real ou temporário (QR)** — confirmado pelo dono do sistema. **Fechado
  na Fase 1** (ver abaixo): `alterar_senha.php` é genérico por tipo via
  `Cadastro::atualizarSenha($tokenPayload['tipo'], ...)`; não precisou de tela nova
  pro mundo QR porque `auth.php` já redirecionava pra lá sem filtrar por tipo — só
  faltava a lógica de senha ser genérica, e agora é.
- **Duas operações de senha, não confundir** (esclarecido nesta sessão): "trocar
  senha" (self-service, usuário autenticado mexe só na própria linha, só na coluna
  `senha`) é **genérica pra qualquer tipo de usuário** (`tb_cadastro` ou
  `tb_usuarios_qr`) — não deve ser restrita por tipo. "Resetar senha" (admin
  definindo senha temporária pra *outro* usuário) só escreve em `redefineSenha`,
  nunca em `senha` diretamente, e exige `GRUPO_GERENCIAR_USUARIOS` do chamador.
  Nenhuma das duas deve passar por `Cadastro::update()` genérico.
- **Isolamento de privilégio do processo de validação de senha** — **fechado na
  Fase 1 no nível de código** (isolamento por credencial de banco separada
  continua em aberto, ver seção "ainda não corrigido" acima). `senha` e
  `ultimo_acesso`/`ultimoacesso` são propriedade exclusiva do sistema de login
  (só `Cadastro::atualizarSenha()`/`atualizarUltimoAcesso()` escrevem essas
  colunas — bloqueadas explicitamente em `Cadastro::update()`, o CRUD
  administrativo). **`redefineSenha` é compartilhado**: o admin escreve nela via
  `update()` (reset de senha de terceiro), o login lê/zera ela no fluxo de troca
  forçada — os dois caminhos são código desacoplado, sem um chamar o outro.
- **Princípio geral de autorização — implementado na Fase 1**: **toda operação**
  (não só as de senha) se baseia no bitmap `grupos` do usuário chamador — não
  existe operação "livre" pra usuário autenticado. A edição administrativa de
  outro usuário (`nivel`/`grupos`/`cliente`/`nome`) é um CRUD administrativo
  (`Cadastro::update()`, exige `GRUPO_GERENCIAR_USUARIOS`, checado **só dentro da
  classe**, nenhum endpoint HTTP duplica essa checagem), com allowlist explícita
  de campos que **exclui `senha`/`ultimoacesso`/`ultimo_acesso`** e **permite
  `redefineSenha`** — é por aí que o admin "reseta" a senha de outro usuário
  (escreve temporária em `redefineSenha`, hasheada antes de gravar).
- **Pegadinha de PHP encontrada e corrigida**: havia uma linha em branco fora de tags
  `<?php ?>` em `alterar_senha.php`, entre dois blocos PHP — isso manda saída pro
  corpo da resposta antes da hora, fazendo um `setcookie()` posterior falhar
  silenciosamente ("headers already sent"). Os blocos foram unidos. Fique atento a
  esse padrão se aparecerem bugs parecidos ("a mudança não colou") em outros arquivos
  legados com blocos PHP fragmentados.

## Fase 1 — Segurança de autenticação (implementada e testada nesta sessão)

Fecha os dois problemas reais que ficaram em aberto da Fase 0: autopromoção via
`Cadastro::update()` e senha em texto puro (armazenamento e trânsito). Ordem de
implementação seguida (migração → métodos novos → fechar autopromoção → renovação
de token → chave ECDH → rehash → login cifrado → front-end) documentada no plano
aprovado; todos os passos foram implementados e testados de ponta a ponta no
container descartável.

- **Migração** `migrations/2026_07_14b_hash_senhas_e_acesso_qr.sql`: alarga
  `senha`/`redefineSenha` pra `varchar(255)` nas duas tabelas (bcrypt = 60 chars;
  `tb_cadastro.redefineSenha` era `varchar(30)`, pequeno demais), adiciona
  `tb_usuarios_qr.ultimo_acesso` (não existia) e cria `tb_login_replay` (dedup de
  nonce anti-replay). **Já aplicada no banco de teste local.**
- **`cli/rehash_senhas.php`** (CLI-only, checa `php_sapi_name()`): migra
  `senha`/`redefineSenha` de texto puro pra `password_hash()`/bcrypt nas duas
  tabelas, idempotente (pula valores já hasheados/vazios). **Já rodado no banco de
  teste local** — todas as senhas de teste hoje são hash, não texto puro. Ainda
  precisa rodar contra produção, junto com o deploy do código novo (ver ordem
  abaixo — não há modo de aceitar texto puro OU hash simultaneamente).
- **`Cadastro` (`api/objects/usuario.php`) virou o dono de todo o escopo de
  autenticação/autorização**, como decidido: `validarSenha()` (password_verify
  contra `senha` OU `redefineSenha`, mitigação de timing com hash-dummy quando o
  usuário não existe), `gerarToken()`/`renovarToken()` (só eles tocam
  `ultimo_acesso`/`ultimoacesso`, best-effort — não derruba o request se o banco
  cair no meio), `atualizarSenha()` (self-service, genérico real/qr),
  `autorizar()` (único ponto de entrada de autorização — assinatura + forma do
  payload + bitmap, tudo num método só), `chavePublicaAtual()`/`processarLogin()`
  (todo o protocolo ECDH+AEAD). As constantes `GRUPO_*` e `possuiGrupos()`
  migraram de `tokenAuth.php` pra cá.
- **`tokenAuth.php` encolhido**: só HMAC (assinar/validar) + cookie, sem saber o
  que tem dentro do payload. Chamado **só** por dentro de `Cadastro` — `auth.php`/
  `api/shared/apiAuth.php` não o incluem mais diretamente, só chamam
  `Cadastro::autorizar()`/`renovarToken()`.
- **`loginKx.php`** (novo, raiz): primitivos `sodium` de baixo nível pro ECDH+AEAD
  — geração/rotação do par de chaves efêmero do servidor (`crypto_kx_keypair`,
  rotação a cada 15min, janela de graça de +15min), derivação de chaves de sessão
  (`crypto_kx_server_session_keys`) e cifra/decifra (`xchacha20poly1305_ietf`).
  Estado em `.login_kx_keys.json` (raiz, dot-prefixed como `.dev-env`, já no
  `.gitignore`). Chamado **só** por dentro de `Cadastro` — nenhum endpoint toca
  `sodium_*` diretamente. **Pegadinha real encontrada**: a extensão `sodium` do
  PHP (diferente de outras bindings) espera o keypair **combinado**
  (`secretKey.publicKey`, 64 bytes) em `sodium_crypto_kx_server_session_keys()`,
  não pk/sk separados — confirmado via `ReflectionFunction` (PHP 8.3). Gerou um
  erro real (`expects exactly 2 arguments, 3 given`) até ser corrigido.
- **`login_chave.php`** (novo, raiz): endpoint público que só chama
  `Cadastro::chavePublicaAtual()` e devolve `{kid, publicKey, exp}`.
- **`login.php` reescrito como plumbing puro**: GET serve a página (sem lógica de
  auth no HTML — erro agora só vem do JSON cifrado, tratado no cliente); POST lê
  `php://input`, chama só `Cadastro::processarLogin()`, devolve o resultado.
  Nenhum `sodium_*`, nenhuma comparação de senha, nenhuma montagem de payload de
  token no arquivo. `ctx`/`atd`/`next` vêm de `$_GET` (não mais de campos hidden
  do form) — o POST vai pra mesma URL da página (querystring preservada).
- **Protocolo do envelope** (ajuste em relação ao design original, decidido
  durante a implementação): o campo `aad` **não é transmitido** — cliente e
  servidor recalculam de forma independente como `kid + '|' + base64(clientPublicKey)`
  a partir dos campos que já estão no envelope, em vez de confiar num campo solto
  que poderia ser adulterado. Envelope de request:
  `{kid, clientPublicKey, aeadNonce, ciphertext}`; resposta:
  `{aeadNonce, ciphertext}` (mesmo `aad` recalculado, chave `tx`). Sempre HTTP 200
  quando o envelope é bem formado (erro vem no campo `ok` do corpo cifrado); 400
  (envelope malformado) e 409 (`kid` expirado) só acontecem antes de tocar dado de
  usuário. Anti-replay em duas camadas: `ts` (rejeita se `abs(now-ts) > 60s`) +
  dedup de `nonce` em `tb_login_replay`.
- **`webpack/src/login.js` reescrito** com `libsodium-wrappers` (novo em
  `package.json`) — o arquivo antigo usava `forge`+MD5/SHA1, remanescente de uma
  era pré-token, e estava **desconectado do form atual** (procurava `#btnLogin`,
  que não existia) — zero risco de regressão real ao reescrever. Fluxo: intercepta
  `submit` do form com `preventDefault()` (nunca deixa o navegador fazer POST
  nativo), valida campos no cliente, busca a chave do servidor, faz o handshake
  ECDH, cifra e manda via `fetch()`. **Pegadinha de build encontrada**: o pacote
  `libsodium-wrappers` na variante ESM importa um arquivo relativo
  (`./libsodium.mjs`) que só existe dentro do pacote `libsodium` (dependência
  separada), não dentro dele mesmo — quebra o build do webpack. Corrigido com
  `resolve.alias` em `webpack.config.js` apontando pra variante CommonJS via
  caminho de arquivo absoluto (apontar pelo *specifier* do pacote não funciona,
  esbarra no campo `exports` do `package.json`). Bundle reconstruído e copiado pra
  `js/login.bundle.js`; `login.php` agora inclui esse bundle (não incluía nada
  antes).
- **Bug de resiliência encontrado e corrigido durante o teste**: o catch de
  `atualizarUltimoAcesso()` só pegava `PDOException` — com o banco fora do ar,
  `$this->conn` é `null` e chamar `->prepare()` nele lança `Error` (não
  `PDOException`), então uma página com cookie válido quebrava (500) em vez de
  continuar funcionando (best-effort, como desenhado). Corrigido pra `catch
  (\Throwable $e)` + guarda explícita `!$this->conn`. Confirmado por teste real
  (apontando `.dev-env` pra host inválido temporariamente).
- **Testado de ponta a ponta** no container descartável (`phpmsqlphpadmindocker-laravel:latest`,
  porta 8090) com um script Node (`libsodium-wrappers`) simulando o cliente: login
  normal, senha errada (sem vazar por status), página protegida com/sem cookie,
  regressão da autopromoção (403 + valores inalterados no banco), campo `senha`
  bloqueado mesmo pro admin, reset de senha por admin via `redefineSenha`, login
  com senha temporária (`precisaTrocarSenha:true`), troca de senha forçada
  self-service, login QR genérico (`ctx=qr&atd=N`), replay rejeitado, `kid`
  expirado → 409, `ultimo_acesso`/`ultimoacesso` avançando nas duas tabelas,
  resiliência com banco fora do ar. Todos os cenários passaram.

## Endpoint novo do QR (`api/atendimento_publico.php`)

Único endpoint dedicado à página pública do QR — read-only, isolado de
`api/atendimentos.php` (que continua com todo o poder admin, intocado).

- **Segurança vem inteiramente da autenticação por token** — `tag`/`tk` NÃO são
  mecanismo de segurança.
- `tag` = `codAtendimento` (referência/exibição). `tk` = `atendimento_id` (chave real
  de busca, pros QR novos) — resolve a duplicidade de `codAtendimento` daqui pra
  frente.
- **Compatibilidade com QR antigos já impressos**: se `tk` não for um
  `atendimento_id` válido (ou não bater com o `tag`), cai pra busca por
  `codAtendimento` sozinho — mesma ambiguidade que já existe hoje pra esses casos,
  aceita porque não dá pra corrigir retroativamente um código já impresso.
- Autorização: usuário QR só acessa o `atendimento_id` ao qual foi vinculado (403 se
  tentar outro). Usuário real acessa qualquer atendimento livremente.
- Reaproveita os métodos já existentes de `Atendimento`
  (`tb_atendimentos`/`tb_atendimentos_produtos`/`tb_atendimentos_terminais`/
  `tb_atendimentos_poroes_terminais`, todos aceitando `$queryParms["Id"]` ou
  `["CodAtd"]` + **precisa também de `"page"=>1`**, senão `runQueryFilter()` quebra
  com "Unsupported operand types: int * string" — bug real que já apareceu e foi
  corrigido).
- Formato de resposta compatível com o que `loadAtendimento()` (JS) já espera.

## O que NÃO foi construído ainda (não perder de vista)

1. ~~Criptografia ECDH+AEAD pra troca de credenciais no login~~ — **implementada e
   testada na Fase 1** (ver seção acima).
2. **Integração no front-end do link QR público** (diferente do login — isso é
   sobre a página pública `atendimentos/link?tag=X&tk=Y`, não tocada nesta Fase
   1): `webpack/src/atendimentos_api.js` (função `updateQrCode()` e o fluxo
   `isLink`) ainda gera/espera o `tk` antigo (crc32) e chama `api/atendimentos.php`
   direto. Precisa apontar pro `api/atendimento_publico.php` novo e usar
   `atendimento_id` como `tk`. Depois precisa rebuild do bundle webpack
   (`js/atend.bundle.js`). Nada disso foi feito ainda.
3. ~~Restrição de campo em `Cadastro::update()`~~ — **implementada e testada na
   Fase 1** (allowlist + `GRUPO_GERENCIAR_USUARIOS`, ver seção acima).
4. Escopo de privilégio do `saUser` no MySQL — **aceito como intencional pro
   ambiente de dev** (ver "ainda não corrigido" acima); em aberto de verdade é só
   a isolação por credencial de banco pro processo de login (item novo abaixo).
5. Remoção/endurecimento de `register_globals.php`.
6. Mecanismo de entrega dos arquivos grandes (até 600GB) por atendimento.
7. **Isolamento por credencial de banco pro processo de login** (distinto do
   isolamento por código, que está fechado) — uma credencial MySQL própria com
   `GRANT` restrito só a `senha`/`redefineSenha`/`ultimo_acesso`. Não implementado.
8. A visão maior de "middleware de dados, nenhum código sabe que existe banco" — só
   a peça de autenticação está pronta; o resto do acesso a dados ainda é direto.
   Confirmado pelo dono do sistema: as funções que hoje mapeiam o MySQL (`ez_sql_*`,
   `conecta.php`, classes em `api/objects/`) **deverão ser reescritas para falar
   com o middleware**, e o middleware deve **exportar uma interface não-relacional**
   dos dados (não expor tabelas/colunas/JOINs — a app não deve saber que existe um
   modelo relacional por trás). Só o middleware deve ter credencial de banco.
   **Esclarecido nesta sessão**: as classes `Entidade::method()` (ex: `Cadastro`,
   `Atendimento`, em `api/objects/`) **não são descartáveis** — o dono do sistema as
   considera a **forma-alvo da interface interna de acesso ao middleware**. O que é
   temporário/provisório é só o *interior* delas (acesso SQL direto hoje); o
   contrato/shape dessas classes deve ser tratado como uma direção de
   desenvolvimento a preservar, trocando o SQL direto por chamadas ao middleware
   por dentro, sem necessariamente mudar a superfície que o resto da app já usa.

## Ambiente / infraestrutura

- Código-fonte legado: `/mnt/Data/Projects/dev-standard-brazil/development/branch-producao/`
  — **cópia de trabalho local, sem ligação direta com produção (Hostinger)**.
  Mudanças aqui exigem um passo de deploy separado (ainda não definido) pra ir ao ar.
  Confirmado explicitamente pelo dono do sistema.
- **Git inicializado nesta sessão** (não existia antes) — commit raiz único
  (`d2cc44b`) cobrindo o estado até a Fase 1, tag `v0.1.0-fase1-auth`. Sem
  remoto configurado ainda. `.gitignore` cobre `.dev-env`/`_dev-env`/
  `_dbstdbrz2.ini`/`dbstdbrz2.ini`/`dbstdbrz2.ini.main`/`.login_kx_keys.json`/
  `logs/` (todos com credenciais reais ou dado operacional de produção,
  confirmado um a um antes do primeiro commit — vale reconferir essa lista se
  novos arquivos de config/segredo aparecerem antes de qualquer `git add`).
- Rede Docker compartilhada: `phpmsqlphpadmindocker_SrrpolacoNet` — contém `mysqldb`
  (MySQL 8, `saUser`/`saP@ssw0rd`, banco `u210527770_dbstdbrz2`), `phpmyadmin`
  (porta 81), e outros containers de projetos irmãos.
- Padrão de teste usado a sessão inteira: container descartável a partir da imagem
  `phpmsqlphpadmindocker-laravel:latest` (tem PHP 8.3 + mysqli), montando
  `branch-producao` em `/var/www/html`, na rede acima, rodando
  `php -S 0.0.0.0:8090`, publicado com `-p 8090:8090`. Normalmente derrubado
  (`docker rm -f atendimentos-legado-test`) depois de cada rodada de teste
  automatizado — mas às vezes deixado no ar pra teste manual do dono do sistema pelo
  navegador.
- Máquina: hostname `srrmintmini2` (confirmado), acessada a partir de um Windows 11
  via VSCode Remote-SSH. URLs pelo navegador: `http://srrmintmini2.local:8090/` (app)
  e `http://srrmintmini2.local:81/` (phpMyAdmin, mesma credencial `saUser`).
- Migrações SQL registradas em `migrations/2026_07_14_grupos_e_usuarios_qr.sql` e
  `migrations/2026_07_14b_hash_senhas_e_acesso_qr.sql` (Fase 1 — ambas já aplicadas
  no banco de teste local).
- Conta de teste manual: `sramos30@hotmail.com` (cadastro_id=22, conta real do dono
  do sistema) — só o banco LOCAL foi alterado, senha agora em hash (rodou
  `cli/rehash_senhas.php`). Não afeta nenhuma credencial de produção real.
- Usuários de teste automatizado criados no banco local pela Fase 1 (senhas em
  hash, criados via SQL direto pra teste, **não via API**):
  `teste.admin@local.test` (cadastro_id=43, grupos=127), `teste.viewer@local.test`
  (cadastro_id=44, grupos=1, senha trocada durante o teste pra
  `NovaSenhaViewer1`), `teste.qr@local.test` (`tb_usuarios_qr`, atendimento_id=2).
  Podem ser removidos ou reaproveitados em rodadas futuras de teste.

## Preferências explícitas do dono do sistema (importante respeitar)

- Conversa em português.
- Rejeitou reaproveitar qualquer coisa do app Laravel abandonado, nem como
  referência.
- Não sobe nada parecido com produção sem testar muito antes.
- Prefere pragmatismo — evitar retrabalho, sequenciar pra não construir a mesma
  coisa duas vezes.
- Rejeitou PKI/mTLS pela carga operacional de manutenção que o cliente final não
  consegue sustentar — prefere criptografia autocontida (chaves efêmeras geradas em
  tempo real, primitivas de biblioteca padrão, não criptografia caseira).
- Login automatizado (máquina-a-máquina) é explicitamente escopo futuro, não agora.
- Confirmou estar de acordo com a duplicidade de `codAtendimento` como limitação de
  dados conhecida e não corrigível agora — o design usa `atendimento_id` como
  desempate em vez de tentar corrigir a causa raiz.
