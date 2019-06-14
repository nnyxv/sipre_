<?php 
session_start();
$idUsuario = $_SESSION['idUsuarioSysGts'];
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts']; 
?>
    
    
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title>.: SIPRE 2.0 :. Contabilidad - Usuarios Conectados</title>
<meta http-equiv="Content-Type"content="text/html; charset=iso-8859-1">

<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
<link rel="stylesheet" type="text/css" href="../js/domDragContabilidad.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
        
	<script language="javascript">
  		function objetoAjax(){
			var xmlhttp=false;
			try{
				xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			}catch(e){
			try {
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			}catch(E){
				xmlhttp = false;
			}
			}
			if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
				xmlhttp = new XMLHttpRequest();
			}
			return xmlhttp;
		}

		function cargar(e, url){
			//donde se mostrará los registros
			divContenido = document.getElementById(e);
			ajax=objetoAjax();
			//uso del medoto GET
			//indicamos el archivo que realizará el proceso de paginar
			//junto con un valor que representa el nro de pagina
			ajax.open("GET", url);
			//divContenido.innerHTML= '<img src="../view/images/mozilla_blu.gif">';
			ajax.onreadystatechange=function() {
				if (ajax.readyState==4) {
					//mostrar resultados en esta capa
					divContenido.innerHTML = ajax.responseText
				}
			}
			//como hacemos uso del metodo GET
			//colocamos null ya que enviamos 
			//el valor por la url ?pag=nropagina
			ajax.send(null)
		}
	</script>
	<script language="javascript">
		function LIMPIAR(){
		document.frmPantallaLimpiar.target='topFrame';
		document.frmPantallaLimpiar.method='post';
		document.frmPantallaLimpiar.action='LimpiarUsuarios.php';
		document.frmPantallaLimpiar.submit();			
	   }
		function refrescar(){
			window.setInterval("cargar('capa1', 'usuarios.php')",1000);
		}
		function salir(pag){
		   if (pag  == 1){
				location.href="frmPantallaCierreMes.php";
			}else if (pag  == 2){
				location.href="frmPantallaActualizar.php";
			}else if (pag  == 3){
				location.href="frmPantallaAsientodeCierre.php";
			}else if (pag == 4){
				location.href="frmEnviaraContabilidad.php";
			}else if (pag == 5){
				location.href="frmPantallaAsientodePreCierre.php";
			}
		}
	</script>
</head>
<body>
<div id="divGeneralPorcentaje"> 
<div class="noprint"><?php include("banner_contabilidad2.php"); ?></div>  
<div id="divInfo" class="print">
	<table border="0" width="100%">
    	<tr>
	        <td class="tituloPaginaContabilidad">Usuarios Conectados</td>            
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
    </table> 

		<div class="x-form-item">
    		<div id="capa1">																			 
				<script language='javascript'>
					refrescar();				
				</script>
			</div>																			
		</div>

	<form name="frmPantallaLimpiar">
		<table width="100%" border=0 align=center height=0 cellpadding=0 cellspacing=0 class=cabecera>
	   		<tr>
  				<td height=20 valign=top align="right"><hr/>
                    <button type="submit" NAME="BTNLIMPIAR" ONCLICK="LIMPIAR()" value="Limpiar Comprobantes">Limpiar Comprobantes</button>
				</td>
			 </TR>
		</table>	
        </form>
</div> 
    
<div class="noprint"><?php include("pie_pagina.php"); ?></div>

        
	</body>
</html>