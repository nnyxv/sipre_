<?php
require_once ("../connections/conex.php");

session_start();

include ("../inc_sesion.php");

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_monedas.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento de Monedas</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function validarMoneda(){
		if (validarCampo('txtDescripcion','t','') == true){
				xajax_guardarMoneda(xajax.getFormValues('frmMoneda'));
		} else {
			validarCampo('txtDescripcion','t','')
						
			alert("El campo señalado en rojo es requerido");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include ('banner_tesoreria.php'); ?>
    </div>

    <div id="divInfo" class="print">
		<table width="80%" align="center">
            <tr>
                <td class="tituloPaginaTesoreria" colspan="2">Mantenimiento de Monedas</td>
            </tr>
            <tr>
                <td align="left">
                    <button type="button" onclick="xajax_levantarDivFlotante();" >Nuevo</button>
                </td>
            </tr>
            <tr>
            	<td id="tdListaMonedas">
					<script>
                        xajax_listarMonedas(0,'','','');
                    </script>
                </td>
            </tr>
		</table>
    </div>
    <div class="noprint">
	<?php include("pie_pagina.php") ?>
    </div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    <form id="frmMoneda" name="frmMoneda">
    <table border="0" id="tblMoneda" width="300px">
    <tr>
    	<td>
            <table border="0">
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Descripción:</td>
                    <td>
                    	<input type="text" id="txtDescripcion" name="txtDescripcion" size="30" />
                        <input type="hidden" id="hddIdMoneda" name="hddIdMoneda" />
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Abreviación :</td>
                    <td><input type="text" id="txtAbreviacion" name="txtAbreviacion" size="30" /></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Estado:</td>
                    <td id="tdSelEstatusMoneda">
                    	<select id="selEstatusMoneda" name="selEstatusMoneda">
                        	<option value="1">Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr/>
        	<input type="button" id="bttGuardar" name="bttGuardar" value="Guardar" onclick="validarMoneda();"/>
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar"/>
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