<table width="1000" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="450" height="60" valign="baseline" bgcolor="#F7F7F7"><img src="imagens/logo.gif" width="450" height="99" /></td>
    <td align="right" valign="middle" bgcolor="#F7F7F7"><span class="titulo_grande">Área administrativa</span><br />
      <br />
      <span class="peq">
      <?php 
				$HourOfDay = date("G");
				if ($HourOfDay <= 12) {
				$horadia= 'Bom dia ';
				} else if ($HourOfDay > 12 && $HourOfDay < 18) {
				$horadia= 'Boa tarde ';
				} else {
				$horadia= 'Boa noite ';
				}
				echo $horadia;
				 echo  $_SESSION['tnome']; 
				 echo  " - Seu último acesso foi em: ".$_SESSION['ultimoacesso'];
				 ?>
    </span></td>
    <td width="80" bgcolor="#F7F7F7">&nbsp;</td>
  </tr>
  <tr>
    <td height="1" colspan="3" valign="baseline" bgcolor="#FFFFFF"></td>
  </tr>
  <tr>
    <td colspan="3" valign="baseline" background="imagens/back_menu.jpg" bgcolor="#0A305F">    
    <div class="menu">
    <?php if($_SESSION['tnivel'] ==1) { ?>
        <ul>
          <li><a href="index.php" target="_self" >Início</a> </li>
          
          <li><a href="" target="_self" >Atendimentos</a>
            <ul>
              <li><a href="atendimentos_listar.php" target="_self">Listar Atendimentos</a></li>
              <li><a href="atendimentos_adicionar.php" target="_self">Adicionar Atendimento</a></li>
            </ul>
          </li>
          
          <li><a href="" target="_self" >Relatórios</a>
            <ul>
              <li><a href="atendimentos_relatorio.php" target="_self">Relatório de Atendimentos</a></li>
              <li><a href="atendimentos_relatorio_anual.php" target="_self">Relatório de Atendimentos Anual</a></li>
              <li><a href="atendimentos_relatorio_mensal.php" target="_self">Relatório de Atendimentos Mensal</a></li>
            </ul>
          </li>
          
          <li><a href="" target="_self" >Cadastros</a>
            <ul>
              <li><a href="usuarios_editar.php" target="_self">Editar Usuário</a></li>
              <li><a href="usuarios_adicionar.php" target="_self">Adicionar Usuário</a></li>
              
              <li><a href="produtos_editar.php" target="_self">Editar Produto</a></li>
              <li><a href="produtos_adicionar.php" target="_self">Adicionar Produto</a></li>
              
              <li><a href="terminais_editar.php" target="_self">Editar Terminal</a></li>
              <li><a href="terminais_adicionar.php" target="_self">Adicionar Terminal</a></li>
            </ul>
          </li>
          
          <li><a href="" target="_self" >Opções do Sistema</a>
            <ul>
              <li><a href="alterar_senha.php" target="_self">Alterar Senha</a></li>
            </ul>
          </li>
          
          <li><a href="logout.php" target="_self" >Sair</a> </li>
        </ul>
        <?php }  else if($_SESSION['tnivel'] ==0) { ?>
         <ul>
          <li><a href="index.php" target="_self" >Início</a> </li>
          <li><a href="" target="_self" >Relatórios</a>
            <ul>
              <li><a href="atendimentos_relatorio.php" target="_self">Relatório de Atendimentos</a></li>
              <li><a href="atendimentos_relatorio_anual.php" target="_self">Relatório de Atendimentos Anual</a></li>
              <li><a href="atendimentos_relatorio_mensal.php" target="_self">Gráficos Mensais</a></li>
            </ul>
          </li>
          <li><a href="" target="_self" >Opções do Sistema</a>
            <ul>
              <li><a href="alterar_senha.php" target="_self">Alterar Senha</a></li>
            </ul>
          </li>
          <li><a href="logout.php" target="_self" >Sair</a> </li>
        </ul>
        <?php } ?>
    </div></td>
  </tr>
</table>
