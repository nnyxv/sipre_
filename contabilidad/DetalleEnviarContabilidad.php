<?php session_start();?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>.: SIPRE 2.0 :. Contabilidad - Enviar a Contabilidad</title>
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

<body>        
<div id="divGeneralPorcentaje">   
<?php
	include_once('GenerarEnviarContabilidad.php');
	$xFechaD = "$fechaD";
	$xFechaH = "$fechaH";
	eliminarRenglones();
	
	generarComprasAd(0,$xFechaD,$xFechaH);//LISTO /*F 1*/
	generarComprasVe(0,$xFechaD,$xFechaH);//LISTO /*F 2*/
	generarComprasRe(0,$xFechaD,$xFechaH);//LISTO /*F 3*/
	generarComprasSe(0,$xFechaD,$xFechaH);//LISTO /*F 4*/
	generarVentasAd(0,$xFechaD,$xFechaH);//LISTO /*F 4.1*/
	generarVentasRe(0,$xFechaD,$xFechaH);//LISTO /*F 5*/
	generarVentasVe(0,$xFechaD,$xFechaH);//LISTO /*F 6*/
	generarVentasSe(0 ,$xFechaD,$xFechaH); ///*F 7*/ PENDIENTE PROBAR -- 26 - 27
	generarNotasVentasSe(0,$xFechaD,$xFechaH);//LISTO /*F 8*/
	generarNotasVentasVe(0,$xFechaD,$xFechaH);//LISTO /*F 9*/
	generarNotasRe(0,$xFechaD,$xFechaH);//LISTO /*F 10*/
	generarNotasVeSinDetalle(0,$xFechaD,$xFechaH);//LISTO /*F 11*/
	generarNotasReSinDetalle(0,$xFechaD,$xFechaH);//LISTO /*F 11*/
	generarNotasSeSinDetalle(0,$xFechaD,$xFechaH);//LISTO /*F //*/
	generarNotasAdSinDetalle(0,$xFechaD,$xFechaH);//LISTO /*F 11.1*/
	generarNotasAdConDetalle(0,$xFechaD,$xFechaH);//LISTO /*F 11.1*/
	generarValeSe(0,$xFechaD,$xFechaH);//PENDIENTE PROBAR
	generarDepositoTe(0,$xFechaD,$xFechaH);//LISTO /*F 13*/
	generarDepositosTeRe(0,$xFechaD,$xFechaH);//LISTO /*F 14*/
	generarDepositosTeVe(0,$xFechaD,$xFechaH);//LISTO /*F 15*/
	generarTransferenciaTe(0,$xFechaD,$xFechaH);//LISTO /*F 16*/
	////////////////////////////////////////////////////////////
	generarAnularTransferenciaTe(0,$xFechaD,$xFechaH);
	////////////////////////////////////////////////////////////
	generarNotaCreditoTe(0,$xFechaD,$xFechaH);//LISTO /*F 17*/
	generarNotaDebitoTe(0,$xFechaD,$xFechaH);//LISTO /*F 18*/
	generarChequesTe(0,$xFechaD,$xFechaH);//LISTO /*F 19*/
	generarChequesAnuladoTe(0,$xFechaD,$xFechaH);//LISTO /*F 20*/
	generarCajasTe(0,$xFechaD,$xFechaH);//ANULADA
	generarCajasEntradaNotasCargoRe(0,$xFechaD,$xFechaH);//LISTO /*F 22*/
	generarCajasEntradaNotasCargoVe(0,$xFechaD,$xFechaH);//LISTO /*F 23*/
	generarCajasEntradaRe(0,$xFechaD,$xFechaH);//LISTO /*F 24*/
	generarCajasEntradaVe(0,$xFechaD,$xFechaH);//LISTO /*F 25*/
	generarAnticiposRe(0,$xFechaD,$xFechaH);//LISTO /*F 26*/
	generarAnticiposVe(0,$xFechaD,$xFechaH);//LISTO /*F 27*/
	generarConceptosAnticiposVe(0,$xFechaD,$xFechaH);//LISTO /*F 27.1*/
	generarValeEntradaRe(0,$xFechaD,$xFechaH);// LISTO /*F 28*/
	generarValeEntradaVe(0,$xFechaD,$xFechaH);// LISTO /*F 30.1*/
	generarValeSalidaRe(0,$xFechaD,$xFechaH);// LISTO /*F 29*/
	generarValeSalidaVe(0,$xFechaD,$xFechaH);// LISTO /*F 30*/
	generarNotasCargoRe(0,$xFechaD,$xFechaH);// LISTO /*F 32*/
	generarNotasCargoVe(0,$xFechaD,$xFechaH);// LISTO /*F 33*/
	generarNotasCargoCpRe(0,$xFechaD,$xFechaH);// LISTO /*F 33*/
	generarNotasCargoCpVe(0,$xFechaD,$xFechaH);// LISTO /*F 34*/
	generarNotasCargoCpAd(0,$xFechaD,$xFechaH);// LISTO /*F 35*/
	generarNotasCreditoCpAd(0,$xFechaD,$xFechaH);//LISTO /*F 36*/
	generarNotasCreditoCpVe(0,$xFechaD,$xFechaH);//LISTO /*F 39*/
	generarNotasCreditoCpRe(0,$xFechaD,$xFechaH);//LISTO /*F 37*/
	//generarNotasCreditoCpReParcial(0,$xFechaD,$xFechaH);//LISTO /*F 37.1*/
	generarNotasCreditoCpSe(0,$xFechaD,$xFechaH);//LISTO F 38*/
	generarComisionesBancarias(0,$xFechaD,$xFechaH);//LISTO /*F 40*/
?>

<table width="100%" align="center">
	<tr>
		<td class="tituloCampo" width="142" align="right"> 
<?php    
	$SqlStr = "SELECT a.codigo,a.descripcion 
	FROM centrocosto a
	WHERE a.activo = 0";//		
	
	$conAd = ConectarBD();
	$exc = EjecutarExec($conAd,$SqlStr) or die($SqlStr);
?>
			M&oacute;dulos:
		</td>
        <td width="188" align="left">
			<select name="idcc" onchange="llamartrans();">
				<option value="">Seleccione...</option>
               
<?php 
	while ($row=ObtenerFetch($exc)){ 
		$id = $row[0];
		$des = $row[1];
		echo "<option value=$id>$des</option>";
	}
?>
			</select>

		</td>		
		<td class="tituloCampo" width="142" align="right"> 	
			Transacci&oacute;n:
    	</td>
        <td width="188" align="left">
        	<div id="divtran">     
                <select name="idct" onchange="LimpiaDetalle();">
                    <option value="">Seleccione...</option>
                </select>
			</div>
		</td>	
		<td class="tituloCampo" width="142" align="right"> 	
			D&iacute;a: 
        </td>
        <td width="188" align="left">
        	<div id=comboDia>
				<select name="idDia">
					<option>Seleccione...</option>
				</select> 
			</div>  
		</td>		
 	</tr>
</table>
<br>

<iframe  name="FrameDetalle" id="FrameDetalle" frameborder="0"  width="1240" height="300"  marginheight="2" marginwidth="2" scrolling="yes" allowtransparency="yes" style="border: #DBE2ED 0px solid;" align="left"> </iframe>
<table width="100%">
	<tr>
  		<td  height=20 align="center" valign=top> 
			<button type="button" value="Contabilizar" name="btnContabilizar" onclick="Contabilizar();">Contabilizar</button>
			<!--<input type="button" value="Contabilizar por Centro de Costo" name="btnContabilizar" onclick="Contabilizar();">-->
		</td>		
	</tr>
</table>
</div>
</body>
</html>
