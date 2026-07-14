<?php

class Usuario {
    // database connection and table name
    private $conn;

    // utilities
    private $utilities;

	// constants
	private $maxRecsPerPage = 65535;	

    // object properties
    public $usuario_id;
    public $nome;
    public $email;
    public $senha;
    public $ultimoacesso;
    public $nivel;

    // constructor with $db as database connection
    public function __construct($db,$utilities){
        $this->conn = $db;
        $this->utilities = $utilities;
    }

    function runQuery( $kwId, $kwEmail, $select, $from_record_num, $kwRecsPPage ) {
        $stmt = null;

        $query = "SELECT ".$select;
        $query .= " FROM tb_cadastro WHERE 1 ";

        if( $kwId != "" ) {
            $kwId=(Int)htmlspecialchars(strip_tags($kwId));
            $query .= " and cadastro_id = :id";
        }

        if( $kwEmail != "" ) {
            $kwEmail=htmlspecialchars(strip_tags($kwEmail));
            $kwEmail = "%".$kwEmail."%";
            $query .= " and email like :email";
        }

        $query .= " LIMIT :limit OFFSET :offset;";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // bind parameters

        if( $kwId != "" ) {
            $stmt->bindParam(':id', $kwId, PDO::PARAM_INT );
        }
    
        if( $kwEmail != "" ) {
            $stmt->bindParam(':email', $kwEmail, PDO::PARAM_STR );
        }

        // bind variable values
        $stmt->bindParam( ':offset', $from_record_num, PDO::PARAM_INT);

        if( $kwRecsPPage <= 0 )
            $kwRecsPPage = 65536;

        $stmt->bindParam( ':limit', $kwRecsPPage, PDO::PARAM_INT);

        // execute query
        $stmt->execute();

        return $stmt;
    }

    // read usuarios
    function tb_usuarios($kwId, $kwEmail, $from_record_num, $kwRecsPPage){
		try {
			if( $kwRecsPPage < 1 )
				$kwRecsPPage = 1;
			
			if( $kwRecsPPage > $this->maxRecsPerPage )
				$kwRecsPPage = $this->maxRecsPerPage;

			if( $from_record_num < 0 ) {
				$from_record_num = 0;
			}

            // select query        
            $select = "cadastro_id, nome, email, ultimoacesso, nivel";     

			$stmt = $this->runQuery($kwId, $kwEmail, $select, $from_record_num, $kwRecsPPage);
			$num = $stmt->rowCount();
			
			return $stmt;
			
		} catch (Exception $e) {
			var_dump( "Exeption: $e->getMessage()");
			return $e;
		}
    }

    function count($kwId, $kwEmail) {
		try {

            $select = "COUNT(*) as total_rows ";

			$stmt = $this->runQuery($kwId, $kwEmail, $select, 0, -1 );
			
            $num = $stmt->rowCount();
                
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
            return $row['total_rows'];
			
		} catch (Exception $e) {
			var_dump( "Exeption: $e->getMessage()");
			return $e;
		}
    }

    function insertNew($objUsuario) {    
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
         );

        if( $objUsuario ) {
            try {
                $query = "insert into tb_cadastro (" .
                    "cadastro_id, nome, email, senha, ultimoacesso, nivel) " .
                    "VALUES( ".intval($objUsuario["cadastro_id"]).", '".
                    strip_tags($objUsuario["nome"])."', '".
                    strip_tags($objUsuario["email"])."', '".
                    strip_tags($objUsuario["senha"])."', '".
                    strip_tags($objUsuario["ultimoacesso"])."', '".
                    Number($objUsuario["nivel"])."') ";
            
                $stmt = $this->conn->prepare($query);
            
                $errorStr["query"] = $query;
            
                // execute the query
                $errorStr["rc"] = $stmt->execute();
                $errorStr["rc_rowCount"] = $stmt->rowCount();
            
            } catch (PDOException $exception) {
                $errorStr["err_code"] = $exception->getCode();
                $errorStr["err_msg"] = $exception->getMessage(); 
            }
        } else {
            $errorStr["err_code"] = -1;
        }
        
        return $errorStr;
    }

    function update($objUsuario) {    
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
         );

        if( $objUsuario ) {

            $query = "UPDATE tb_cadastro SET ";
            $fields = "";
            $whereClause = " WHERE cadastro_id = ".intval($objUsuario["cadastro_id"]);

            $fields .= "nome = '".strip_tags($objUsuario["nome"])."'";
            $fields .= ", "; 
            $fields .= "email = '".strip_tags($objUsuario["email"])."'";
            $fields .= ", "; 
            $fields .= "senha = '".strip_tags($objUsuario["senha"])."'";
            $fields .= ", ";             
            $fields .= "ultimoacesso = '".strip_tags($objUsuario["ultimoacesso"])."'";
            $fields .= ", ";             
            $fields .= "nivel = ".Number($objUsuario["nivel"]);
            
            if( len($fields) > 0 ) {
                try {
                    $query .= $fields.$whereClause;

                    $stmt = $this->conn->prepare($query);
                
                    $errorStr["query"] = $query;
                
                    // execute the query
                    $errorStr["rc"] = $stmt->execute();
                    $errorStr["rc_rowCount"] = $stmt->rowCount();
                
                } catch (PDOException $exception) {
                    $errorStr["err_code"] = $exception->getCode();
                    $errorStr["err_msg"] = $exception->getMessage(); 
                }
            }

        } else {
            $errorStr["err_code"] = -1;
        }
        return $errorStr;
    }

    function verificaSenha($kwEmail,$kwSenha) {
        $stmt = null;

        $kwEmail=htmlspecialchars(strip_tags($kwEmail));
        $kwEmail = "%".$kwEmail."%";

        $kwSenha=htmlspecialchars(strip_tags($kwSenha));
        $kwSenha = "%".$kwSenha."%";

        $query = "SELECT ultimoacesso FROM tb_cadastro WHERE ";
        $query .= " email like :email and senha like :senha";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':email', $kwEmail, PDO::PARAM_STR );
        $stmt->bindParam(':senha', $kwSenha, PDO::PARAM_STR );

        // execute query
        $stmt->execute();
        $num = $stmt->rowCount();

        return $num;
    }
}

?>