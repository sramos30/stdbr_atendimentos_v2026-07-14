# Versão do sistema de atendimentos para Hostinger

* https://github.com/sramos30/stdbr_atendimentos.git


* 2023-01-30 - updated .dev-env 


>git clone --single-branch --branch producao https://github.com/sramos30/stdbr_atendimentos.git
>git clone --single-branch --branch master https://github.com/sramos30/stdbr_atendimentos.git
>git clone --single-branch --branch planos https://github.com/sramos30/stdbr_atendimentos.git

# Standard Brazil

* [stdbrazil completo](https://github.com/sramos30/stdbrazil.git)
* [versao localweb](git@github.com:sramos30/stdbr-locaweb.git)

## .htaccess
    ## UPLOAD OPTIONS start ##
    php_value upload_max_filesize 512M
    php_value post_max_size 512M
    php_value memory_limit 256M
    php_value max_execution_time 300
    php_value max_input_time 300
    ## UPLOAD OPTIONS end ##


## wp-config.php

    /** define upload fie limits */
    @ini_set( 'upload_max_filesize' , '512M' );
    @ini_set( 'post_max_size', '512M');
    @ini_set( 'memory_limit', '256M' );
    @ini_set( 'max_execution_time', '300' );
    @ini_set( 'max_input_time', '300' );


## dbstdbrz2.ini

    [database]
    username = u210527770_dbstdbrz2
    password = sYESp2CzHar74P
    dbname = u210527770_dbstdbrz2
    hostname = 127.0.0.1
    WP_DB_NAME = u210527770_DfUai
    WP_DB_USER = u210527770_QX5On
    WP_DB_PASSWORD = oudkR6cWTP

## .dev-env
    [database]
    username = u210527770_dbstdbrz2
    password = sYESp2CzHar74P
    dbname = u210527770_dbstdbrz2
    hostname = mysqldb
    WP_DB_NAME = u210527770_DfUai
    WP_DB_USER = u210527770_QX5On
    WP_DB_PASSWORD = oudkR6cWTP



## Bats de acesso ssh
```
stdserverux_stdserverux.bat
===========================
ssh stdserverux@181.215.135.55 

stdserverux_root.bat
====================
ssh root@181.215.135.55

standardbrazil_com_br.bat
=========================
ssh -p 65002 u210527770@185.239.210.184

```

## UPDATE DATABASE TO INCLUDE THE LINK
```
delete from tb_atendimentos where data = 0000-00-00;
ALTER TABLE `tb_atendimentos` CHANGE `deleted` `link` VARCHAR(512) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

UPDATE tb_atendimentos SET tag = CONCAT(DATE_FORMAT(data, '%Y%m%d'), LEFT(upper(REPLACE(navio,' ','')),12)) WHERE codAtendimento like '';
UPDATE tb_atendimentos SET tag = replace(replace(replace(upper(trim(codAtendimento)),"Á", "A"),"PAGUA","PGUA"),"/","") WHERE CHAR_LENGTH(trim(codAtendimento)) > 0;
```

## UPDATE DATABASE TO ADMIN CADASTRO
```
ALTER TABLE `tb_cadastro` CHANGE `ultimoacesso` `ultimoacesso` VARCHAR(20) NULL DEFAULT NULL;
ALTER TABLE `tb_cadastro` ADD `ativo` CHAR(1) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'S' AFTER `nivel`, ADD `redefineSenha` VARCHAR(30) NULL AFTER `ativo`;
UPDATE tb_cadastro SET redefineSenha = senha, ativo = 'S';

UPDATE `tb_cadastro` SET `nivel` = '2' WHERE `tb_cadastro`.`cadastro_id` = 22;

ALTER TABLE `tb_cadastro` DROP `redefineSenha`;
ALTER TABLE `tb_cadastro` DROP `ativo`;
```

## Versão Atendimentos/Drafts

```
L:\2023\2. Holds inspections\Arcelor Mittal\AM Annaba - HCI+HCSFU May &June 23

Cod.Atendimento
Data
Navio
Cliente

ALTER TABLE `tb_atendimentos` ADD `cliente` VARCHAR(30) NULL DEFAULT NULL AFTER `link`;

```

