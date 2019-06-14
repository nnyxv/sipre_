<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("sa_vale_salida_historico_list"))) {//sa_vale_salida_historico_list nuevo gregor  //sa_historico_vale_salida antes
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_sa_vale_salida_historico_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	        
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Histórico de Vales de Salida</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
        
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_servicios.php"); ?>
    </div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaServicios">Histórico de Vales de Salida</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
		</tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_exportarHistorico(xajax.getFormValues('frmBuscar'));" class="noprint" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
                    </td>
                    <td>
                        
                        <button type="button" id="btnImprimir" name="btnImprimir" class="noprint" onclick="window.print();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/print.png" alt="print"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                    </td>
                </tr>
                </table>
            	
			<form id="frmBuscar" name="frmBuscar" onsubmit="$('btnBuscar').click(); return false;" style="margin:0">
            	<table border="0" align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td colspan="6" id="tdlstEmpresa">
                     <!--   <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Todos ]</option>
                        </select>-->
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="100">Desde:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtFechaDesde" name="txtFechaDesde" readonly="readonly" size="10" style="text-align:center" value="<?php echo date("01-m-Y")?>"/>
                    </div>
                    <div style="float:left">
                    	<img src="../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero noprint"/>
						<script type="text/javascript">
                            Calendar.setup({
                            inputField : "txtFechaDesde",
                            ifFormat : "%d-%m-%Y",
                            button : "imgFechaDesde"
                            });
						</script>
                    </div>
                    </td>
                    <td align="right" class="tituloCampo" width="100">Hasta:</td>
                    <td>
                    <div style="float:left">
                    	<input type="text" id="txtFechaHasta" name="txtFechaHasta" readonly="readonly" size="10" style="text-align:center" value="<?php echo date("d-m-Y")?>"/>
					</div>
                    <div style="float:left">
                    	<img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero noprint"/>
						<script type="text/javascript">
                            Calendar.setup({
                            inputField : "txtFechaHasta",
                            ifFormat : "%d-%m-%Y",
                            button : "imgFechaHasta"
                            });
						</script>
                    </div>
                    </td>
                    <td align="right" class="tituloCampo" width="100">Vendedor:</td>
                    <td colspan="2" id="tdlstEmpleadoVendedor">
                        <select id="lstEmpleadoVendedor" name="lstEmpleadoVendedor">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                	<td align="right" class="tituloCampo" width="110">Tipo de Orden:</td>
                    <td id="tdlstTipoOrden">
                        <select id="lstTipoOrden" name="lstTipoOrden">
                            <option value="-1">[ Todos ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="$('btnBuscar').click();"></td>
                    <td>
                    	<input type="button" class="noprint" id="btnBuscar" onclick="xajax_buscarOrden(xajax.getFormValues('frmBuscar'));" value="Buscar" />
						<input type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" value="Limpiar" />
                    </td>
                </tr>
                    <tr>
                        <td colspan="4">                            
                            <input class="puntero" type="checkbox" name="conRepuestos" id="conRepuestos" value="1"/><label class="puntero" for="conRepuestos">Con Repuesto&nbsp;</label>
                            <input class="puntero" type="checkbox" name="conManos" id="conManos" value="1"/><label class="puntero" for="conManos">Con Manos de Obra&nbsp;</label>
                            <input class="puntero" type="checkbox" name="conTot" id="conTot" value="1"/><label class="puntero" for="conTot">Con Tot&nbsp;</label>
                            <input class="puntero" type="checkbox" name="conNotas" id="conNotas" value="1"/><label class="puntero" for="conNotas">Con Notas de Cargo</label>
                        </td>
                        <td  class="tituloCampo" align="right">
                            Modo Fltro:
                        </td>
                        <td>
                            <select name="modoFiltro" id="modoFiltro">
                                <option value="1">Individual</option>
                                <option value="2">Combinado</option>                                
                            </select>
                        </td>
                    </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaOrdenes" name="frmListaOrdenes" style="margin:0">
				<div id="divListaOrdenes"></div>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                        <tr>
                            <td><img src="../img/iconos/ico_gris.gif" /></td>
                            <td>Vales Devueltos</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_morado.gif" /></td>
                            <td>Vales de Salida</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>

<script>
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="$(\'btnBuscar\').click(); cargarTipoOrdenEmpresa(this.value); cargarEmpleadoEmpresa(this.value); "'); //buscador

//xajax_cargaLstEmpresaBuscar('<?php //echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEmpleado('','lstEmpleadoVendedor','tdlstEmpleadoVendedor');
xajax_cargaLstTipoOrden();
xajax_cargaLstEstadoOrden();

function cargarTipoOrdenEmpresa(empresa){
	xajax_cargaLstTipoOrden('',empresa);
}

function cargarEmpleadoEmpresa(empresa){
	xajax_cargaLstEmpleado('','lstEmpleadoVendedor','tdlstEmpleadoVendedor',empresa);
}

xajax_listadoOrdenes(0,'numero_vale','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|'+$('txtFechaDesde').value+'|'+$('txtFechaHasta').value);
</script>