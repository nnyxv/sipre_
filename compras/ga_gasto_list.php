<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if (!(validaAcceso("ga_gasto_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_gasto_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Gastos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
   <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		
		switch (verTabla){
			case "tdImpuesto": //tdListIpmuesto 
						byId('tdFlotanteTitulo2').innerHTML = 'Listado de Impeusto';
						document.forms['frmImpuesto'].reset();
					break;
			default:
				xajax_eliminarImpuesto(xajax.getFormValues('frmGasto'),1);
				
				if (verTabla == "tblGasto") {
					if (valor > 0) {
						xajax_cargarGasto(valor);
						byId('tdFlotanteTitulo1').innerHTML = 'Editar Gasto';
					} else {
						xajax_formGasto();
						byId('tdFlotanteTitulo1').innerHTML = 'Agregar Gasto';
					}
				}
				
				byId('txtGasto').focus();
				byId('txtGasto').select();
					break;
		}
		
		openImg(nomObjeto);
	}
	
	function agregarImpuesto(estatu){
		switch (estatu){
			case 1: 
				document.getElementById("btnAgregarImpuesto").disabled = false;
				document.getElementById("btnQuitarImpuesto").disabled = false;
					break;
			case 0: 
				document.getElementById("btnAgregarImpuesto").disabled = true;
				document.getElementById("btnQuitarImpuesto").disabled = true;
					break;
			}
	}
	
	function RecorrerForm(nameFrm,accion){ 
		var frm = document.getElementById(nameFrm);
		var sAux= "";
		for (i=0;i<frm.elements.length;i++)	{//recorre los elementos del form
			if(frm.elements[i].type == 'button' || frm.elements[i].type == 'submit'){//si son tipo text
				sAux = frm.elements[i].id;
				if (accion == 0 ) {
					document.getElementById(sAux).disabled = true; //desahabilita
				} else {
					document.getElementById(sAux).disabled = false; //habilita
				}
			}
		}
	}
	
	function validarFrmGasto() {
		if (validarCampo('txtGasto','t','') == true
		&& validarCampo('lstModoGasto','t','lista') == true
		&& validarCampo('lstAfectaDocumento','t','listaExceptCero') == true
		&& validarCampo('lstAsociaDocumento','t','listaExceptCero') == true) {
			xajax_guardarGasto(xajax.getFormValues('frmGasto'), xajax.getFormValues('frmListaGasto'));
		} else {
			validarCampo('txtGasto','t','');
			validarCampo('lstModoGasto','t','lista');
			validarCampo('lstAfectaDocumento','t','listaExceptCero');
			validarCampo('lstAsociaDocumento','t','listaExceptCero')
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idGasto){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarGasto(idGasto, xajax.getFormValues('frmListaGasto'));
		}
	}
	
	function validarEliminarBloque(){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarGastoBloque(xajax.getFormValues('frmListaGasto'));
		}
	}
	function ocultaMuestra(siNo){
		switch(siNo){
			case "si":
				$('#trretencion').show();
					xajax_cargarRetenciones();
					break;
			case "no":
				$('#trretencion').hide();
				break;	
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_compras.php"); ?></div>
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCompras">Gastos</td>
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
			
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                  <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Modo:</td>
                    <td>
                        <select id="lstModoGastoBuscar" name="lstModoGastoBuscar" class="inputHabilitado" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Todos ]</option>
                            <option value="1">Gastos</option>
                            <option value="2">Otros Cargos</option>
                            <option value="3">Gastos por Importación</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" onkeyup="byId('btnBuscar').click();"/></td>
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
            <form id="frmListaGasto" name="frmListaGasto" style="margin:0"  onsubmit="return false;">
            	<div id="divListaGasto" style="width:100%"></div>
			</form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                	<tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center"> 
                            <table border="0" >
                                <tr>
                                    <td>Activo</td>
                                    <td><img title="Activo" src="../img/iconos/ico_verde.gif"></td>
                                    <td>Afecta Cuentas por pagar</td>
                                    <td><img class="puntero" title="Editar" src="../img/iconos/stop.png"></td>
                                    <td>Editar</td>
                                    <td><img class="puntero" title="Editar" src="../img/iconos/pencil.png"></td>
                                    <td>Eliminar</td>
                                    <td><img class="puntero" title="Editar" src="../img/iconos/cross.png"></td>
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
	<div id="divFlotanteTitulo1" class="handle">
        <table>
            <tr>
                <td id="tdFlotanteTitulo1" align="left" width="100%"></td>
            </tr>
        </table>
    </div>
<form id="frmGasto" name="frmGasto" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblGasto" width="860">
    <tr>
    	<td>
        	<table width="100%" border="0">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Gasto:</td>
                <td width="20%">
                    <input type="text" id="txtGasto" name="txtGasto" size="35"/>
                    <input type="hidden" id="hddIdGasto" name="hddIdGasto" readonly="readonly"/>
				</td>
                <td  width="40%" rowspan="7" id="tdImpuesto" valign="top">
                    <fieldset>
                        <legend class="legend">% Impuesto</legend>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td  align="left" colspan="2"> <!--BOTON AGREGAR IMPUESTO --> 
                                    <a class="modalImg" id="AgregarIpuesto" rel="#divFlotante2" onclick="abrirDivFlotante1(this, 'tdImpuesto');">
                                        <button id="btnAgregarImpuesto" name="btnAgregarImpuesto" type="button" disabled="disabled" title="Agregar Impuesto">
                                            <table cellspacing="0" cellpadding="0" align="center">
                                                <tr>
                                                    <td>&nbsp;</td>
                                                    <td><img src="../img/iconos/add.png"></td>
                                                    <td>&nbsp;</td>
                                                    <td>Agregar</td>
                                                </tr>
                                            </table>
                                        </button>
                                    </a>
                                    	<button name="btnQuitarImpuesto" id="btnQuitarImpuesto" onclick="xajax_eliminarImpuesto(xajax.getFormValues('frmGasto'));" type="button" disabled="disabled" title="Quitar Impuesto">
                                        <table cellspacing="0" cellpadding="0" align="center">
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td><img src="../img/iconos/delete.png"></td>
                                                <td>&nbsp;</td>
                                                <td>Quitar</td>
                                            </tr>
                                        </table>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><!--TABALA DONDE SE AGRAGAN LOS IMPUESTOS-->
                                	<div style="max-height:130px; overflow:auto; width:100%;">
                                        <table border="0" width="100%">                                       	
                                            <tr class="tituloColumna" align="center">
                                                <td width="10%" align="center">
                                                    <input type="checkbox" id="cbxItmImpuesto" onclick="selecAllChecks(this.checked,this.id,2);"/>
                                                </td>
                                                <td width="13%">Id</td>
                                                <td width="25%">Tipo Impuesto</td>
                                                <td width="25%">Observacion</td>
                                                <td width="13%">Impuesto</td>
                                            </tr>
                                            <tr id="trItemsImpuesto"></tr>
                                        </table>
                                    </div> 
                                </td>                                    
                            </tr>
                        </table>
                    </fieldset>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus I.V.A.:</td>
                <td>
                	<input id="rbtEstatusIvaSi" name="rbtEstutusIva" type="radio" value="1" onclick="agregarImpuesto(1)"/> Si
                    <input id="rbtEstutusIvaNo" name="rbtEstutusIva" type="radio" value="0" onclick="agregarImpuesto(0)"checked="checked"/> No
				</td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Modo:</td>
            	<td>
                	<select id="lstModoGasto" name="lstModoGasto" class="inputHabilitado">
                    	<option value="-1">[ Seleccione ]</option>
                    	<option value="1">Gastos</option>
                    	<option value="2">Otros Cargos</option>
                    	<option value="3">Gastos por Importación</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Afecta Documento:</td>
            	<td>
                	<select id="lstAfectaDocumento" name="lstAfectaDocumento" class="inputHabilitado">
                    	<option value="-1">[ Seleccione ]</option>
                    	<option value="0">No</option>
                    	<option value="1">Si</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Asocia Documento:</td>
            	<td>
                	<select id="lstAsociaDocumento" name="lstAsociaDocumento" class="inputHabilitado">
                    	<option value="-1">[ Seleccione ]</option>
                    	<option value="0">No</option>
                    	<option value="1">Si</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus I.S.L.R.:</td>
            	<td>
                	<input id="retencionISLRsi" name="retencionISLR" type="radio" value="1" onclick="ocultaMuestra('si')"/> Si
                    <input id="retencionISLRno" name="retencionISLR" type="radio" value="0" checked="checked" onclick="ocultaMuestra('no')"/> No
                </td>
            </tr>
            <tr id="trretencion" align="left" style="display:none">
            	<td align="right" class="tituloCampo">Retención I.S.L.R.:</td>
            	<td id="tdlistRetenciones"></td>
            </tr>
            
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
           <!-- <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmGasto();">Guardar</button>
            <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>-->
        <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmGasto();">
            Guardar
        </button>
        <button type="button" id="btnCancelar" name="btnCancelar" class="close" onclick="ocultaMuestra('no');">
           Cerrar
        </button>

        </td>
    </tr>
    </table>
</form>
</div>

<!--LISTADO DE IMPUESTO-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle">
    	
        <table>
            <tr>
                <td id="tdFlotanteTitulo2" align="left" width="100%"></td>
            </tr>
        </table>
    </div>
	<form id="frmImpuesto" name="frmImpuesto" style="margin:0" onsubmit="return false;">   
         <table>
            <tr>
                <td id="tdListIpmuesto"></td>
            </tr>
            <tr>
                <td align="right"><hr />
                    <button type="button" id="btnCerraIpuestoGasto" name="btnCerraIpuestoGasto" class="close">Cerrar</button>
                </td>
            </tr>
        </table>
    </form>
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

xajax_listadoGastos(0,'id_gasto','ASC');
xajax_listImpuesto(0,"iva","ASC","");

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

</script>