<?php

class Produto {
    // database connection and table name
    private $conn;

    // utilities
    private $utilities;

    // constructor with $db as database connection
    public function __construct($db,$utilities){
        $this->conn = $db;
        $this->utilities = $utilities;
    }

    function runQuery( $kwId, $kwNome, $select, $page, $kwRecsPPage ) {
        $stmt = null;

        if( $kwRecsPPage <= 0 ) {
            $kwRecsPPage = 655360;
            $from_record_num = 0;
        } else {    
            $from_record_num = ($kwRecsPPage * $page) - $kwRecsPPage;
        }

        $query = "SELECT ".$select;
        $query .= " FROM tb_produtos a WHERE 1 ";

        if( strlen($kwId) > 0 ) {
            $query .= " AND a.produto_id = '$kwId' ";
        } else if( strlen($kwNome) > 0 ) {
            $kwNome=htmlspecialchars(strip_tags($kwNome));

            $query .= " AND a.nome like '%$kwNome%' ";
        }

        $query .= " LIMIT $kwRecsPPage OFFSET $from_record_num;";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        //==>debug
        //echo json_encode( array( 
        //    "query" => $query,
        //));
        //die();

        // execute query
        $stmt->execute();

        return $stmt;
    }

    // read products
    function tb_produtos($kwId, $kwNome, $page, $kwRecsPPage){
        // select all query        
        $select = "produto_id, nome, descricao, tags";     

        $stmt = $this->runQuery($kwId, $kwNome, $select, $page, $kwRecsPPage);
        $num = $stmt->rowCount();
        
        return $stmt;
    }

    function count($kwId, $kwNome) {
        $select = "COUNT(*) as total_rows ";
        $stmt = $this->runQuery($kwId, $kwNome, $select, 0, -1 );
        $num = $stmt->rowCount();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_rows'];
    }

    function getNewRecordId() {
        $newValue = -1;
        $stmt = null;

        $query = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_NAME = 'tb_produtos'";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        $stmt->execute();

        if( $stmt->rowCount() > 0 ) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $newValue = $row["AUTO_INCREMENT"];
        }

        return $newValue;
    }

    function insertNew($objProduto) {    
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
        );

        if( $objProduto ) {
            try {
                $query = "insert into tb_produtos (" .
                    "produto_id, nome, descricao, tags ) " .
                    "VALUES( ".intval($objProduto["produto_id"]).", '".
                    strip_tags($objProduto["nome"])."', '".
                    strip_tags($objProduto["descricao"])."', '".
                    strip_tags($objProduto["tags"])."')";

                $stmt = $this->conn->prepare($query);
            
                //==>Debug
                //$errorStr["query"] = $query;
            
                // execute the query
                $errorStr["err_code"] = $stmt->execute();
                $errorStr["rc_rowCount"] = $stmt->rowCount();

                if( $stmt->rowCount() > 0 )
                    $errorStr["rc"] = true;

            } catch (PDOException $exception) {
                $errorStr["err_code"] = $exception->getCode();
                $errorStr["err_msg"] = $exception->getMessage(); 
            }
        } else {
            $errorStr["err_code"] = -1;
            $errorStr["err_msg"] = "Nothing to insert"; 
        }
        
        return $errorStr;
    }

    function update($objProduto) {
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
        );
        
        //produto_id, nome, descricao, tags
        try {

            $query = "";
            
            $queryExt = "SET ";

            if( array_key_exists('nome', $objProduto) ) {
                $query .= $queryExt."nome = '".strip_tags($objProduto["nome"])."'";
                $queryExt = ", ";
            }

            if( array_key_exists('descricao', $objProduto) ) {
                $query .= $queryExt."descricao = '".strip_tags($objProduto["descricao"])."'";
                $queryExt = ", ";
            }

            if( array_key_exists('tags', $objProduto) ) {
                $query .= $queryExt."tags = '".strip_tags($objProduto["tags"])."'";
                $queryExt = ", ";
            }

            if( strlen($query) > 0 ) {
                $query = "UPDATE tb_produtos ".$query.
                "WHERE ".
                    "produto_id = ".intval($objProduto["produto_id"]);
            
                $stmt = $this->conn->prepare($query);
        
                //==>Debug
                //$errorStr["query"] = $query;
            
                // execute the query
                $errorStr["rc"] = $stmt->execute();
                $errorStr["rc_rowCount"] = $stmt->rowCount();
                
                $errorStr["rc"] = true;
            } else {
                $errorStr["err_code"] = -1;
                $errorStr["err_msg"] = "No fields to update";     
            }
        } catch (PDOException $exception) {
            $errorStr["rc"] = false;
            $errorStr["err_code"] = $exception->getCode();
            $errorStr["err_msg"] = $exception->getMessage(); 
        }

        return $errorStr;
    }
}

?>