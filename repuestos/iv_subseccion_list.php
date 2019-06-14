<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_subseccion_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require_once('../clases/rafkLista.php');
$currentPage = $_SERVER["PHP_SELF"];

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_subseccion_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Sub-Secciones</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../clases/styleRafkLista.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
    <script>
	function validarForm() {
		if (validarCampo('txtSubSeccion','t','') == true
		&& validarCampo('lstSeccion','t','lista') == true
		) {
			xajax_guardarSubSeccion(xajax.getFormValues('frmSubSeccion'));
		} else {
			validarCampo('txtSubSeccion','t','');
			validarCampo('lstSeccion','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Sub-Secciones</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_formSubSeccion();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    	<button type="button" onclick="if (confirm('¿Desea eliminar los registros seleccionado(s)?') == true) xajax_eliminarSubSeccion(xajax.getFormValues('frmListaSubSeccion'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
					</td>
                </tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaSubSeccion" name="frmListaSeccion" style="margin:0">
            	<?php
                $objSubSec = new lista();
                $objSubSec->iniciar(15, 0, "id_subseccion", "DESC", $currentPage, "SubSec");
                $query = "SELECT
					iv_subsecciones.id_subseccion,
					iv_secciones.*,
					iv_subsecciones.descripcion AS descripcion_subseccion,
					(CASE tipo_seccion
						WHEN 1 THEN 'Repuesto'
						WHEN 2 THEN 'Activo'
						WHEN 3 THEN 'Servicio'
						WHEN 4 THEN 'Otro'
					END) AS descripcion_tipo_seccion
				FROM iv_subsecciones
					INNER JOIN iv_secciones ON (iv_subsecciones.id_seccion = iv_secciones.id_seccion)
					WHERE iv_subsecciones.descripcion <> 'NA'";
                $rsSubSec = $objSubSec->consulta($database_conex, $conex, $query);
				
                echo $objSubSec->tabla(
					array(
						array("","","","center","checkbox","cbxSubSec"),
						array("Id","8%","id_subseccion","right"),
						array("Seccion","20%","descripcion","left"),
						array("Descripción Sub-Sección","44%","descripcion_subseccion","left"),
						array("Abreviatura Seccion","12%","corta","left"),
						array("Tipo Seccion","16%","descripcion_tipo_seccion","center")),
					$rsSubSec[0],
					array(
						array("../img/iconos/pencil.png","javascript:xajax_cargarSubSeccion('|id_subseccion|');","onclick")));
                ?>
			</form>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmSubSeccion" name="frmSubSeccion" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblSeccion" width="450px">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Sub-Sección</td>
                <td width="75%"><input type="text" id="txtSubSeccion" name="txtSubSeccion" maxlength="50" size="50"/><input type="hidden" id="hddIdSubSeccion" name="hddIdSubSeccion" /></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Sección:</td>
                <td id="tdlstSeccion">
                	<select id="lstSeccion" name="lstSeccion">
                    	<option value="-1">[ Seleccione ]</option>
                    </select>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" onclick="validarForm();">Guardar</button>
            <button type="button" onclick="$('divFlotante').style.display='none';">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>