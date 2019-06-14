<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_cierre_mensual_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_if_generar_cierre_mensual.php");
include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_cierre_mensual_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Cierre Mensual</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblCierreMensual').style.display = 'none';
		byId('tblAnalisisInv').style.display = 'none';
		byId('tblListaClasificacionInv').style.display = 'none';
		byId('tblListaMaxMin').style.display = 'none';
		
		if (verTabla == "tblCierreMensual") {
			xajax_formCierreMensual(xajax.getFormValues('frmCierreMensual'));
			
			tituloDiv1 = 'Cierre Mensual';
		} else if (verTabla == "tblAnalisisInv") {
			document.forms['frmAnalisisInv'].reset();
			byId('hddIdCierreMensualAnalisisInv').value = '';
			
			xajax_formAnalisisInv(valor);
			
			tituloDiv1 = 'Ver Análisis de Inventario';
		} else if (verTabla == "tblListaClasificacionInv") {
			byId('txtCriterio').className = 'inputHabilitado';
			
			xajax_formClasificacionInv(valor);
			
			tituloDiv1 = 'Clasificación de Inventario';
		} else if (verTabla == "tblListaMaxMin") {
			xajax_formMaxMin(valor);
			
			tituloDiv1 = 'Máximos y Mínimos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblCierreMensual") {
			byId('txtIdEmpresa').focus();
			byId('txtIdEmpresa').select();
		} else if (verTabla == "tblListaClasificacionInv") {
			byId('txtCriterio').focus();
			byId('txtCriterio').select();
		}
	}
	
	function validarFrmAprobarCierre(idCierreMensual) {
		var arrayCierre = new Array();
		
		error = false;
		if (byId('hddClasificacionInv' + idCierreMensual) == undefined) {
			arrayCierre.push('Clasificación de Inv.');
			error = true;
		}
		
		if (byId('hddMaximoMinimo' + idCierreMensual) == undefined) {
			arrayCierre.push('Cálculo de Max. y Min.');
			error = true;
		}
		
		if (byId('hddAnalisisInv' + idCierreMensual) == undefined) {
			arrayCierre.push('Análisis de Inv.');
			error = true;
		}
		
		if (error == true) {
			alert("Falta generar " + arrayCierre.join(", "));
			return false;
		} else {
			if (confirm('¿Seguro desea aprobar el cierre?') == true) {
				// BLOQUEA LOS BOTONES DEL LISTADO
				for (cont = 1; cont <= 20; cont++) {
					if (!(byId('imgClasificacionInv' + cont) == undefined)) {
						byId('imgClasificacionInv' + cont).style.display = 'none';
					}
					if (!(byId('imgMaximoMinimo' + cont) == undefined)) {
						byId('imgMaximoMinimo' + cont).style.display = 'none';
					}
					if (!(byId('imgAnalisisInv' + cont) == undefined)) {
						byId('imgAnalisisInv' + cont).style.display = 'none';
					}
					if (!(byId('imgAprobarCierre' + cont) == undefined)) {
						byId('imgAprobarCierre' + cont).style.display = 'none';
					}
				}
				xajax_aprobarCierreMensual(idCierreMensual, xajax.getFormValues('frmListaCierreMensual'));
			}
		}
	}
	
	function validarFrmAnalisisInventario(idCierreMensual) {
		if (confirm('¿Seguro desea generar el Análisis de Inventario?') == true) {
			// BLOQUEA LOS BOTONES DEL LISTADO
			for (cont = 1; cont <= 20; cont++) {
				if (!(byId('imgClasificacionInv' + cont) == undefined)) {
					byId('imgClasificacionInv' + cont).style.display = 'none';
				}
				if (!(byId('imgMaximoMinimo' + cont) == undefined)) {
					byId('imgMaximoMinimo' + cont).style.display = 'none';
				}
				if (!(byId('imgAnalisisInv' + cont) == undefined)) {
					byId('imgAnalisisInv' + cont).style.display = 'none';
				}
				if (!(byId('imgCierreGral' + cont) == undefined)) {
					byId('imgCierreGral' + cont).style.display = 'none';
				}
				if (!(byId('imgAprobarCierre' + cont) == undefined)) {
					byId('imgAprobarCierre' + cont).style.display = 'none';
				}
			}
			xajax_generarAnalisisInv(idCierreMensual, xajax.getFormValues('frmListaCierreMensual'));
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
	
	function validarFrmClasificacionInventario(idCierreMensual) {
		if (confirm('¿Seguro desea generar la Clasificación de Inventario?') == true) {
			// BLOQUEA LOS BOTONES DEL LISTADO
			for (cont = 1; cont <= 20; cont++) {
				if (!(byId('imgClasificacionInv' + cont) == undefined)) {
					byId('imgClasificacionInv' + cont).style.display = 'none';
				}
				if (!(byId('imgMaximoMinimo' + cont) == undefined)) {
					byId('imgMaximoMinimo' + cont).style.display = 'none';
				}
				if (!(byId('imgAnalisisInv' + cont) == undefined)) {
					byId('imgAnalisisInv' + cont).style.display = 'none';
				}
				if (!(byId('imgCierreGral' + cont) == undefined)) {
					byId('imgCierreGral' + cont).style.display = 'none';
				}
				if (!(byId('imgAprobarCierre' + cont) == undefined)) {
					byId('imgAprobarCierre' + cont).style.display = 'none';
				}
			}
			xajax_generarClasificacionInv(idCierreMensual, xajax.getFormValues('frmListaCierreMensual'));
		}
	}
	
	function validarFrmMaximoMinimo(idCierreMensual) {
		if (confirm('¿Seguro desea generar la Clasificación de Máximo y Mínimo?') == true) {
			// BLOQUEA LOS BOTONES DEL LISTADO
			for (cont = 1; cont <= 20; cont++) {
				if (!(byId('imgClasificacionInv' + cont) == undefined)) {
					byId('imgClasificacionInv' + cont).style.display = 'none';
				}
				if (!(byId('imgMaximoMinimo' + cont) == undefined)) {
					byId('imgMaximoMinimo' + cont).style.display = 'none';
				}
				if (!(byId('imgAnalisisInv' + cont) == undefined)) {
					byId('imgAnalisisInv' + cont).style.display = 'none';
				}
				if (!(byId('imgCierreGral' + cont) == undefined)) {
					byId('imgCierreGral' + cont).style.display = 'none';
				}
				if (!(byId('imgAprobarCierre' + cont) == undefined)) {
					byId('imgAprobarCierre' + cont).style.display = 'none';
				}
			}
			xajax_generarMaximoMinimo(idCierreMensual, xajax.getFormValues('frmListaCierreMensual'));
		}
	}
	
	function validarFrmCierreGral(idCierreMensual) {
		if (confirm('¿Seguro desea generar todos los procesos?') == true) {
			// BLOQUEA LOS BOTONES DEL LISTADO
			for (cont = 1; cont <= 20; cont++) {
				if (!(byId('imgClasificacionInv' + cont) == undefined)) {
					byId('imgClasificacionInv' + cont).style.display = 'none';
				}
				if (!(byId('imgMaximoMinimo' + cont) == undefined)) {
					byId('imgMaximoMinimo' + cont).style.display = 'none';
				}
				if (!(byId('imgAnalisisInv' + cont) == undefined)) {
					byId('imgAnalisisInv' + cont).style.display = 'none';
				}
				if (!(byId('imgCierreGral' + cont) == undefined)) {
					byId('imgCierreGral' + cont).style.display = 'none';
				}
				if (!(byId('imgAprobarCierre' + cont) == undefined)) {
					byId('imgAprobarCierre' + cont).style.display = 'none';
				}
			}
			xajax_generarCierreGral(idCierreMensual, xajax.getFormValues('frmListaCierreMensual'));
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
        	<td class="tituloPaginaRepuestos">Cierre Mensual</td>
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
                            <td><img src="../img/iconos/chart_organisation.png"/></td><td>Clasificación de Inv.</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/chart_bar.png"/></td><td>Cálculo de Max. y Min.</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/chart_pie.png"/></td><td>Análisis de Inv.</td>
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
            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr align="left">
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td>La creación del cierre puede tardar unos minutos, debido a que se estará generando:
                	<ul>
                        <li>ESTADISTICO DE VENTAS</li>
                        <li>INDICADORES DE TALLER</li>
                        <li>FACTURACIÓN ASESORES DE SERVICIOS</li>
                        <li>FACTURACIÓN VENDEDORES DE REPUESTOS</li>
                    </ul>
				</td>
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

<form id="frmAnalisisInv" name="frmAnalisisInv" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblAnalisisInv" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="120">Empresa:</td>
                <td><input type="text" id="txtEmpresaAnalisisInv" name="txtEmpresaAnalisisInv" readonly="readonly" size="50"/></td>
                <td align="right" class="tituloCampo" width="120">Mes-Año:</td>
                <td><input type="text" id="txtMesAno" name="txtMesAno" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Ver:</td>
                <td colspan="2">
                	<label><input type="checkbox" name="cbxVerUbicDisponible" checked="checked" value="3"/> Con Disponibilidad</label>
                    <label><input type="checkbox" name="cbxVerUbicSinDisponible" checked="checked" value="4"/> Sin Disponibilidad</label>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Código:</td>
                <td id="tdCodigoArt"></td>
            	<td align="right" class="tituloCampo">Criterio:</td>
                <td><input type="text" id="txtPalabra" name="txtPalabra" onkeyup="byId('btnBuscar').click();"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdCierreMensualAnalisisInv" name="hddIdCierreMensualAnalisisInv" readonly="readonly"/>
            <button type="submit" onclick="xajax_imprimirAnalisisInventario(xajax.getFormValues('frmAnalisisInv'));">Aceptar</button>
            <button type="button" id="btnCancelarAnalisisInv" name="btnCancelarAnalisisInv" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>

	<table border="0" id="tblListaClasificacionInv" width="960">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="14%">Empresa:</td>
                <td width="86%">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresaClasificacionInv" name="txtIdEmpresaClasificacionInv" readonly="readonly" size="6" style="text-align:right;"/></td>
                        <td></td>
                        <td><input type="text" id="txtEmpresaClasificacionInv" name="txtEmpresaClasificacionInv" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Mes-Año:</td>
                <td><input type="text" id="txtMesAnoClasificacionInv" name="txtMesAnoClasificacionInv" readonly="readonly" size="10" style="text-align:center"/></td>
			</tr>
            </table>
		</td>
	</tr>
    <tr>
    	<td>
        <form id="frmBuscarClasificacionInv" name="frmBuscarClasificacionInv" onsubmit="return false;" style="margin:0">
			<input type="hidden" id="hddIdCierreMensualClasificacionInv" name="hddIdCierreMensualClasificacionInv" readonly="readonly"/>
            <table align="right" border="0">
            <tr align="left">
            	<td align="right" class="tituloCampo">Clasif. Anterior:</td>
            	<td id="tdlstVerClasificacionAnt"></td>
            	<td align="right" class="tituloCampo">Clasif. Actual:</td>
            	<td id="tdlstVerClasificacionAct"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Código:</td>
                <td id="tdCodigoArtClasif"></td>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscarClasificacionInv').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarClasificacionInv" onclick="xajax_buscarClasificacionInv(xajax.getFormValues('frmBuscarClasificacionInv'));">Buscar</button>
                	<button type="button" onclick="document.forms['frmBuscarClasificacionInv'].reset(); byId('btnBuscarClasificacionInv').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td>
        	<div id="divListaClasificacionInv" style="width:100%"></div>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaClasificacionInv" name="btnCancelarListaClasificacionInv" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaMaxMin" width="960">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="14%">Empresa:</td>
                <td width="86%"><input type="text" id="txtEmpresaMaxMin" name="txtEmpresaMaxMin" readonly="readonly" size="50"/></td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Mes-Año:</td>
                <td><input type="text" id="txtMesAnoMaxMin" name="txtMesAnoMaxMin" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td>
        	<div id="divListaMaxMin" style="width:100%"></div>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaMaxMin" name="btnCancelarListaMaxMin" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

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