<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_cierre_mensual_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_an_cierre_mensual_list.php");
include("../controladores/ac_iv_general.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Pedidos de Venta</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblCierreMensual').style.display = 'none';

		if (verTabla == "tblCierreMensual") {
			xajax_formCierreMensual(xajax.getFormValues('frmCierreMensual'));
			
			tituloDiv1 = 'Cierre Mensual';
		} 
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblCierreMensual") {
			byId('txtIdEmpresa').focus();
			byId('txtIdEmpresa').select();
		} 
		
	}
	
	function validarFrmAprobarCierre(idCierreMensual) {
		var arrayCierre = new Array();
		
		if (confirm('¿Seguro desea aprobar el cierre?') == true) {
			// BLOQUEA LOS BOTONES DEL LISTADO
			for (cont = 1; cont <= 20; cont++) {
				if (!(byId('imgAprobarCierre' + cont) == undefined)) {
					byId('imgAprobarCierre' + cont).style.display = 'none';
				}
			}
			xajax_aprobarCierreMensual(idCierreMensual, xajax.getFormValues('frmListaCierreMensual'));
		}
	}
	function validarFrmCierreMensual() {
		if (validarCampo('lstMesAno','t','lista') == true) {
			if (confirm('¿Desea Crear el Cierre del Mes Seleccionado?') == true) {
				xajax_guardarCierreMensual(xajax.getFormValues('frmCierreMensual'), xajax.getFormValues('frmListaCierreMensual'));
			}
		} else {
			validarCampo('lstMesAno','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
      <div id="divInfo" class="print">
		<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Cierre Mensual</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblCierreMensual');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
			
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                    <td></td>
                    <td></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Mes:</td>
                    <td id="tdlstMes"></td>
                	<td align="right" class="tituloCampo" width="120">Año:</td>
                    <td id="tdlstAno"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
            </td>
        </tr>
        <tr>
        	<td>
			<form id="frmListaCierreMensual" name="frmListaCierreMensual" style="margin:0">
            	<div id="divListaCierreMensual" style="width:100%"></div>
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
                       		<td>&nbsp;</td>
                            <td><img src="../img/iconos/accept.png"/></td><td>Aprobar Cierre</td>
                            <td>&nbsp;</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        </table>
    </div>
	
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmCierreMensual" name="frmCierreMensual" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblCierreMensual" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo">Empresa:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                        <td><!--<button type="button" id="btnInsertarEmp" name="btnInsertarEmp" onclick="xajax_listadoEmpresas(0,'','','');" title="Listar"><img src="../img/iconos/help.png"/></button>--></td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Mes / Año:</td>
                <td id="tdlstMesAno" width="80%"></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table cellpadding="0" cellspacing="0" width="100%">
	            <tr align="left">
	            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdCierreMensual" name="hddIdCierreMensual"/>
            <button type="submit" id="btnGuardarCierreMensual" onclick="validarFrmCierreMensual();">Guardar</button>
            <button type="button" id="btnCancelarCierreMensual" name="btnCancelarCierreMensual" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>
</body>
</html>

<script>
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstMes();
xajax_cargaLstAno('<?php echo date("Y"); ?>');
xajax_listaCierreMensual(0, 'id_cierre_mensual', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||<?php echo date("Y"); ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>