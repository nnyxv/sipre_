<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_seccion_list"))) {
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
include("controladores/ac_iv_seccion_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Secciones</title>
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
		if (validarCampo('txtSeccion','t','') == true
		&& validarCampo('txtAbreviatura','t','') == true
		&& validarCampo('lstTipoSeccion','t','lista') == true
		) {
			xajax_guardarSeccion(xajax.getFormValues('frmSeccion'));
		} else {
			validarCampo('txtSeccion','t','');
			validarCampo('txtAbreviatura','t','');
			validarCampo('lstTipoSeccion','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar() {
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarSeccion(xajax.getFormValues('frmListaSeccion'));
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
        	<td class="tituloPaginaRepuestos">Secciones</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_formSeccion();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    	<button type="button" onclick="validarEliminar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
					</td>
                </tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaSeccion" name="frmListaSeccion" style="margin:0">
            	<?php
                $objSec = new lista();
                $objSec->iniciar(15, 0, "id_seccion", "DESC", $currentPage, "Sec");
                $query = "SELECT *,
					(CASE tipo_seccion
						WHEN 1 THEN 'Repuesto'
						WHEN 2 THEN 'Activo'
						WHEN 3 THEN 'Servicio'
						WHEN 4 THEN 'Otro'
					END) AS descripcion_tipo_seccion
				FROM iv_secciones";
                $rsSec = $objSec->consulta($database_conex, $conex, $query);
				
                echo $objSec->tabla(
					array(
						array("","","","center","checkbox","cbxSec"),
						array("Id","8%","id_seccion","right"),
						array("Descripción","64%","descripcion","left"),
						array("Abreviatura","12%","corta","left"),
						array("Tipo Seccion","16%","descripcion_tipo_seccion","center")),
					$rsSec[0],
					array(
						array("../img/iconos/pencil.png","javascript:xajax_cargarSeccion('|id_seccion|');","onclick")));
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
    
<form id="frmSeccion" name="frmSeccion" style="margin:0">
    <table border="0" id="tblSeccion" width="450px">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Sección:</td>
                <td width="75%"><input type="text" id="txtSeccion" name="txtSeccion" maxlength="50" size="50"/><input type="hidden" id="hddIdSeccion" name="hddIdSeccion" /></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Abreviatura:</td>
                <td><input type="text" id="txtAbreviatura" name="txtAbreviatura" maxlength="10" size="15"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Sección:</td>
                <td>
                	<select id="lstTipoSeccion" name="lstTipoSeccion">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="1">Repuesto</option>
                    </select>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" onclick="validarForm();">Guardar</button>
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