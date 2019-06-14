<?php
require_once ("../connections/conex.php");

session_start();

include("../inc_sesion.php");
	
	if (!validaAcceso("sa_retencion_list")){
		echo "
		<script type=\"text/javascript\">
			alert('Acceso Denegado');
			window.location='index.php';
		</script>";
	}

$currentPage = $_SERVER["PHP_SELF"];

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_sa_retencion_list.php");
include("controladores/ac_iv_general.php");//necesario para el listado de empresa final

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	        
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Listado Comprobante de Retención</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
	
	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
    <style type="text/css">
	.root {
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
	}
	</style>
    <script>
	//no veo que se use en ningun lugar
		function validar(acc){
			if (validarCampo("lstEmpresa","t","lista") == true
			 && validarCampo("lstProveedor","t","lista") == true
			 && validarCampo("txtFecha","t","") == true){
				cadena = $('lstEmpresa').value + '|' + $('lstProveedor').value + '|' + $('txtFecha').value;
				if (acc)
					xajax_listado('','','',cadena);
				else
					verVentana('sa_imprimir_retencion_pdf.php?valBusq='+cadena, 950, 600);
			}
			else{
				validarCampo("lstEmpresa","t","lista") == true
				validarCampo("lstProveedor","t","lista") == true			
				validarCampo("txtFecha","t","") == true
				
				alert("Los campos señalados en rojo son requeridos");
				return false;				
			}
		}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_servicios.php"); ?>
    </div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaServicios">
            	Historico Retenciones
                <br>
			</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
            <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarRetenciones(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                <table border="0" align="left">
                    <tr>
                        <td align="right" width="120" class="tituloCampo">Empresa/Sucursal: </td>
                        <td align="left" id="tdSelEmpresa">
                        
                        </td>
                     </tr>
                 </table>
                 <table>
                     <tr>
                        <td align="right" class="tituloCampo" width="120">
                        Departamento
                        </td>
                        <td align="left" >
                            <select id="departamento" name="departamento">
                                <option value="0">[ Todos ]</option>
                                <option value="1" selected="selected">Servicios</option>                            
                            </select>
                        </td>
                        <td align="right" class="tituloCampo" width="120">
                            Criterio
                        </td>
                        <td align="left"><input type="text" name="txtBusq" id="txtBusq" onkeyup="xajax_buscarRetenciones(xajax.getFormValues('frmBuscar'));"/></td>
                        <td align="left">
    <input type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarRetenciones(xajax.getFormValues('frmBuscar'));" value="Buscar" />
    <input type="button" onclick="document.forms['frmBuscar'].reset(); xajax_buscarRetenciones(xajax.getFormValues('frmBuscar'));" value="Ver Todo" />
                        </td><!--xajax_buscarRetenciones(xajax.getFormValues('frmBuscar'));-->
                    </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td id="tdListadoRetencion"></td>
        </tr>
        </table>
    </div>
    
    <div class="noprint">
	<?php include("menu_serviciosend.inc.php"); ?>
    </div>
</div>
</body>
</html>

<script>
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','','selEmpresa','tdSelEmpresa'); 
xajax_listadoRetencion(0,'idRetencionCabezera','DESC','' + '|' + -1 + '|' + 1);
</script>