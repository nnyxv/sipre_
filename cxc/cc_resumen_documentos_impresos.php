<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cc_resumen_documentos_impresos"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_resumen_documentos_impresos.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Resumen documentos impresos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css"/>
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
        <style>
            
            /*tablas completas*/
            .tabla-resumen{         
                border-width:1px;
                border-style: solid;
                border-color:#cccccc;
                width:900px;
                text-align:left;
                font-size:14px;
            }
            
            /*todos los td, sin cabecera titulos*/
            .tabla-resumen td{
                padding:3px;
            }
            
            /*segunda columna - la ultima*/
            .tabla-resumen td:last-child{                
                text-align:center;
                width:150px;
            }
            
/*            .tabla-resumen tr:nth-last-child(-n+3){                
                text-align:right;                
            }*/
            
            /*cabecera titulos*/
            .tabla-resumen th{
                background-color:#cccccc;
                padding:4px;
            }
            
            /* ultima fila ultima columna */
            .tabla-resumen tr:last-child td:last-child{
                background-color:#e6ffe6;
                font-weight:bold;
            }
            
            /* ultima fila dos ultimas columnas*/
            .tabla-resumen tr:last-child td:nth-last-child(-n+2){
                font-weight:bold;
                border-top-style:solid;
                border-top-width:1px;
            }
            
            /*ultimos dos filas <tr> en sus ultimos <td> finales*/
/*            .tabla-resumen tr:nth-last-child(-n+2) td:last-child{
                background-color:#e6ffe6;
                font-weight:bold;
            }*/
            
        </style>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
			
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr class="solo_print">
			<td align="left" id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
			<td class="tituloPaginaCuentasPorCobrar" colspan="2">Resumen documentos impresos</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr class="noprint">
			<td>
				<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
					<table align="right" border="0">
					<tr align="left">
						<td align="right" width="120" class="tituloCampo">Empresa:</td>
						<td id="tdlstEmpresa">
							<select id="lstEmpresa" name="lstEmpresa">
								<option value="-1">[ Todos ]</option>
							</select>
						</td>
					</tr>
					<tr align="left">
						<td align="right" width="120" class="tituloCampo">Fecha:</td>
						<td>
							<table cellpadding="0" cellspacing="0">
							<tr>
								<td>&nbsp;Desde:&nbsp;</td>
								<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
								<td>&nbsp;Hasta:&nbsp;</td>
								<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
							</tr>
							</table>
						</td>
					</tr>
					<tr align="left">
						
					</tr>
					<tr align="left">
						<td ></td>
						<td ></td>
						<td ></td>
						<td ></td>	
						<td>
							<button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarDocumentos(xajax.getFormValues('frmBuscar'));">Buscar</button>							
						</td>
                                                <td><button type="button" id="btnLimpiar" name="btnLimpiar" onclick="limpiar();">Limpiar</button></td>
                                                    
                                                <td width="200"></td>
					</tr>
					</table>
				</form>
			</td>
		</tr>
		<tr>
			<td id="tdResumen" colspan="2" align="center"></td>
		</tr>
		<tr>
			<td>
<!--				<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25" class="puntero"/></td>
					<td align="center">
						<table>
						<tr>
							<td><img src="../img/iconos/ico_view.png"/></td>
							<td>Ver</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/print.png"/></td>
							<td>Imprimir</td>
							<td>&nbsp;</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>-->
			</td>
		</tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
    
    function limpiar(){       
        byId('lstEmpresa').value = "<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>";
        byId('txtFechaDesde').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
        byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";
        byId('btnBuscar').click();
    }
    
byId('txtFechaDesde').className = 'inputHabilitado';
byId('txtFechaHasta').className = 'inputHabilitado';

byId('txtFechaDesde').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"purple"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"purple"
	});
};

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_resumenDocumentos('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('txtFechaDesde').value + '|' + byId('txtFechaHasta').value);
</script>