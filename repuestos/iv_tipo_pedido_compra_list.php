<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_tipo_pedido_compra_list"))) {
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
include("controladores/ac_iv_tipo_pedido_compra_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Tipo de Pedido de Compra</title>
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
		if (validarCampo('txtTipoPedidoCompra','t','') == true
		&& validarCampo('txtClave','t','') == true
		&& validarCampo('txtNumeracion','t','') == true
		) {
			xajax_guardarTipoPedidoCompra(xajax.getFormValues('frmTipoPedidoCompra'));
		} else {
			validarCampo('txtTipoPedidoCompra','t','');
			validarCampo('txtClave','t','');
			validarCampo('txtNumeracion','t','');
			
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
        	<td class="tituloPaginaRepuestos">Tipo de Pedido de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_formTipoPedidoCompra();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    	<button type="button" onclick="if (confirm('¿Desea eliminar los registros seleccionado(s)?') == true) xajax_eliminarTipoPedidoCompra(xajax.getFormValues('frmListaTipoPedidoCompra'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
					</td>
                </tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaTipoPedidoCompra" name="frmListaTipoPedidoCompra" style="margin:0">
            	<?php
                $obj = new lista();
                $obj->iniciar(15, 0, "id_tipo_pedido_compra", "DESC", $currentPage, "Sec");
                $query = "SELECT * FROM iv_tipo_pedido_compra";
                $rs = $obj->consulta($database_conex, $conex, $query);
				
                echo $obj->tabla(
					array(
						array("","","id_tipo_pedido_compra","center","checkbox","cbxTipoPedidoCompra"),
						array("Nombre","70%","tipo_pedido_compra","left"),
						array("Clave","15%","clave","right"),
						array("Numeración","15%","numeracion","center")),
					$rs[0],
					array(
						array("../img/iconos/pencil.png","javascript:xajax_cargarTipoPedidoCompra('|id_tipo_pedido_compra|');","onclick")));
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
    
<form id="frmTipoPedidoCompra" name="frmTipoPedidoCompra" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblTipoPedidoCompra" width="450px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Tipo Pedido:</td>
                <td width="75%"><input type="text" id="txtTipoPedidoCompra" name="txtTipoPedidoCompra" size="50"/><input type="hidden" id="hddIdTipoPedidoCompra" name="hddIdTipoPedidoCompra" /></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave:</td>
                <td><input type="text" id="txtClave" name="txtClave" size="20"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Numeración:</td>
                <td>
                	<input type="text" id="txtNumeracion" name="txtNumeracion" size="20"/>
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