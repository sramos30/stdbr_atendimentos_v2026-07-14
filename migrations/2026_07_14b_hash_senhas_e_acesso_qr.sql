-- Prepara o schema para: (1) senhas em hash (bcrypt via password_hash(), 60 chars,
-- as colunas antigas eram pequenas demais especialmente redefineSenha em
-- tb_cadastro que era varchar(30)); (2) ultimo_acesso em tb_usuarios_qr, que hoje
-- não existe (usuário QR nunca teve esse campo); (3) tabela de dedup de nonce
-- pra proteção anti-replay do login cifrado (ECDH+AEAD).
--
-- 100% aditiva/de alargamento - não quebra nada do código atual antes do rehash
-- (ver scripts/rehash_senhas.php) e da troca de login.php/Cadastro pra usar hash.

ALTER TABLE tb_cadastro MODIFY COLUMN senha VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE tb_cadastro MODIFY COLUMN redefineSenha VARCHAR(255) DEFAULT NULL;
ALTER TABLE tb_usuarios_qr MODIFY COLUMN senha VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE tb_usuarios_qr MODIFY COLUMN redefineSenha VARCHAR(255) DEFAULT NULL;

ALTER TABLE tb_usuarios_qr ADD COLUMN ultimo_acesso DATETIME DEFAULT NULL;

-- Decisão explícita: tb_cadastro.ultimoacesso (varchar(20), 15 anos de valores
-- livres incluindo o sentinela '0000-00-00 00:00:00') NÃO é convertida pra
-- DATETIME aqui - risco desnecessário sob STRICT_TRANS_TABLES, sem ganho pedido.
-- tb_usuarios_qr.ultimo_acesso nasce DATETIME por ser coluna nova, sem legado.

CREATE TABLE tb_login_replay (
    nonce_hash CHAR(64) NOT NULL,
    criado_em DATETIME NOT NULL,
    PRIMARY KEY (nonce_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
