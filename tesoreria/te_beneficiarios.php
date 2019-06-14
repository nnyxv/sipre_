<?php
require_once ("../connections/conex.php");
/* Validación del Módulo */
//include('../inc_sesion.php');
//validaModulo("an_modificar_vehiculo");
/* Fin Validación del Módulo */

session_start();

include ("../inc_sesion.php");

//require_once('clases/rafkLista.php');
$currentPage = $_SERVER["PHP_SELF"];

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_beneficiarios.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Beneficiarios</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    
   <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	<script>
     
        /*function enviarForm(){
            window.location.href="te_nuevo_beneficiario.php";
        }*/
        
    </script>
    <script>
		function validarFormInsertar() {
			if (validarCampo('txtNombreBeneficiario','t','') == true
			&& validarCampo('txtCiRif','t','numPositivo') == true
			&& validarCampo('txtEstado','t','') == true
			&& validarCampo('txtCiudad','t','') == true
			&& validarCampo('textDireccion','t','') == true
			&& validarCampo('txtTelefono','t','numPositivo') == true
			&& validarCampo('txtEmailBanco','t','email') == true)
			{
				xajax_insertarDatos(xajax.getFormValues('frmBeneficiario'));
			} else {
				
				validarCampo('txtNombreBeneficiario','t','');
				validarCampo('txtCiRif','t','numPositivo');
				validarCampo('txtEstado','t','');
				validarCampo('txtCiudad','t','');
				validarCampo('textDireccion','t','');
				validarCampo('txtTelefono','t','numPositivo');
				validarCampo('txtEmailBanco','t','email');
	
				alert("Los campos señalados en rojo son requeridos");
	
				return false;
	
			}
		}
		function validarFormActualizar() {
			if (validarCampo('txtNombreBeneficiario','t','') == true
			&& validarCampo('txtCiRif','t','numPositivo') == true
			&& validarCampo('txtEstado','t','') == true
			&& validarCampo('txtCiudad','t','') == true
			&& validarCampo('textDireccion','t','') == true
			&& validarCampo('txtTelefono','t','numPositivo') == true
			&& validarCampo('txtEmailBanco','t','email') == true)
			{
				xajax_actualizarDatos(xajax.getFormValues('frmBeneficiario'));
			} else {
				
				validarCampo('txtNombreBeneficiario','t','');
				validarCampo('txtCiRif','t','numPositivo');
				validarCampo('txtEstado','t','');
				validarCampo('txtCiudad','t','');
				validarCampo('textDireccion','t','');
				validarCampo('txtTelefono','t','numPositivo');
				validarCampo('txtEmailBanco','t','email');
	
				alert("Los campos señalados en rojo son requeridos");
	
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
    	<table border="0" width="80%" align="center">
        <tr>
        	<td class="tituloPaginaTesoreria">Beneficiarios<br></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
            <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarBenficiario(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                <table width="100%">
                <tr>
               	  <td align="left"><button name="btnNuevo" type="button" id="btnNuevo" onclick="xajax_nuevoBeneficiario(xajax.getFormValues('frmBeneficiario'));">Nuevo</button></td>
                    <td align="left" width="47%"><input type="hidden" onclick="enviarForm();" value="Nueva Factura" /></td>
                    <td align="right" class="tituloCampo" width="14%">Nombre Beneficiario:</td>
                    <td align="left" width="13%"><input type="text" name="txtBusq" id="txtBusq" onkeyup="$('btnBuscar').click();"/></td>
                    <td align="left" width="16%"><button type="button" name="btnBuscar" id="btnBuscar" onclick="xajax_buscarBenficiario(xajax.getFormValues('frmBuscar'));" >Buscar</button><button type="button" onclick="document.forms['frmBuscar'].reset(); xajax_listadoBeneficiario();" >Ver Todo</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td id="tdListadoBeneficiario"></td>
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
    <form id="frmBeneficiario" name="frmBeneficiario">
    <table border="0" id="tblBanco" width="610">
    <tr>
    	<td>
            <table border="0" id="tblVerAlmacen">
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nombre:</td>
                    <td><input type="text" id="txtNombreBeneficiario" name="txtNombreBeneficiario" size="25" /><input type="hidden" id="hddIdBeneficiario" name="hddIdBeneficiario"/></td>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>CI/RIF:</td>
                    <td><select name="listLetraCiRif" id="listLetraCiRif">
                        <option value="V">V</option>
                        <option value="J">J</option>
                        <option value="E">E</option>
                        <option value="G">G</option>
                        </select>-<input type="text" id="txtCiRif" name="txtCiRif" size="15" onkeypress="return validarSoloNumeros(event);"/></td>
                </tr>
                <tr>
                	<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Ciudad:</td>
                     <td><input type="text" id="txtCiudad" name="txtCiudad" size="25" /></td>
                     <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Estado:</td>
                     <td><input type="text" id="txtEstado" name="txtEstado" size="25" /></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Direccion:</td>
                    <td colspan="3"><textarea name="textDireccion" cols="72" rows="2" id="textDireccion"></textarea></td>
                </tr>
                <tr>
                	 
                	 <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Telefono:</td>
                    <td><input type="text" id="txtTelefono" name="txtTelefono" size="25" onkeypress="return validarTelefono(event);"/></td>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Email:</td>
                    <td><input type="text" id="txtEmailBanco" name="txtEmailBanco" size="25" /></td>
                </tr>
                <tr>
                	<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Retencion:</td>
                    <td id="tdlstRetencion"><select id="lstRetencion" name="lstRetencion">
                   			 <option value="-1">Seleccione...</option>
                		</select>
		        <script>
                        xajax_cargaLstRetencion();
                        </script></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right" id="trBeneficiariosBotones">
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

<script>
xajax_listadoBeneficiario();
</script>