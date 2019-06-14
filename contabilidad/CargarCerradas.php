<?php session_start();?>

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
	$conAd = ConectarBDAd();
	$SqlStr = "SELECT 
		a.codigo
		,a.descripcion
		,b.debe
		,b.haber
	FROM
		sipre_co_config.company a
		INNER JOIN sipre_co_config.comcerrada b ON a.codigo = b.codigo AND mes = $Mes AND ano = $Ano";
	$exc = EjecutarExecAd($conAd,$SqlStr) or die($SqlStr);
	
	echo " 
	<div id='divGeneralPorcentaje'>
	<div class='noprint'><?php include('banner_contabilidad2.php'); ?></div> 
		<table width='100%' name='mitabla' border='1' align='center' class='Acceso'>
			<tr>
				<td class='tituloPaginaContabilidad'>
					Estados Cerrados
				</td>
			</tr>
			<tr>
				<td align='left'>
					<font size=-1><B>Codigo</B></font>
				</td>
				<td align='left'>
					<font size=-1><B>Descripcion</B></font>
				</td>
				<td align='right'>
					<font size=-1><B>Debe</B></font>
				</td>
				<td align='right'>
					<font size=-1><B>Haber</B></font>
				</td>
			</tr>";
	 while ($row=ObtenerFetch($exc)){ 
		$debe = number_format($row[2],2);
		$haber = number_format($row[3],2);
		echo "
			<tr>
				<td align='left'>
					<font size=-1>$row[0]</font>
				</td>
				<td align='left'>
					<font size=-1>$row[1]</font>
				</td>
				<td align='right'>
					<font size=-1>$debe</font>
				</td>
				<td align='right'>
					<font size=-1>$haber</font>
				</td>
			</tr>";
	}
	echo "</table>
	<div class='noprint'>";
		include("pie_pagina.php");
	echo "</div>";
?>