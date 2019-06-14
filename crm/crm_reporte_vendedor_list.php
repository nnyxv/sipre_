<?php
require_once("../connections/conex.php");
	
session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_seguimiento_historico_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');//clase xajax
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');//Configuranto la ruta del manejador de script
$xajax->configure( 'defaultMode', 'synchronous' ); 

include("controladores/ac_crm_reporte_vendedor_list.php"); //contiene todas las funciones xajax
include("../controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Control de Trafico</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>

    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCrm.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    																					
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script src="../js/highcharts/js/highcharts.js"></script>
	<script src="../js/highcharts/js/modules/exporting.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_crm.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table width="100%" border="0"> <!--tabla principa-->
            <tr><td class="tituloPaginaCrm">Reporte por Vendedor</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
            	<td>
                	<table border="0" width="100%">
                    	<tr>
                            <td valign="top" align="left" width="46%">
                               <button type="button" onclick="xajax_exportarCliente(xajax.getFormValues('frmBuscar'));" style="cursor:default">
                            		<table align="center" cellpadding="0" cellspacing="0">
                            			<tr>
                            				<td>&nbsp;</td>
                            				<td><img src="../img/iconos/page_excel.png"/></td>
                            				<td>&nbsp;</td><td>XLS</td>
                            			</tr>
                            		</table>
                            	</button>
                            </td>
                            <td align="right" width="54%">
                            	<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                                    <table border="0" width="100%">
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Empresa:</td>
                                            <td id="tdlstEmpresa">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Vendedor:</td>
                                            <td id="tdLstVendedor">&nbsp;</td>
                                        </tr>
                                        
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Estatus:</td>
                                            <td id="tdLstEstatus">&nbsp;</td>
                                        </tr>
                                        
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Fecha Creacion</td>
                                            <td>
                                            	Desde: <input id="textDesdeCreacion" name="textDesdeCreacion" class="inputHabilitado" value="<?php echo date("d-m-Y") ?>" style="width:25%; text-align:center" />
                                                Hasta: <input id="textHastaCreacion" name="textHastaCreacion" class="inputHabilitado" value="<?php echo date("d-m-Y") ?>" style="width:25%; text-align:center" />
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="120">Criterio</td>
                                            <td>
                                            	<input id="textCriterio" name="textCriterio" class="inputHabilitado" style="width:74%;" onblur="byId('btnBuscar').click();" />
                                            </td>
                                            <td align="left">
                                                <button type="button" id="btnBuscar" onclick="xajax_buscarSeguimiento(xajax.getFormValues('frmBuscar'))">Buscar</button>
                                                <button type="button" id="btnLimpiar" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
                                            </td>
                                        </tr>
                                    </table>
                                </form>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr><td align="left" ><h2><?php echo date("l, F d Y"); ?></h2></td></tr>
            <tr>
            	<td>
                	<form id="frmLstSeguimiento" name="frmLstSeguimiento" onsubmit="return false;" style="margin:0">
                    	<div id="divLstSeguimiento"></div>
                    </form>
                </td>
            </tr>
            <tr align="right">
            </tr>
        </table>

    </div> <!-- fin contenedor interno-->

    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div> <!--fin del contenedor general-->
</body>
</html>

<script type="text/javascript">
	
    
	xajax_cargaLstVendedor('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
	xajax_cargaLstEstatus('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
    xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>','onchange=\"xajax_cargaLstVendedor(this.value); byId(\'btnBuscar\').click();\"');
	xajax_listaEmpleado(0,"","","");
	xajax_lstSeguimiento(0,"seguimiento.id_seguimiento","ASC","<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||"+"<?php echo date("Y-m-d"); ?>"+"|"+"<?php echo date("Y-m-d"); ?>");
	
	new JsDatePick({
		useMode:2,
		target:"textHastaCreacion",
		cellColorScheme :"bananasplit",
		dateFormat:"%d-%m-%Y"
	});
	
	new JsDatePick({
		useMode:2,
		target:"textDesdeCreacion",
		cellColorScheme :"bananasplit",
		dateFormat:"%d-%m-%Y"
	});

</script>