<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("te_resumen"))) {
	echo "<script>alert('Acceso Denegado'); window.location.href = 'index.php';</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_te_resumen.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento de Cuentas</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
            
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
	
    <script>	
         
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaBanco').style.display = 'none';
		
		if (verTabla == "tblListaBanco") {			
			document.forms['frmBuscarBanco'].reset();
			xajax_listaBanco();
			
			tituloDiv1 = 'Bancos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaBanco") {			
			byId('txtCriterioBuscarBanco').focus();
			byId('txtCriterioBuscarBanco').select();
		}
	}
	
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_tesoreria.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
            <td class="tituloPaginaTesoreria">Resumen de Cuenta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa" align="left"></td>

				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Banco:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly"/><input type="hidden" id="hddIdBanco" name="hddIdBanco"/></td>
                            <td><a onclick="abrirDivFlotante1(this, 'tblListaBanco');" rel="#divFlotante1" id="aListarBanco" class="modalImg"><button title="Listar" type="button"><img src="../img/iconos/help.png"></button></a>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Nro. Cuenta:</td>
                    <td colspan="2" id="tdLstCuenta">
                    </td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarCuenta(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('hddIdBanco').value = ''; byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>		
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListaCuenta"></td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    <table border="0" id="tblListaBanco" style="display:none" width="610">
    <tr>
        <td>
            <form onsubmit="return false;" style="margin:0" name="frmBuscarBanco" id="frmBuscarBanco">
                <table align="right">
                <tr align="left">
                    <td width="120" align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" onkeyup="byId('btnBuscarBanco').click();" class="inputHabilitado" name="txtCriterioBuscarBanco" id="txtCriterioBuscarBanco"></td>
                    <td>
                        <button onclick="xajax_buscarBanco(xajax.getFormValues('frmBuscarBanco'));" name="btnBuscarBanco" id="btnBuscarBanco" type="button">Buscar</button>
                        <button onclick="document.forms['frmBuscarBanco'].reset(); byId('btnBuscarBanco').click();" type="button">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListaBanco"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarBanco" name="btnCancelarBanco" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</div>

<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',"onchange=\"byId('btnBuscar').click();\"");
xajax_cargaLstCuenta();
xajax_listaCuenta(0,'','',<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>);

function openImg(idObj) {
	var oldMaskZ = null;
	var $oldMask = $(null);
	
	$(".modalImg").each(function() {
		$(idObj).overlay({
			//effect: 'apple',
			oneInstance: false,
			zIndex: 10100,
			
			onLoad: function() {
				if ($.mask.isLoaded()) {
					oldMaskZ = $.mask.getConf().zIndex; // this is a second overlay, get old settings
					$oldMask = $.mask.getExposed();
					$.mask.getConf().closeSpeed = 0;
					$.mask.close();
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0,
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false
					});
				} // Other onLoad functions
			},
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) { // re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0
					});
					
					$(".apple_overlay").css("zIndex", oldMaskZ + 2); // Assumes the other overlay has apple_overlay class
				}
			}
		}).load();
	});
}

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

</script>