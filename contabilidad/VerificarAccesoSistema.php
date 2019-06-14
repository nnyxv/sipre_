<?php
session_start();
include_once('FuncionesPHP.php');
$TexUsuario =  $_REQUEST["TexUsuario"];
$TexClave =  $_REQUEST["TexClave"];

/*$TexUsuario =  $_SESSION["nombreUsuarioSysGts"];
$TexClave =  "1234";*/
if ($TexUsuario == ""){
	$sMensaje ='Introduzca el Usuario';
	//header("Location: FrmIzquierda.php?TMensaje=$sMensaje");
	header("Location: http://yokomuroccs.dyndns.org:9000/sipre_automotriz/");
	return;
}
if ($TexClave == ""){
	$sMensaje ='Introduzca la clave';
	//header("Location: FrmIzquierda.php?TMensaje=$sMensaje");       
	return;
}

$con = ConectarBDAD();
$sTabla = 'usuario';
$sCondicion = '';
$sCampos .= 'Nombre';
$sCampos .= ',clave';
$sCampos .= ',Acceso';
$sCampos .= ',centrocosto';
$sCampos .= ',solicitud';
$sCondicion .= 'Nombre= '."'".trim($TexUsuario)."'";
$sCondicion .= ' and clave= '."'".trim($TexClave)."'";
$SqlStr = 'SELECT '.$sCampos.' FROM '.$sTabla. ' WHERE ' .$sCondicion;
$exc = EjecutarExecAD($con,$SqlStr) or die(mysql_error());
if ( NumeroFilas($exc) > 0) {
	$_SESSION['SisNombreUsuario'] = $TexUsuario;
	$_SESSION["UsuarioSistema"] =trim(ObtenerResultado($exc,1)) ;
	$_SESSION["AccesoSistema"] =trim(ObtenerResultado($exc,3)) ;
	$_SESSION["CCSistema"] = "";
	if(substr(trim(ObtenerResultado($exc,3)),0,1) == "E"){
		$_SESSION["CCSistema"] = trim(ObtenerResultado($exc,4));
	}	  
	if (ObtenerResultado($exc,5) == 1){
		header("Location: FrmCambiodeClave.php"); 
		return;			  
	}
	if(count($conectadoExclusivo) > 0){
		echo "<script language= 'javascript'>
		alert('Disculpe la molestia en est o momentos se encuentra un usuario exclusivo conectado, intente mas tarde');
		parent.window.close();
		</script>";
	}
	header("Location: ElegirEmpresa.php");      
} else { // if ( NumeroFilas($exc)>0){
	$sMensaje ='Usuario o Clave Errada';
	header("Location: FrmIzquierda.php?TMensaje=$sMensaje");       
} // if ( NumeroFilas($exc)>0){ ?>