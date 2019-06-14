<?php
	session_start();
        include_once('FuncionesPHP.php');
        
        function salir(){
            $objResponse = new xajaxResponse();
            
            $base_de_datos1 = "sipre_co_config";
            $db_usuario1 = "sipre_contable";
            $db_password1 = "c0nt@b1l1d@d";
            /*$db_usuario1 = "oriomka_root";
            $db_password1 = "oriomka";*/
            
            
            if (!($id1 = mysql_connect("localhost", $db_usuario1, $db_password1))){
                    return -1;
            }
            if (!mysql_select_db($base_de_datos1, $id1)){
                    return -1;
            }
            
            $SqlStr1="UPDATE usuario SET conectado='0' WHERE nombre="."'".$_SESSION["UsuarioSistema"]."'";
            $result= mysql_query($SqlStr1);
            
            $objResponse->script("location.href='../index2.php'");
            
            return $objResponse;
        }
	
	$xajax->register(XAJAX_FUNCTION,"salir");
?>