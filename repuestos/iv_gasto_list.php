<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include("../inc_sesion.php");
if (!(validaAcceso("iv_gasto_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_gasto_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Gastos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../clases/styleRafkLista.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblGasto').style.display = 'none';
		
		if (verTabla == "tblGasto") {
			document.forms['frmGasto'].reset();
			byId('hddIdGasto').value = '';
			
			byId('txtGasto').className = 'inputHabilitado';
			byId('lstModoGasto').className = 'inputHabilitado';
			byId('lstAfectaDocumento').className = 'inputHabilitado';
			byId('lstAsociaDocumento').className = 'inputHabilitado';
			
			xajax_formGasto(valor, xajax.getFormValues('frmGasto'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Gasto';
			} else {
				tituloDiv1 = 'Agregar Gasto';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblGasto") {
			byId('txtGasto').focus();
			byId('txtGasto').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaImpuesto').style.display = 'none';
				
		if (verTabla == "tblListaImpuesto") {
			document.forms['frmBuscarImpuesto'].reset();
			
			byId('btnBuscarImpuesto').click();
			
			tituloDiv2 = 'Impuestos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaImpuesto") {
			byId('txtCriterioBuscarImpuesto').focus();
			byId('txtCriterioBuscarImpuesto').select();
		}
	}
	
	function validarFrmGasto() {
		error = false;
		if (!(validarCampo('txtGasto','t','') == true
		&& validarCampo('lstModoGasto','t','lista') == true)) {
			validarCampo('txtGasto','t','');
			validarCampo('lstModoGasto','t','lista');
			
			error = true;
		}
		
		if (byId('lstModoGasto').value == 1) { // 1 = Gastos
			if (!(validarCampo('lstAfectaDocumento','t','listaExceptCero') == true)) {
				validarCampo('lstAfectaDocumento','t','listaExceptCero');
				
				error = true;
			}
		} else if (byId('lstModoGasto').value == 2) { // 2 = Otros Cargos
			if (!(validarCampo('lstAsociaDocumento','t','listaExceptCero') == true)) {
				validarCampo('lstAsociaDocumento','t','listaExceptCero');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarGasto(xajax.getFormValues('frmGasto'), xajax.getFormValues('frmListaGasto'));
		}
	}
	
	function validarEliminar(idGasto){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarGasto(idGasto, xajax.getFormValues('frmListaGasto'));
		}
	}
	
	function validarEliminarBloque(){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarGastoLote(xajax.getFormValues('frmListaGasto'));
		}
	}
	
	function validarInsertarImpuesto(idImpuesto) {
		xajax_insertarImpuesto(idImpuesto, xajax.getFormValues('frmGasto'));
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Gastos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblGasto');">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        </a>
                    	<button type="button" onclick="validarEliminarBloque();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
                    </td>
                </tr>
                </table>
			
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Modo:</td>
                    <td id="tdlstEmpresa">
                        <select id="lstModoGastoBuscar" name="lstModoGastoBuscar" class="inputHabilitado" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">Gastos</option>
                            <option value="2">Otros Cargos</option>
                            <option value="3">Gastos por Importación</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
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
            <form id="frmListaGasto" name="frmListaGasto" style="margin:0">
            	<div id="divListaGasto" style="width:100%"></div>
			</form>
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
    
<form id="frmGasto" name="frmGasto" style="margin:0">
    <table border="0" id="tblGasto" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span>Modo:</td>
            	<td width="32%">
                	<select id="lstModoGasto" name="lstModoGasto" onchange="xajax_asignarModo(this.value);">
                    	<option value="-1">[ Seleccione ]</option>
                    	<option value="1">Gastos</option>
                    	<option value="2">Otros Cargos</option>
                    	<option value="3">Gastos por Importación</option>
                    </select>
                </td>
                <td rowspan="4" width="50%">
                <fieldset id="fieldsetlstImpuesto"><legend class="legend">Impuestos</legend>
                    <table width="100%">
                    <tr align="left">
                        <td>
                            <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <a class="modalImg" id="aNuevoImpuesto" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaImpuesto');">
                                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                    </a>
                                    <button type="button" id="btnEliminarImpuesto" name="btnEliminarImpuesto" onclick="xajax_eliminarGastoImpuesto(xajax.getFormValues('frmGasto'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                                </td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table border="0" class="texto_9px" width="100%">
                            <tr align="center" class="tituloColumna">
                                <td><input type="checkbox" id="cbxItmImpuesto" onclick="selecAllChecks(this.checked,this.id,'frmGasto');"/></td>
                                <td width="25%%">Tipo Impuesto</td>
                                <td width="55%">Observación</td>
                                <td width="20%">% Impuesto</td>
                            </tr>
                            <tr id="trItmPieImpuesto"></tr>
                            </table>
                        </td>
                    </tr>
                    </table>
                </fieldset>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Gasto:</td>
                <td><input type="text" id="txtGasto" name="txtGasto" size="35"/></td>
            </tr>
            <tr id="trlstAfectaDocumento" align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Afecta Documento:</td>
            	<td>
                <div style="float:left">
                    <select id="lstAfectaDocumento" name="lstAfectaDocumento">
                    	<option value="-1">[ Seleccione ]</option>
                    	<option value="0">No</option>
                    	<option value="1">Si</option>
                    </select>
                </div>
                <div style="float:left">
                    <img src="../img/iconos/information.png" title="Indica si afecta la cuenta por pagar"/>
                </div>
                </td>
            </tr>
            <tr id="trlstAsociaDocumento" align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Asocia Documento:</td>
            	<td>
                <div style="float:left">
                    <select id="lstAsociaDocumento" name="lstAsociaDocumento">
                    	<option value="-1">[ Seleccione ]</option>
                    	<option value="0">No</option>
                    	<option value="1">Si</option>
                    </select>
                </div>
                <div style="float:left">
                    <img src="../img/iconos/information.png" title="Indica la factura de compra que se aplica como un cargo al registro de compra"/>
                </div>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdGasto" name="hddIdGasto" readonly="readonly"/>
            <button type="button" id="btnGuardarGasto" name="btnGuardarGasto" onclick="validarFrmGasto();">Guardar</button>
            <button type="button" id="btnCancelarGasto" name="btnCancelarGasto" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
	<table border="0" id="tblListaImpuesto" width="760">
    <tr>
    	<td>
        <form id="frmBuscarImpuesto" name="frmBuscarImpuesto" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarImpuesto" name="txtCriterioBuscarImpuesto" class="inputHabilitado" onkeyup="byId('btnBuscarImpuesto').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarImpuesto" name="btnBuscarImpuesto" onclick="xajax_buscarImpuesto(xajax.getFormValues('frmBuscarImpuesto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarImpuesto'].reset(); byId('btnBuscarImpuesto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaImpuesto" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaImpuesto" name="btnCancelarListaImpuesto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('txtCriterio').className = 'inputHabilitado';

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

xajax_listaGasto(0,'id_gasto','ASC');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>