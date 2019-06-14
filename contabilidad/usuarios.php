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
<?php
	include_once('FuncionesPHP.php');
	$conectadosNormal= verificarConectados("N");
	if(count($conectadosNormal) > 0){		
			 
		echo "<table align='center' width='100%'>
										<tr class='tituloColumna'>
											<td class=cabecera>Nombre</td>
											<td class=cabecera>Tel&eacute;fono</td>
											<td class=cabecera>Email</td>
											<td class=cabecera>Departamento</td>
											<td class=cabecera>Extensi&oacute;n</td>
										</tr>";
		for($i= 0; $i < count($conectadosNormal); $i++){
			
			echo "<tr><td class=cabecera>".$conectadosNormal[$i]["nombre"]."</td>
					<td class=cabecera>".$conectadosNormal[$i]["tlfn"]."</td>
					<td class=cabecera>".$conectadosNormal[$i]["email"]."</td>
					<td class=cabecera>".$conectadosNormal[$i]["dpto"]."</td>
					<td class=cabecera>".$conectadosNormal[$i]["ext"]."</td></tr>";
		}
		echo "</table>";
	}else{
	$pag = $_SESSION["pag"];
		echo "Usuarios Desconectados<br><br><br><br>
			<button type='submit' value='Volver' name='volver' onclick='javascript:salir($pag);'>Volver</button>";
	}
?>