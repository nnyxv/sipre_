<?php 
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_apertura_caja"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_apertura_caja.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Apertura de Caja</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCaja.css"/>

	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script language="javascript" type="text/javascript">
    function validarFrmApertura() {
		error = false;
        if (!(validarCampo('txtCargaEfectivo','t','numPositivo') == true)){
            validarCampo('txtCargaEfectivo','t','numPositivo');
            
            error = true;
        }
        
        if (error == true) {
            alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
            return false;
        } else {
            xajax_aperturaCaja(xajax.getFormValues('frmApertura'));
        }
    }
	
	function validarFrmReapertura(fechaApertura) {
		error = false;
        if (!(validarCampo('txtCargaEfectivo','t','numPositivo') == true)){
            validarCampo('txtCargaEfectivo','t','numPositivo');
            
            error = true;
        }
        
        if (error == true) {
            alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
            return false;
        } else {
			if (confirm('Esta caja ya tiene un registro de apertura con fecha ' + fechaApertura + '.\n ¿Seguro desea aperturar otra con la misma fecha?') == true) {
	            xajax_aperturaCaja(xajax.getFormValues('frmApertura'), 'true');
			}
        }
    }
    
    function horaActual() {
        momentoActual = new Date();
        
        hora = momentoActual.getHours();
        minuto = momentoActual.getMinutes();
        segundo = momentoActual.getSeconds();
        
        tiempo = "a.m."
        if (parseInt(hora) == 0) {
            hora = 12;
        } else if (parseInt(hora) > 12) {
            hora = hora - 12;
            tiempo = "p.m."
        }
        
        if (parseInt(minuto) >= 0 && parseInt(minuto) <= 9)
            minuto = "0" + minuto;
            
        if (parseInt(segundo) >= 0 && parseInt(segundo) <= 9)
            segundo = "0" + segundo;
    
        horaImprimible = hora + ":" + minuto + ":" + segundo + " " + tiempo;
        
        document.getElementById('tdHoraActual').innerHTML = horaImprimible
        
        setTimeout("horaActual()",1000)
    }
    </script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cj.php"); ?></div>
    
	<div id="divInfo" class="print" style="vertical-align:middle">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCaja">Apertura de <?php echo $nombreCajaPpal; ?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right">
			<form id="frmApertura" name="frmApertura" style="margin:0">
				<table border="0" width="100%" align="center">
				<tr>
					<td>
                    <fieldset><legend class="legend">Apertura de Caja</legend>
						<table border="0" width="100%" align="center">
						<tr>
							<td valign="top">
								<table border="0" align="center">
                                <tr align="left">
									<td align="right" class="tituloCampo" width="120">Empresa:</td>
									<td width="180"><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" class="inputCompleto" readonly="readonly"/></td>
									<td align="right" class="tituloCampo" width="120"><?php echo $spanRIF; ?>:</td>
									<td width="180"><input type="text" id="txtRif" name="txtRif" class="inputCompleto" readonly="readonly" style="text-align:center"/></td>
								</tr>
                                <tr align="left">
									<td align="right" class="tituloCampo">Estado de Caja:</td>
                                    <td colspan="3"><input type="text" id="txtEstadoCaja" name="txtEstadoCaja" style="text-align:center; width:99%" readonly="readonly"/></td>
									
								</tr>
                                <tr align="left"><td align="right" class="tituloCampo">Fecha de Apertura:</td>
									<td><input type="text" id="txtFechaApertura" name="txtFechaApertura" class="inputCompleto" readonly="readonly" style="text-align:center"/></td>
									<td align="right" class="tituloCampo">Aperturada por:</td>
                                    <td><input type="text" id="txtEmpleadoApertura" name="txtEmpleadoApertura" class="inputCompleto" readonly="readonly"/></td>
								</tr>
                                <tr align="left">
									<td align="right" class="tituloCampo">Saldo de Caja</td>
									<td><input type="text" id="txtSaldoCaja" name="txtSaldoCaja" class="inputCompleto" readonly="readonly" style="text-align:right" value="0.00"/></td>
									<td align="right" class="tituloCampo">Carga de Efectivo</td>
									<td><input type="text" id="txtCargaEfectivo" name="txtCargaEfectivo" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
                    </fieldset>
					</td>
				</tr>
				<tr>
					<td align="right"><hr>
						<button type="button" id="btnApertura" name="btnApertura" onclick="validarFrmApertura();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/key.png"/></td><td>&nbsp;</td><td>Aperturar</td></tr></table></button>
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
xajax_cargarDatosCaja();
</script>