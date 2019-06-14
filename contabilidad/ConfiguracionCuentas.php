<?php
	session_start();
	require('FuncionesPHP.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
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
</head>

<script language="JavaScript">
	function BuscarCuentas(){
		document.ConfiguracionCuentas.method='post';
		document.ConfiguracionCuentas.target='FrameDetalle1';
		document.ConfiguracionCuentas.action='DetalleConfig1.php';
		document.ConfiguracionCuentas.submit();
	}

	function jSalir(){
		window.close();
	}
</script>

<body>
<div id="divGeneralPorcentaje">
<table border="0" width="100%">
	<tr>
		<td class="tituloPaginaContabilidad">Configuraci&oacute;n de Cuentas</td>
	</tr>
</table>
<form name="ConfiguracionCuentas" action="ConfiguracionCuentas.php" method="post">
<!--<div class="x-box"><div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div></div>
	<div class="x-box"><div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc">
	<div class="x-form-bd" id="container">-->
	<br>
	<!--<div class="x-form-item">-->
	<?php
		$con = ConectarBD();
		$sTabla = 'formatos';
		$sCondicion = '';
		$sCampos = 'formato';
		$sCampos.= ',descripcion';
		$sCampos.= ',titulo_cen';
		$sCondicion.= 'formato = '."'".$T_formato."'";
		$SqlStr = 'SELECT '.$sCampos.' FROM '.$sTabla. ' WHERE ' .$sCondicion;
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		if (NumeroFilas($exc)>0){
			$StatusOculto = 'UP';
			$T_formato = trim(ObtenerResultado($exc,1));
			$T_descripcion = trim(ObtenerResultado($exc,2));
			$Ttitulo_cen = trim(ObtenerResultado($exc,3));
		}else{ // if ( NumeroFilas($exc)>0){
			$StatusOculto = 'LI';
			$T_descripcion = '';
			$Ttitulo_cen = '';
		} // if ( NumeroFilas($exc)>0){
		
		$sCampos = "formato ";
		$sCampos.= ",orden";
		$sCampos.= ",titulo";
		$sCampos.= ",numero";
		
		$sCondicion = " numero = '". $Tnumero ."'";
		$sCondicion.= " AND formato = '". $T_formato ."' ORDER BY orden";
		
		$SqlStr = "SELECT " .$sCampos. " FROM balance_a WHERE ". $sCondicion;
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		if (NumeroFilas($exc)>0){
			$formato = trim(ObtenerResultado($exc,1));
			$orden = trim(ObtenerResultado($exc,2));
			$titulo = trim(ObtenerResultado($exc,3));
			$numero = trim(ObtenerResultado($exc,4));
		}
	?>
	<table width="100%" align="center" class="Acceso">
		<tr>
			<td width="700" height="16"><h3>Dependencia de las cuentas</h3></td>
		</tr>
	</table>
		
	<table width="800" border="0" align="center" class="Accesso">
		<tr class="tituloColumna">
			<td class="cabecera" width="5" align="center" height="16"><a>Formato</a></td>
			<td class="cabecera" width="140" align="center" height="16"><a>Descripci&oacute;n</a></td>
			<td class="cabecera" width="15" align="center" height="16"><a>Orden</a></td>
			<td class="cabecera" width="130" align="center" height="16"><a>T&iacute;tulo Rengl&oacute;n</a></td>
		</tr>	
		<tr>
			<td class="Renglones" width="5" align="left" height="16" valign="top"><a><?php print(strtoupper($T_formato)); ?></a></td>
			<td class="Renglones" width="140" align="left" height="16" valign="top"><a><?php print(strtoupper($T_descripcion)); ?></a></td>
			<td class="Renglones" width="15" align="left" height="16" valign="top"><a><?php print(strtoupper($orden)); ?></a></td>
			<td class="Renglones" width="160" align="left" height="16" valign="top"><a><?php print(strtoupper($titulo)); ?></a></td>
		</tr>	
	</table>
	<p>&nbsp;</p>
	<table width="950" border="0" align="center" class="Accesso">
		<tr>
			<td class="tituloCampo" width="136" align="right"><a>Cuentas:</a></td>
			<td width="152">
				<input class="cTexBox" name="TexBuscar" type="text" value="">
			</td>
			<td width="35" align="left">
				<input class="inputBoton" name="BtnBuscar" type="button" value="..." onClick=" BuscarCuentas()">
			</td>
			<td width="154" align="left">
				<select name="TPoN">
					<option value='+'>+</option>
					<option value='-'>-</option>		
				</select>
			</td>
			<td width="2"></td>
			<td class="tituloColumna"><a>Cuentas Seleccionadas</a></td>
		</tr>
	</table>
	<table width="950" border="0" align="center" class="Accesso">
		<tr class="tituloColumna">
			<td width="51" height="16">C&oacute;digo</td>
			<td width="180" height="16">Descripci&oacute;n</td>
			<td width="51" height="16">C&oacute;digo</td>
			<td width="180" height="16">Descripci&oacute;n</td>
		</tr>
		<tr>
			<td class="cabecera" align="center" height="16" colspan="2" valign="top">
				<iframe name="FrameDetalle1" frameborder="0" width="465" height="250" marginheight="2" marginwidth="2" scrolling="yes" allowtransparency="yes" name="I5" style="border: #DBE2ED 1px solid;" id="cboxmain1" align="left"> </iframe>
			</td>
			<td class="cabecera" align="center" height="16" colspan="2" valign="top">
				<iframe name="FrameDetalle2" frameborder="0" width="465" height="250" marginheight="2" marginwidth="2" scrolling="yes" allowtransparency="yes" name="I5" style="border: #DBE2ED 1px solid;" id="cboxmain1" align="left"> </iframe>
			</td>
		</tr>	
	</table>
	<table width="100%" border="0" align="left" cellpadding="0" cellspacing="0" class="Acceso">
	<p>&nbsp;</p>
		<tr>
			<td width="100%" align="center" colspan="4" class="cabecera2"><input type="button" name="BtnSalir" value="   Salir   " onClick="jSalir();"></td>
		</tr>
	</table>
	<!--</div>-->
	<!--</div></div>
	</div></div></div>-->
		<div class="x-box"><div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div></div>
	</div>
	<input type="hidden" name="T_formato" value="<?php print($T_formato); ?>">
	<input type="hidden" name="Tnumero" value="<?php print($Tnumero); ?>">
	<input type="hidden" name="CuentaSelec" value="">
	<input type="hidden" name="StatusSelec" value="">
	<script language="javascript">
		document.ConfiguracionCuentas.method='post';
		document.ConfiguracionCuentas.target='FrameDetalle2';
		document.ConfiguracionCuentas.StatusSelec.value = '';
		document.ConfiguracionCuentas.action='DetalleConfig2.php';
		document.ConfiguracionCuentas.submit();
	</script>
</form>
</div>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<div class="noprint">
	<?php include("pie_pagina.php"); ?>
</div>
</body>
</html>