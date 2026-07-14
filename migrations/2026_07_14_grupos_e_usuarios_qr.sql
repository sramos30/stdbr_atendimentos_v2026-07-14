-- Introduz o modelo de autorização por grupos (bitmap, estilo NT) e a tabela de
-- usuários temporários (QR). Substitui o `nivel` (0/1/2, hierárquico) por bits
-- independentes de capacidade. `nivel` é mantido por enquanto (não usado pela lógica
-- nova) para não quebrar nada que ainda dependa dele.
--
-- Bits (ver tokenAuth.php para as constantes):
--   VER=1, CRIAR=2, ALTERAR=4, EXCLUIR=8, GERENCIAR_CATALOGO=16,
--   CADASTRAR_USUARIO_QR=32, GERENCIAR_USUARIOS=64

ALTER TABLE tb_cadastro ADD COLUMN grupos BIGINT UNSIGNED NOT NULL DEFAULT 1;

-- Escopo de dado (qual cliente nível 2 esse usuário real representa - ex: ATEXP,
-- NORDEN). Hoje só ATEXP tem usuários reais; NULL/vazio é o estado transitório
-- equivalente a ATEXP até o backfill histórico (ver docs da sessão de design).
ALTER TABLE tb_cadastro ADD COLUMN cliente VARCHAR(30) DEFAULT NULL;

UPDATE tb_cadastro SET grupos = 1 WHERE nivel = 0;   -- Viewer -> só VER
UPDATE tb_cadastro SET grupos = 63 WHERE nivel = 1;  -- Operador -> VER|CRIAR|ALTERAR|EXCLUIR|GERENCIAR_CATALOGO|CADASTRAR_USUARIO_QR
UPDATE tb_cadastro SET grupos = 127 WHERE nivel = 2; -- Admin -> tudo

CREATE TABLE tb_usuarios_qr (
    usuario_qr_id INT NOT NULL AUTO_INCREMENT,
    identificacao VARCHAR(150) NOT NULL,
    nome VARCHAR(150) DEFAULT NULL,
    senha VARCHAR(64) NOT NULL DEFAULT '',
    redefineSenha VARCHAR(64) DEFAULT NULL,
    atendimento_id INT NOT NULL,
    grupos BIGINT UNSIGNED NOT NULL DEFAULT 1,
    validade DATETIME NOT NULL,
    ativo CHAR(1) NOT NULL DEFAULT 'S',
    criado_por INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (usuario_qr_id),
    UNIQUE KEY tb_usuarios_qr_ident_atd (identificacao, atendimento_id),
    KEY tb_usuarios_qr_atendimento_idx (atendimento_id),
    CONSTRAINT tb_usuarios_qr_atendimento_fk
        FOREIGN KEY (atendimento_id) REFERENCES tb_atendimentos(atendimento_id)
        ON DELETE CASCADE,
    CONSTRAINT tb_usuarios_qr_criado_por_fk
        FOREIGN KEY (criado_por) REFERENCES tb_cadastro(cadastro_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
