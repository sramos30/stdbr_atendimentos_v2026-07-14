<?php

class Atendimento {
  
    // database connection and table name
    private $conn;

    // utilities
    private $utilities;

	// constants
	private $maxRecsPerPage = 65535;

    // constructor with $db as database connection
    public function __construct($db,$utilities){
        $this->conn = $db;
        $this->utilities = $utilities;
    }

    function runQueryFilter($queryParms) {
        // $kwId, $kwCodAtd, $kwAtdType, $kwNavio, $kwFalta, $kwExcesso, 
        //$kwDifMenor, $kwDifMaior, $kwD1, $kwD2, $kwProds, $kwTerms, 
        //$page, $kwRecsPPage ) {

        $kwId        = array_key_exists("Id", $queryParms)?$queryParms["Id"]:"";
        $kwCodAtd    = array_key_exists("CodAtd", $queryParms)?$queryParms["CodAtd"]:"";
        $kwAtdType   = array_key_exists("AtdType", $queryParms)?$queryParms["AtdType"]:"";
        $kwNavio     = array_key_exists("Navio", $queryParms)?$queryParms["Navio"]:"";
        $kwFalta     = array_key_exists("Falta", $queryParms)?$queryParms["Falta"]:"";
        $kwExcesso   = array_key_exists("Excesso", $queryParms)?$queryParms["Excesso"]:"";
        $kwDifMenor  = array_key_exists("DifMenor", $queryParms)?$queryParms["DifMenor"]:"";
        $kwDifMaior  = array_key_exists("DifMaior", $queryParms)?$queryParms["DifMaior"]:"";
        $kwD1        = array_key_exists("D1", $queryParms)?$queryParms["D1"]:"";
        $kwD2        = array_key_exists("D2", $queryParms)?$queryParms["D2"]:"";
        $kwProds     = array_key_exists("Prods", $queryParms)?$queryParms["Prods"]:"";
        $kwTerms     = array_key_exists("Terms", $queryParms)?$queryParms["Terms"]:"";
        $page        = array_key_exists("page", $queryParms)?$queryParms["page"]:"";
        $kwRecsPPage = array_key_exists("RecsPPage", $queryParms)?$queryParms["RecsPPage"]:"";

        $stmt = null;

        if( $kwRecsPPage <= 0 ) {
            $kwRecsPPage = 6553600;
            $from_record_num = 0;
        } else {    
            $from_record_num = ($kwRecsPPage * $page) - $kwRecsPPage;
        }

        $query = "SELECT DISTINCT a.atendimento_id FROM tb_atendimentos a ";
        
        if( strlen($kwProds) > 0  ) {
            $query .= " LEFT JOIN tb_atendimentos_produtos p ON ( a.atendimento_id = p.atendimento_id ) " . 
            " LEFT JOIN tb_produtos tp  ON ( p.produto_id = tp.produto_id) "; 
        }

        if( strlen($kwTerms) > 0 ) {
            $query .= " LEFT JOIN tb_atendimentos_terminais t  ON ( a.atendimento_id = t.atendimento_id ) " .  
            " LEFT JOIN tb_terminais tt  ON ( t.terminal_id = tt.terminal_id) ";
        }

        $query .=" WHERE 1 ";

        if( strlen($kwProds) > 0  ) {
            $query .= " AND p.produto_id IN (".$kwProds.")";
        }

        if( strlen($kwTerms) > 0 ) {
            $query .= " AND t.terminal_id IN (".$kwTerms.")";
        }

        if( strlen($kwD1) > 0 || strlen($kwD2) > 0 ) {
            if( strlen($kwD1) == 0 )
                $kwD1 = "1900-01-01";
            else
                $kwD1=htmlspecialchars(strip_tags($kwD1));

            if( strlen($kwD2) == 0 )
                $kwD2 = "2100-12-31";
            else
                $kwD2=htmlspecialchars(strip_tags($kwD2));

            if( strcmp($kwD1,$kwD2) > 0 ) {
                $_t = $kwD1;
                $kwD1 = $kwD2;
                $kwD2 = $_t;
            }

            $query = $query." AND (a.data between '$kwD1' AND '$kwD2' ) ";  
        }

        if( strlen($kwId) > 0 ) { //&& intval($kwId) > 0 ) {
            $query .= " AND a.atendimento_id = '$kwId' ";
        } else if( strlen($kwCodAtd) > 0 ) {
            $kwCodAtd=htmlspecialchars(strip_tags($kwCodAtd));
            //$kwCodAtd = "%{$kwCodAtd}%";
            $query .= " AND a.codAtendimento = '$kwCodAtd' ";
        } else if( strlen($kwNavio) > 0 ) {
            $kwNavio=htmlspecialchars(strip_tags($kwNavio));
            $kwNavio = "%{$kwNavio}%";

            $query .= " AND a.navio like '%{$kwNavio}%' ";
        }

        if( strlen($kwAtdType) > 0 ) {
            if( $kwAtdType == "draft") 
                $query .= " AND ((a.cliente IS NULL) OR (LENGTH(a.cliente) < 1)) ";
            else if( $kwAtdType == "ref") 
                $query .= " AND ((a.cliente IS NOT NULL) AND (LENGTH(a.cliente) > 1)) ";
        }

        if( $kwDifMenor > 0 ) {
            $query .= " AND (ABS(a.diferenca) <= $kwDifMenor ) ";
        }

        if( $kwDifMaior > 0 ) {
            $query .= " AND (ABS(a.diferenca) >= $kwDifMaior ) ";
        }

        if( $kwExcesso > 0.0 || $kwFalta > 0.0 ) {
            $query .= " AND (";
        }

        if( $kwExcesso > 0.0 ) {
            $query .= " ( a.diferenca >= $kwExcesso )";

            if( $kwFalta > 0.0 ) {
                $query .= " OR ";
            }
        }
        
        if( $kwFalta > 0.0 ) {
            $query .= " ( a.diferenca <= -$kwFalta )";
        }

        if( $kwExcesso > 0.0 || $kwFalta > 0.0 ) {
            $query .= ") ";
        }

        $query .= "GROUP BY a.atendimento_id ORDER BY a.atendimento_id ";
        $query .= " LIMIT $kwRecsPPage OFFSET $from_record_num;";

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        //==>debug
        //echo json_encode( array( 
        //    "query" => $query,
        //    "rowCount" => $stmt->rowCount(), 
        //));
        //die();

        // execute query
        $stmt->execute();

        $arrAtendimentos = "";
        $first = true;

        if( $stmt->rowCount() > 0 ) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if( $first == false ) 
                    $arrAtendimentos .= ", ";
                else    
                    $first = false;
                
                $arrAtendimentos .= $row["atendimento_id"];
            }
        }

        $stmt = null;

        return $arrAtendimentos;
    }

    // read atendimentos
    function tb_atendimentos($queryParms) {
        $lstAtendimentos = $this->runQueryFilter($queryParms);

        //==>debug
        // echo json_encode( array( 
        // "lstAtendimentos" => $lstAtendimentos,
        // ));
        //die();

        if( $lstAtendimentos && strlen($lstAtendimentos) > 0 ) {
            // select all query        
            $query = "SELECT atendimento_id, codAtendimento, data, navio, balanca, arqueacao, " . 
                " comando_navio, perito_receita, outras_partes1, outras_partes1_id, outras_partes2, " .  
                " outras_partes2_id, outras_partes3, outras_partes3_id, excesso, falta, diferenca, link, cliente " . 
                "FROM tb_atendimentos where atendimento_id in (".$lstAtendimentos.") " .
                " ORDER BY data, atendimento_id; ";

            //==>debug
            // echo json_encode( array( 
            // "query" => $query,
            // ));
            //die();

            // prepare query statement
            $stmt = $this->conn->prepare($query);

            // execute query
            $stmt->execute();

            return $stmt;		
        } else {
            return NULL;
        }

    }

    function tb_atendimentos_produtos($queryParms) {
        $lstAtendimentos = null;
        $lstAtendimentos = $this->runQueryFilter($queryParms);

        //==>debug
        // echo json_encode( array( 
        // "lstAtendimentos" => $lstAtendimentos,
        // ));
        //die();

        $query = "SELECT a.atendimento_id, p.produto_id, tp.nome, tp.descricao, tp.tags from tb_atendimentos a " . 
            " LEFT JOIN tb_atendimentos_produtos p ON ( a.atendimento_id = p.atendimento_id ) " . 
            " LEFT JOIN tb_produtos tp  ON ( p.produto_id = tp.produto_id) " . 
            " LEFT JOIN tb_atendimentos_terminais t  ON ( a.atendimento_id = t.atendimento_id ) " .  
            " LEFT JOIN tb_terminais tt  ON ( t.terminal_id = tt.terminal_id) " . 
            " WHERE a.atendimento_id in (".$lstAtendimentos.") " .
            " GROUP BY p.produto_id, a.atendimento_id " .
            " ORDER BY a.atendimento_id, p.produto_id; ";

        //==>debug
        // echo json_encode( array( 
        // "query" => $query,
        // ));
        //die();

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        $stmt->execute();
    
        return $stmt;		
    }

    function tb_atendimentos_terminais($queryParms){
        $lstAtendimentos = null;
        $lstAtendimentos = $this->runQueryFilter($queryParms);

        //==>debug
        // echo json_encode( array( 
        // "lstAtendimentos" => $lstAtendimentos,
        // ));
        //die();

        $query = "SELECT a.atendimento_id, t.terminal_id, tt.nome, tt.descricao, tt.tags ". 
            " FROM tb_atendimentos a " .
            " LEFT JOIN tb_atendimentos_produtos p ON ( a.atendimento_id = p.atendimento_id ) " . 
            " LEFT JOIN tb_produtos tp  ON ( p.produto_id = tp.produto_id) " . 
            " LEFT JOIN tb_atendimentos_terminais t  ON ( a.atendimento_id = t.atendimento_id ) " .  
            " LEFT JOIN tb_terminais tt  ON ( t.terminal_id = tt.terminal_id) " . 
            " where a.atendimento_id in (".$lstAtendimentos.") " .
            " GROUP BY t.terminal_id, a.atendimento_id " .
            " ORDER BY a.atendimento_id, t.terminal_id;";

        //==>debug
        // echo json_encode( array( 
        // "query" => $query,
        // ));
        // ie();

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        $stmt->execute();
    
        return $stmt;		

    }

    function tb_atendimentos_poroes_terminais($queryParms){
        $lstAtendimentos = null;
        $lstAtendimentos = $this->runQueryFilter($queryParms);

        //==>debug
        // echo json_encode( array( 
        // "lstAtendimentos" => $lstAtendimentos,
        // ));
        //die();

        $query = "SELECT apt.atendimento_id, apt.porao, apr.produto_id, apr.fatorestiva, " . 
            "apr.cubagem, apr.condicao, apt.terminal_id, apt.quantidade " .
            " FROM tb_atendimentos_poroes_terminais apt " .
            " LEFT JOIN tb_atendimentos_poroes apr ON ( apt.atendimento_id = apr.atendimento_id and apt.porao = apr.porao ) " .  
            " WHERE apt.atendimento_id in (".$lstAtendimentos.")  order by apt.atendimento_id, apt.porao, apr.produto_id, apt.terminal_id";

        //==>debug
        // echo json_encode( array( 
        // "query" => $query,
        // ));

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        $stmt->execute();
    
        return $stmt;		
    }

    function count($queryParms) {
        $kwRecsPPage = $queryParms["RecsPPage"];
        $queryParms["RecsPPage"] = -1;
        $lstAtendimentos = $this->runQueryFilter($queryParms);
        $queryParms["RecsPPage"] = $kwRecsPPage;
        
        $arrAtendimentos = explode(",", $lstAtendimentos);

        //==>debug
        // echo json_encode( array( 
            // "arrAtendimentos" => $arrAtendimentos,
        // ));
        //die();

        return sizeof( $arrAtendimentos );
	}

    function getNewAtendimento() {
        $newValue = -1;
        $stmt = null;

        $query = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_NAME = 'tb_atendimentos'";

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
    
    function insertPart1($objAtd) {
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
         );

        try {
            $query = "insert into tb_atendimentos (" .
                "atendimento_id, codAtendimento, data, navio, balanca, arqueacao, " .
                "comando_navio, perito_receita, outras_partes1, " . 
                "outras_partes1_id, outras_partes2, outras_partes2_id, " .
                "outras_partes3, outras_partes3_id, excesso, falta, diferenca, link, cliente ) " .
                "VALUES( ".intval($objAtd["atdId"]).", '".
                strip_tags($objAtd["codAtendimento"])."', '".
                strip_tags($objAtd["data"])."', '".
                strip_tags($objAtd["navio"])."', '".
                sprintf("%.3f",($objAtd["balanca"]))."', '".
                sprintf("%.3f",($objAtd["arqueacao"]))."', '".
                sprintf("%.3f",($objAtd["comando_navio"]))."', '".
                sprintf("%.3f",($objAtd["perito_receita"]))."', '".
                sprintf("%.3f",($objAtd["outras_partes1"]))."', '".
                strip_tags($objAtd["outras_partes1_id"])."', '".
                sprintf("%.3f",($objAtd["outras_partes2"]))."', '".
                strip_tags($objAtd["outras_partes2_id"])."', '".
                sprintf("%.3f",($objAtd["outras_partes3"]))."', '".
                strip_tags($objAtd["outras_partes3_id"])."', '".
                sprintf("%.3f",$objAtd["excesso"])."', '".
                sprintf("%.3f",$objAtd["falta"])."', '".                
                sprintf("%.2f",$objAtd["diferenca"])."', '".
                strip_tags($objAtd["link"])."', '".
                strip_tags($objAtd["cliente"])."')";

            $stmt = $this->conn->prepare($query);
        
            $errorStr["query_part1"] = $query;
        
            // execute the query
            $errorStr["err_code"] = $stmt->execute();
            $errorStr["rc_rowCount"] = $stmt->rowCount();

            if( $stmt->rowCount() > 0 )
                $errorStr["rc"] = true;

        } catch (PDOException $exception) {
            $errorStr["err_code"] = $exception->getCode();
            $errorStr["err_msg"] = $exception->getMessage(); 
        }

        return $errorStr;
    }

    function updatePart1($objAtd) {
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
         );

        try {
            $query = "UPDATE tb_atendimentos SET " .
                "codAtendimento = '".strip_tags($objAtd["codAtendimento"])."', ".
                "data = '".strip_tags($objAtd["data"])."', ".
                "navio = '".strip_tags($objAtd["navio"])."', ".
                "balanca = '".sprintf("%.3f",($objAtd["balanca"]))."', ".
                "arqueacao = '".sprintf("%.3f",($objAtd["arqueacao"]))."', ".
                "comando_navio = '".sprintf("%.3f",($objAtd["comando_navio"]))."', ".
                "perito_receita = '".sprintf("%.3f",($objAtd["perito_receita"]))."', ".
                "outras_partes1 = '".sprintf("%.3f",($objAtd["outras_partes1"]))."', ".
                "outras_partes1_id = '".strip_tags($objAtd["outras_partes1_id"])."', ".
                "outras_partes2 = '".sprintf("%.3f",($objAtd["outras_partes2"]))."', ".
                "outras_partes2_id = '".strip_tags($objAtd["outras_partes2_id"])."', ".
                "outras_partes3 = '".sprintf("%.3f",($objAtd["outras_partes3"]))."', ".
                "outras_partes3_id = '".strip_tags($objAtd["outras_partes3_id"])."', ".
                "excesso = '".sprintf("%.3f",$objAtd["excesso"])."', ".
                "falta = '".sprintf("%.3f",$objAtd["falta"])."', ".
                "diferenca = '".sprintf("%.2f",$objAtd["diferenca"])."', ".
                "link = '".strip_tags($objAtd["link"])."', ".
                "cliente = '".strip_tags($objAtd["cliente"])."' ".
            "WHERE ".
                "atendimento_id = ".intval($objAtd["atdId"]);
        
            $stmt = $this->conn->prepare($query);
        
            $errorStr["query_part1"] = $query;
        
            // execute the query
            $errorStr["rc"] = $stmt->execute();
            $errorStr["rc_rowCount"] = $stmt->rowCount();
            
            $errorStr["rc"] = true;

        } catch (PDOException $exception) {
            $errorStr["rc"] = false;
            $errorStr["err_code"] = $exception->getCode();
            $errorStr["err_msg"] = $exception->getMessage(); 
        }

        return $errorStr;
    }

    function updateLstProdutos($objAtd) {
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
         );

        try {
            $query = "DELETE FROM tb_atendimentos_produtos WHERE atendimento_id = ".intval($objAtd["atdId"]);
            $stmt = $this->conn->prepare($query);

            $errorStr["rc"] = $stmt->execute();
            $errorStr["rc_rowCount"] = $stmt->rowCount();

            if( $objAtd["lstProdutos"] > 0 ) {
                $query = "INSERT INTO tb_atendimentos_produtos ( atendimento_id, produto_id) VALUES ";
                $lstRecs = "";

                foreach( $objAtd["lstProdutos"] as $id ) {
                    if( intval($id) > 0 ) {
                        if( strlen($lstRecs) > 0 )
                            $lstRecs .= ",";
                        $lstRecs .= "(".intval($objAtd["atdId"]).",".$id.")"; 
                    }
                }

                if( strlen($lstRecs) > 0 ) {
                    $stmt = $this->conn->prepare($query.$lstRecs);

                    $errorStr["rc"] = $stmt->execute();
                    $errorStr["rc_rowCount"] = $stmt->rowCount();
                }
            }

        } catch (PDOException $exception) {
            $error_code["rc"] = false;
            $errorStr["err_code"] = $exception->getCode();
            $errorStr["err_msg"] = $exception->getMessage(); 
        }

        return $errorStr;
    }

    function updateLstTerminais($objAtd) {
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
         );

        try {
            $query = "DELETE FROM tb_atendimentos_terminais WHERE atendimento_id = ".intval($objAtd["atdId"]);
            $stmt = $this->conn->prepare($query);

            $errorStr["rc"] = $stmt->execute();
            $errorStr["rc_rowCount"] = $stmt->rowCount();

            if( $objAtd["lstTerminais"] > 0 ) {
                $query = "INSERT INTO tb_atendimentos_terminais ( atendimento_id, terminal_id) VALUES ";
                $lstRecs = "";

                foreach( $objAtd["lstTerminais"] as $id ) {
                    if( intval($id) > 0 ) {
                        if( strlen($lstRecs) > 0 )
                            $lstRecs .= ",";
                        $lstRecs .= "(".intval($objAtd["atdId"]).",".$id.")"; 
                    }
                }

                if( strlen($lstRecs) > 0 ) {
                    $stmt = $this->conn->prepare($query.$lstRecs);

                    $errorStr["rc"] = $stmt->execute();
                    $errorStr["rc_rowCount"] = $stmt->rowCount();
                }
            }

        } catch (PDOException $exception) {
            $errorStr["rc"] = false;
            $errorStr["err_code"] = $exception->getCode();
            $errorStr["err_msg"] = $exception->getMessage(); 
        }

        return $errorStr;
    }

    function updatePoroes($objAtd) {
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
         );
        
        $errorStr["rc_arr"] = array();

        try {
            $query = "DELETE FROM tb_atendimentos_poroes WHERE atendimento_id = ".intval($objAtd["atdId"]);
            $stmt = $this->conn->prepare($query);
            
            $rc = $stmt->execute();
            $rc_rowCount = $stmt->rowCount();

            array_push( $errorStr["rc_arr"], array( "rc" => $rc, "rc_rowCount" => $rc_rowCount ) );

            $query = "DELETE FROM tb_atendimentos_poroes_terminais WHERE atendimento_id = ".intval($objAtd["atdId"]);
            $stmt = $this->conn->prepare($query);

            $rc = $stmt->execute();
            $rc_rowCount = $stmt->rowCount();

            array_push( $errorStr["rc_arr"], array( "rc" => $rc, "rc_rowCount" => $rc_rowCount ) );

            $query1 = "INSERT INTO tb_atendimentos_poroes ( atendimento_id, porao, produto_id, fatorestiva, cubagem, condicao) VALUES ";
            $lstRecs1 = "";

            $query2 = "INSERT INTO tb_atendimentos_poroes_terminais (atendimento_id, porao, terminal_id, quantidade) VALUES  ";
            $lstRecs2 = "";
           
            $validPores = array();

            if( count($objAtd["lstPoroes"]) > 0 ) {
                foreach( $objAtd["lstPoroes"] as $id ) {
                    if( intval($id) > 0 ) {
                        if( array_key_exists("terminais", $objAtd["poroes"][$id]) ) {
                            foreach( $objAtd["poroes"][$id]["terminais"] as $k => $v) {
                                if( intval($k) > 0 
                                    && array_key_exists(strval($k), $objAtd["poroes"][$id]["terminais"])
                                    && array_key_exists("quantidade", $objAtd["poroes"][$id]["terminais"][$k] )
                                    && floatval($objAtd["poroes"][$id]["terminais"][$k]["quantidade"]) > 0.0 ) {

                                    if( strlen($lstRecs2) > 0 )
                                        $lstRecs2 .= ",";
                                    
                                    $line = "(".intval($objAtd["atdId"]).",".intval($id).",".intval($k).',"'.
                                        sprintf("%.3f",$objAtd["poroes"][$id]["terminais"][$k]["quantidade"]).'")';
                                    $lstRecs2 .= $line;

                                    
                                    if( !in_array( $id, $validPores ) ) {
                                        array_push( $validPores, $id );
                                    }
                                }
                            }
                        }
                    }
                }

                foreach( $objAtd["lstPoroes"] as $id ) {
                    if( intval($id) > 0 ) {

                        if( in_array( $id, $validPores )
                            && array_key_exists( "poroes", $objAtd ) 
                            && array_key_exists( $id, $objAtd["poroes"] )
                            && array_key_exists( "produto_id", $objAtd["poroes"][$id])
                            && array_key_exists( "fatorestiva", $objAtd["poroes"][$id])
                            && array_key_exists( "cubagem", $objAtd["poroes"][$id])
                            //&& array_key_exists( "condicao", $objAtd["poroes"][$id])
                        ) {
                            if( !array_key_exists( "condicao", $objAtd["poroes"][$id]) )
                                $objAtd["poroes"][$id]["condicao"] = "---";

                            if( strlen($lstRecs1) > 0 )
                                $lstRecs1 .= ",";
                        
                            $line = "(".intval($objAtd["atdId"]).','.$id. ',"'.$objAtd["poroes"][$id]["produto_id"].'","'.
                                sprintf( "%.2f",$objAtd["poroes"][$id]["fatorestiva"]).'",'.$objAtd["poroes"][$id]["cubagem"].',"'.
                                $objAtd["poroes"][$id]["condicao"].'")'; 
        
                            $lstRecs1 .= $line;
                        }
                    }
                }
            
                if( strlen($lstRecs1) > 0 ) {
                    $stmt = $this->conn->prepare($query1.$lstRecs1);
                    
                    $rc = $stmt->execute();
                    $rc_rowCount = $stmt->rowCount();
                    
                    array_push( $errorStr["rc_arr"], array( "rc" => $rc, "rc_rowCount" => $rc_rowCount ) );
                    
                    $errorStr["rc"] = $rc;
                    $errorStr["rc_rowCount"] += intval($rc_rowCount);

                    //$errorStr["query1"] = $query1.$lstRecs1;
                }

                if( strlen($lstRecs2) > 0 ) {
                    $stmt = $this->conn->prepare($query2.$lstRecs2);
                    
                    $rc = $stmt->execute();
                    $rc_rowCount = $stmt->rowCount();
                    
                    array_push( $errorStr["rc_arr"], array( "rc" => $rc, "rc_rowCount" => $rc_rowCount ) );
                    
                    $errorStr["rc"] = $rc;
                    $errorStr["rc_rowCount"] += intval($rc_rowCount);

                    //$errorStr["query2"] = $query2.$lstRecs2;
                }
                
           }

            $errorStr["err_code"] = 0;

        } catch (PDOException $exception) {
            $errorStr["rc"] = false;
            $errorStr["err_code"] = $exception->getCode();
            $errorStr["err_msg"] = $exception->getMessage(); 
        }

        return $errorStr;
    }

    function insertNew($objAtd) {    
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
         );

        
        if( $objAtd ) {

            // insert part 1
            $errorStr["part1"] = $this->insertPart1($objAtd);

            if( $errorStr["part1"]["rc"] == false ) {
                $errorStr["err_code"] |= 1;
            } else {
                $errorStr["rc"] = $errorStr["part1"]["rc"];
                $errorStr["rc_rowCount"] += $errorStr["part1"]["rc_rowCount"];
            }

            // insert part 2 - lstProdutos
            if( $errorStr["err_code"] == 0 ) {
                $errorStr["updateLstProdutos"] = $this->updateLstProdutos($objAtd);

                if( $errorStr["updateLstProdutos"]["rc"] == false ) {
                    $errorStr["err_code"] |= 2;
                } else {
                    $errorStr["rc"] = $errorStr["updateLstProdutos"]["rc"];
                    $errorStr["rc_rowCount"] += $errorStr["updateLstProdutos"]["rc_rowCount"];
                }
            }

            // insert part 4 - lstTerminais
            if( $errorStr["err_code"] == 0 ) {
                $errorStr["updateLstTerminais"] = $this->updateLstTerminais($objAtd);

                if( $errorStr["updateLstTerminais"]["rc"] == false ) {
                    $errorStr["err_code"] |= 4;
                } else {
                    $errorStr["rc"] = $errorStr["updateLstTerminais"]["rc"];
                    $errorStr["rc_rowCount"] += $errorStr["updateLstTerminais"]["rc_rowCount"];
                }
            }

            // insert part 8|16|32 - Poroes
            if( $errorStr["err_code"] == 0 ) {
                $errorStr["updatePoroes"] = $this->updatePoroes($objAtd);

                if( $errorStr["updatePoroes"]["rc"] == false ) {
                    $errorStr["err_code"] |= (8|16|32);
                } else {
                    $errorStr["rc"] = $errorStr["updatePoroes"]["rc"];
                    $errorStr["rc_rowCount"] += $errorStr["updatePoroes"]["rc_rowCount"];
                }
            }

        } else {
            $errorStr["err_code"] = -1;
        }
        
        return $errorStr;
    }

    function update($objAtd) {    
        $errorStr = array( "err_code" => 0,
            "rc" => false,
            "rc_rowCount" => 0
         );

        
        if( $objAtd ) {
            // update part 1
            if( ($objAtd["changes"] & 1) == 1 ) {
                $errorStr["part1"] = $this->updatePart1($objAtd);

                if( $errorStr["part1"]["rc"] == false ) {
                    $errorStr["err_code"] |= 1;
                } else {
                    $errorStr["rc"] = $errorStr["part1"]["rc"];
                    $errorStr["rc_rowCount"] += $errorStr["part1"]["rc_rowCount"];
                }
            }

            // update part 2 - lstProdutos
            if( $errorStr["err_code"] == 0  && ($objAtd["changes"] & 2) == 2 ) {
                $errorStr["updateLstProdutos"] = $this->updateLstProdutos($objAtd);

                if( $errorStr["updateLstProdutos"]["rc"] == false ) {
                    $errorStr["err_code"] |= 2;
                } else {
                    $errorStr["rc"] = $errorStr["updateLstProdutos"]["rc"];
                    $errorStr["rc_rowCount"] += $errorStr["updateLstProdutos"]["rc_rowCount"];
                }
            }

            // update part 4 - lstTerminais
            if( $errorStr["err_code"] == 0  && ($objAtd["changes"] & 4) == 4 ) {
                $errorStr["updateLstTerminais"] = $this->updateLstTerminais($objAtd);

                if( $errorStr["updateLstTerminais"]["rc"] == false ) {
                    $errorStr["err_code"] |= 4;
                } else {
                    $errorStr["rc"] = $errorStr["updateLstTerminais"]["rc"];
                    $errorStr["rc_rowCount"] += $errorStr["updateLstTerminais"]["rc_rowCount"];
                }
            }

            // update part 8|16|32 - Poroes
            if( $errorStr["err_code"] == 0  && ($objAtd["changes"] & (8|16|32)) != 0 ) {
                $errorStr["updatePoroes"] = $this->updatePoroes($objAtd);

                if( $errorStr["updatePoroes"]["rc"] == false ) {
                    $errorStr["err_code"] |= $objAtd["changes"];
                } else {
                    $errorStr["rc"] = $errorStr["updatePoroes"]["rc"];
                    $errorStr["rc_rowCount"] += $errorStr["updatePoroes"]["rc_rowCount"];
                }
            }

        } else {
            $errorStr["err_code"] = -1;
        }
        
        return $errorStr;
    } 
  }
?>