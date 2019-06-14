<?php
//Nota: este es una copia exacta de "sa_orden_tot_list.phph" que necesitaba 2 archivos para cambiar el get acc, en bd ya estaban 2
require_once ("../connections/conex.php");

session_start();
define('PAGINA', 1);//Define que pag es para no usar $GET por url sino pasar, cambiar la constante en las 2 paginas del tot

include ("../inc_sesion.php");

$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_sa_orden_tot_list.php");

include("controladores/ac_iv_general.php");//necesario para el listado de empresa final

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Listado de Ordenes de TOT</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
	
	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <style type="text/css">
	/*.root {
		background-color:#FFFFFF;
		border:6px solid #999999;
		font-family:Verdana, Arial, Helvetica, sans-serif;
		font-size:11px;
		max-width:1050px;
		position:absolute;
	}

	.handle {
		padding:2px;
		background-color:#000066;
		color:#FFFFFF;
		font-weight:bold;
		cursor:move;
	}*/
	</style>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_servicios.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaServicios" id="tdReferenciaPagina">Ordenes de TOT</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
            	<table align="left">
                <tr>
                	<!--<td><input class="noprint" type="button" value="Nuevo" onclick="window.open('sa_orden_compra_tot.php?id=0&accion=1&acc=<?php echo $_GET['acc'] ?>','_self');"/></td>-->
                    <td><button onclick="window.print();" class="noprint" name="btnImprimir" id="btnImprimir" type="button">
                    	<table cellspacing="0" cellpadding="0" align="center"><tbody>
                        <tr><td>&nbsp;</td><td><img alt="print" src="../img/iconos/print.png"></td><td>&nbsp;</td><td>Imprimir</td></tr></tbody></table>
                        </button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarOrden(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                <table align="right" border="0">
                <tr>
                	<td align="right" class="tituloCampo" width="100">Empresa:</td>
                    <td id="tdlstEmpresa">
                    <select id="lstEmpresa" name="lstEmpresa">
                        <option value="-1">Todos...</option>
                    </select>
                    <script>
                    //xajax_cargaLstEmpresas();
                    </script>
                    </td>
                    <td align="right" class="tituloCampo" width="150">N&deg; Orden Servicio / TOT:</td>
                    <td id="tdlstAno"><input type="text" id="txtPalabra" name="txtPalabra" onkeyup="$('btnBuscar').click();"/></td>
                    <td>
                        <input type="button" class="noprint" id="btnBuscar" onclick="xajax_listadoTOT(0,'sa_orden.numero_orden','DESC',$('lstEmpresa').value + '|' + $('txtPalabra').value);" value="Buscar" />
						<input type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" value="Ver Todo" />
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td id="tdListaTOT"></td>
        </tr>
        <tr>
        	<td>
            	<table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                <tbody>
                    <tr>
                        <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                        <td align="center">
                            <table>
                            <tbody>
                                <tr>
                                    <td><img src="../img/iconos/ico_view.png"></td><td>Ver Detalle</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/page_white_acrobat.png"></td><td>Registro Compra PDF</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_print.png"></td><td>Comprobante de Retención</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/page_red.png"></td><td>Comprobante de Retención ISLR</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/delete.png"></td><td>Colocar TOT en cero</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/new_window.png"></td><td>Movimiento Contable</td>
                                </tr>
                            </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
                </table>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint">
	<?php include("menu_serviciosend.inc.php"); ?>
    </div>
</div>
</body>
</html>


<div id="divFormulario" style="display:none;"></div>

<script>
	xajax_listadoTOT(0,'sa_orden.numero_orden','DESC',<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>);
</script>
<script language="javascript">
    function activarMovimiento(){
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
    }
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',''); 
</script>