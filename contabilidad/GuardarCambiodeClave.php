<?php session_start();
include_once('FuncionesPHP.php');
$TexConfirmar =  $_REQUEST["TexConfirmar"];
$TexClave =  $_REQUEST["TexClave"];
$TexUsuario =  $_SESSION["UsuarioSistema"];

 $con = ConectarBDAD();
        $sTabla='usuario';
        $sCondicion='';
        $sCampos="clave='$TexClave'";
		$sCampos.=",solicitud=0";
        $sCondicion='Nombre= '."'".trim($TexUsuario)."'";
		$SqlStr='update '.$sTabla. ' set '.$sCampos. ' Where ' .$sCondicion;
		        $exc = EjecutarExecAD($con,$SqlStr) or die($SqlStr);
		header("Location: ElegirEmpresa.php");      
?>
