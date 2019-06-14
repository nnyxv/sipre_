<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_articulo_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Registrar Artículo</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css" />
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
	
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblPermiso').style.display = 'none';
		byId('tblListaArancelFamilia').style.display = 'none';
		byId('tblListaArtSustAlt').style.display = 'none';
		byId('tblFlotanteContenido').style.display = 'none';
		byId('tblListaImpuesto').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputInicial';
			
			xajax_formValidarPermisoEdicion(valor);
			
			tituloDiv1 = 'Ingreso de Clave Especial';
		} else if (verTabla == "tblListaArancelFamilia") {
			document.forms['frmBuscarArancelFamilia'].reset();
			
			byId('btnBuscarArancelFamilia').click();
			
			tituloDiv1 = 'Familia Arancelaria';
		} else if (verTabla == "tblListaArtSustAlt") {
			byId('frmBuscarArticulo').reset();
			
			if (valor == 'formArticuloSustituto') {
				xajax_formArticuloSustAlt(1);
				tituloDiv1 = 'Agregar Artículo Sustituto';
			} else if (valor == 'formArticuloAlterno') {
				xajax_formArticuloSustAlt(2);
				tituloDiv1 = 'Agregar Artículo Alterno';
			}
		} else if (verTabla == "tblFlotanteContenido") {
			xajax_verArticulo(valor, 'tdFlotanteContenido');
			tituloDiv1 = 'Ver Artículo';
		} else if (verTabla == "tblListaImpuesto") {
			document.forms['frmBuscarImpuesto'].reset();
			
			byId('btnBuscarImpuesto').click();
			
			tituloDiv1 = 'Impuestos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		} else if (verTabla == "tblListaArancelFamilia") {
			byId('txtCriterioBuscarArancelFamilia').focus();
			byId('txtCriterioBuscarArancelFamilia').select();
		} else if (verTabla == "tblListaArtSustAlt") {
			byId('txtCodigoArticuloBusq0').focus();
			byId('txtCodigoArticuloBusq0').select();
		} else if (verTabla == "tblListaImpuesto") {
			byId('txtCriterioBuscarImpuesto').focus();
			byId('txtCriterioBuscarImpuesto').select();
		}
	}
	
	function validarFrmArticulo() {
		error = false;
		if (!(validarCampo('txtCodigoProveedor','t','') == true
		&& validarCampo('lstModoCompra','t','lista') == true
		&& validarCampo('lstTipoArticuloArt','t','lista') == true
		&& validarCampo('lstMarcaArt','t','lista') == true
		&& validarCampo('txtDescripcion','t','') == true
		&& validarCampo('lstTipoUnidad','t','lista') == true
		&& validarCampo('lstSeccionArt','t','lista') == true
		&& validarCampo('lstSubSeccionArt','t','lista') == true
		&& validarCampo('lstGeneraComision','t','listaExceptCero') == true
		&& validarCampo('lstPrecioPredet','t','lista') == true)) {
			validarCampo('txtCodigoProveedor','t','');
			validarCampo('lstModoCompra','t','lista');
			validarCampo('lstTipoArticuloArt','t','lista');
			validarCampo('lstMarcaArt','t','lista');
			validarCampo('txtDescripcion','t','');
			validarCampo('lstTipoUnidad','t','lista');
			validarCampo('lstSeccionArt','t','lista');
			validarCampo('lstSubSeccionArt','t','lista');
			validarCampo('lstGeneraComision','t','listaExceptCero');
			validarCampo('lstPrecioPredet','t','lista');
			
			error = true;
		}
		
		if (byId('lstModoCompra').value == 2) {
			if (!(validarCampo('txtCodigoArancelFamilia','t','') == true)) {
				validarCampo('txtCodigoArancelFamilia','t','');
				
				error = true;
			}
		}
		
		valido = false;
		for (i = 0; i <= byId('hddCantCodigo').value; i++) {
			byId('txtCodigoArticulo'+i).className = "inputInicial";
			if (byId('txtCodigoArticulo'+i).value.length > 0
			&& byId('txtCodigoArticulo'+i).value != null
			&& byId('txtCodigoArticulo'+i).value != 'null') {
				valido = true;
			}
		}
		
		if (error == true) {
			if (!(valido == true)) {
				for (i = 0; i <= byId('hddCantCodigo').value; i++) {
					if (!(byId('txtCodigoArticulo'+i).length > 0
					&& byId('txtCodigoArticulo'+i).value != null
					&& byId('txtCodigoArticulo'+i).value != 'null')) {
						validarCampo('txtCodigoArticulo'+i,'t','');
					}
				}
			}
			
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (valido == true) {
				xajax_guardarArticulo(xajax.getFormValues('frmArticulo'), xajax.getFormValues('frmListaArtSust'), xajax.getFormValues('frmListaArtAlt'));
			} else if (valido == false) {
				for (i = 0; i <= byId('hddCantCodigo').value; i++) {
					if (!(byId('txtCodigoArticulo'+i).length > 0
					&& byId('txtCodigoArticulo'+i).value != null
					&& byId('txtCodigoArticulo'+i).value != 'null')) {
						validarCampo('txtCodigoArticulo'+i,'t','');
					}
				}
				
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			}
		}
	}
	
	function validarFormPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarInsertarArticulo(idArticulo, tipoArticulo) {
		if (tipoArticulo == 1) {
			xajax_insertarArticuloSustituto(idArticulo, xajax.getFormValues('frmListaArtSust'));
		} else if (tipoArticulo == 2) {
			xajax_insertarArticuloAlterno(idArticulo, xajax.getFormValues('frmListaArtAlt'));
		}
	}
	
	function validarInsertarImpuesto(idImpuesto) {
		xajax_insertarImpuesto(idImpuesto, xajax.getFormValues('frmArticulo'));
	}
	
	function bloquearForm() {
		byId('btnGuardar').style.display = 'none';
		
		byId('txtCodigoProveedor').readOnly = true;
		byId('txtDescripcion').readOnly = true;
		byId('fleUrlImagen').style.display = 'none';
		byId('aListarArancelFamilia').style.display = 'none';
		
		byId('aInsertarArt').style.display = 'none';
		byId('btnEliminarArt').style.display = 'none';
		byId('aInsertarArtAlt').style.display = 'none';
		byId('btnEliminarArtAlt').style.display = 'none';
		
		byId('trAccionesPantalla').style.display = '';
	}
	
	Array.prototype.count = function() {
		return this.length;
	};
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Artículo</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr class="noprint" id="trAccionesPantalla" style="display:none">
        	<td>
            	<table align="left">
                <tr>
                	<td>
                    	<button type="button" onclick="window.print();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td align="left">
            <form action="controladores/ac_upload_file_articulo.php" enctype="multipart/form-data" id="frmArticulo" name="frmArticulo" method="post" style="margin:0" target="iframeUpload">
                <input type="hidden" id="hddIdArticulo" name="hddIdArticulo"/>
                <table border="0" width="100%">
                <tr>
                	<td width="68%"></td>
                    <td width="32%"></td>
                </tr>
                <tr>
                	<td colspan="2">
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="88%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td>&nbsp;</td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
							</td>
						</tr>
                        </table>
                    </td>
                </tr>
                <tr>
                	<td colspan="2" id="tdMsjSustituido" style="display:none">
                    	<table cellpadding="0" cellspacing="0" class="divMsjError" width="100%">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_fallido.gif" width="25"/></td>
                            <td align="center">Este Artículo Ha Sido Sustituido</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                	<td valign="top">
                    <fieldset><legend class="legend">Datos del Articulo</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Código:</td>
                            <td id="tdCodigoArt" width="40%">
                            </td>
                            <td align="right" class="tituloCampo" width="15%">
                                <span class="textoRojoNegrita">*</span>Cód. Artículo:
                                <br />
                                <span class="textoNegrita_10px">(Proveedor)</span>
                            </td>
                            <td width="30%"><input type="text" id="txtCodigoProveedor" name="txtCodigoProveedor" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="4"><span class="textoRojoNegrita">*</span>Descripción:</td>
                            <td rowspan="4">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td><textarea id="txtDescripcion" name="txtDescripcion" rows="4" style="width:99%"></textarea></td>
                                    <td>&nbsp;</td>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearDescripcion" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'iv_articulo_form_descripcion');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Modo de Compra:</td>
                            <td>
                                <select id="lstModoCompra" name="lstModoCompra" style="width:200px">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">Nacional</option>
                                    <option value="2">Importación</option>
                                </select>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Artículo:</td>
                            <td id="tdlstTipoArticuloArt"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Marca:</td>
                            <td id="tdlstMarcaArt"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Unidad:</td>
                            <td id="tdlstTipoUnidad"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Sección:</td>
                            <td id="tdlstSeccionArt"></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Sub-Sección:</td>
                            <td id="tdlstSubSeccionArt"></td>
                        </tr>
                        </table>
					</fieldset>
                    </td>
                    <td rowspan="2" valign="top">
                    	<table border="0" width="100%">
                        <tr>
                        	<td align="center" colspan="2"><img border="0" id="imgCodigoBarra" name="imgCodigoBarra" /></td>
						</tr>
                        <tr>
                        	<td align="center" class="imgBorde" colspan="2"><img id="imgArticulo" width="220"/></td>
						</tr>
                        <tr>
                        	<td colspan="2">
                            	<input type="file" id="fleUrlImagen" name="fleUrlImagen" class="inputHabilitado" onchange="javascript:submit();" />
                                <iframe name="iframeUpload" style="display:none"></iframe>
                                <input type="hidden" id="hddUrlImagen" name="hddUrlImagen" />
                            </td>
                        </tr>
                        <tr align="center">
                        	<td class="tituloCampo" width="50%">Creación</td>
                        	<td class="tituloCampo" width="50%">Clasificación</td>
                        </tr>
                        <tr align="center">
                        	<td><span id="spnFechaRegistro"></span></td>
                        	<td>
                            	<div id="divClasificacion"></div>
                                <input type="hidden" id="hddClasificacion" name="hddClasificacion"/>
							</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                	<td>
                    <fieldset><legend class="legend">Datos para Compra y Venta</legend>
                        <table border="0" width="100%">
                        <tr>
                        	<td width="18%"></td>
                        	<td width="32%"></td>
                        	<td width="18%"></td>
                        	<td width="32%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Posición Arancelaria:</td>
                            <td colspan="3">
                            	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td><input type="hidden" id="txtIdArancelFamilia" name="txtIdArancelFamilia" onkeyup="xajax_asignarArancelFamilia(this.value, 'false');" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarArancelFamilia" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaArancelFamilia');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtCodigoArancelFamilia" name="txtCodigoArancelFamilia" readonly="readonly" size="24"/></td>
                                    <td>&nbsp;</td>
                                    <td width="100%"><input type="text" id="txtDescripcionArancelFamilia" name="txtArancelFamilia" readonly="readonly" style="width:99%"/></td>
                                    <td>&nbsp;</td>
                                    <td><input type="text" id="txtPorcArancelFamilia" name="txtPorcArancelFamilia" readonly="readonly" size="12" style="text-align:right"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Precio Predet.:
                                <br />
                                <span class="textoNegrita_10px">(Para Ventas)</span>
                            </td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td id="tdlstPrecioPredet"></td>
                                    <td>&nbsp;</td>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearPrecio" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'iv_articulo_form_precio');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo">
                                <span class="textoRojoNegrita">*</span>Genera Comisión:
                                <br />
                                <span class="textoNegrita_10px">(Para Ventas)</span>
                            </td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <select id="lstGeneraComision" name="lstGeneraComision">
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="0">No</option>
                                            <option value="1">Si</option>
                                        </select>
                                    </td>
                                    <td>&nbsp;</td>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearComision" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'iv_articulo_form_genera_comision');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
						</tr>
                        <tr>
                            <td align="right" class="tituloCampo">Aplica Impuesto:</td>
                        	<td>
                                <select id="lstIvaArt" name="lstIvaArt">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="0">No</option>
                                    <option value="1">Si</option>
                                </select>
                            </td>
                        </tr>
                        </table>
					</fieldset>
                    </td>
                </tr>
                <tr>
                	<td colspan="2">
                    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td valign="top" width="60%">
                            <fieldset><legend class="legend">Impuestos</legend>
                                <table width="100%">
                                <tr align="left">
                                    <td>
                                        <table border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>
                                                <a class="modalImg" id="aNuevoImpuesto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaImpuesto');">
                                                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                                </a>
                                                <button type="button" id="btnEliminarImpuesto" name="btnEliminarImpuesto" onclick="xajax_eliminarImpuestoArticulo(xajax.getFormValues('frmArticulo'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                                            </td>
                                            <td>&nbsp;</td>
                                            <td>
                                            <a class="modalImg" id="aDesbloquearIva" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'iv_articulo_form_aplica_iva');">
                                                <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                            </a>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table border="0" class="texto_9px" width="100%">
                                        <tr align="center" class="tituloColumna">
                                            <td><input type="checkbox" id="cbxItmImpuesto" onclick="selecAllChecks(this.checked,this.id,'frmArticulo');"/></td>
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
                            
                            <fieldset><legend class="legend">Otros Datos</legend>
                                <table border="0" width="100%">
                                <tr align="right">
                                    <td class="tituloCampo" width="14%">Stock Máximo:</td>
                                    <td width="19%"><span id="spnStockMaximo"></span></td>
                                    <td class="tituloCampo" width="14%">Saldo:</td>
                                    <td width="20%"><span id="spnSaldo"></span></td>
                                    <td class="tituloCampo" width="14%">En Espera:</td>
                                    <td width="19%"><span id="spnCantEspera"></span></td>
                                </tr>
                                <tr align="right">
                                    <td class="tituloCampo">Stock Mínimo:</td>
                                    <td><span id="spnStockMinimo"></span></td>
                                    <td class="tituloCampo">Reservado:</td>
                                    <td><span id="spnCantReservada"></span></td>
                                    <td class="tituloCampo">Bloqueada:</td>
                                    <td><span id="spnCantBloqueada"></span></td>
                                </tr>
                                <tr align="right">
                                    <td colspan="2"></td>
                                    <td class="tituloCampo">Unid. Disponible:</td>
                                    <td><span id="spnCantDisponible"></span></td>
                                    <td class="tituloCampo">Pedido:</td>
                                    <td><span id="spnCantPedida"></span></td>
                                </tr>
                                <tr align="right">
                                    <td colspan="4"></td>
                                    <td class="tituloCampo">Futura:</td>
                                    <td><span id="spnCantFutura"></span></td>
                                </tr>
                                <tr>
                                    <td colspan="6"><hr /></td>
                                </tr>
                                <tr align="center">
                                    <td align="right" class="tituloCampo">Ult. Compra:</td>
                                    <td><span id="spnFechaUltCompraArt"></span></td>
                                    <td align="right" class="tituloCampo">Ult. Venta:</td>
                					<td><span id="spnFechaUltVentaArt"></span></td>
                                </tr>
                                <tr>
                                    <td colspan="6"><hr /></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Largo (Cm):</td>
                                    <td><input type="text" id="txtLargo" name="txtLargo" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="12" style="text-align:right"/></td>
                                    <td align="right" class="tituloCampo">Ancho (Cm):</td>
                                    <td><input type="text" id="txtAncho" name="txtAncho" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="12" style="text-align:right"/></td>
                                    <td align="right" class="tituloCampo">Alto (Cm):</td>
                                    <td><input type="text" id="txtAlto" name="txtAlto" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="12" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Peso (g):</td>
                                    <td><input type="text" id="txtPeso" name="txtPeso" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="12" style="text-align:right"/></td>
                                </tr>
                                </table>
							</fieldset>
                            </td>
                            <td valign="top" width="40%">
                            <fieldset><legend class="legend">Precios</legend>
                            	<div id="tdListaPrecio" style="width:100%"></div>
							</fieldset>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
				</table>
			</form>
            </td>
        </tr>
        <tr>
        	<td>
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#">Art. que Sustituye</a></li>
                        <li><a href="#">Art. Alternos</a></li>
                    </ul>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                    <form id="frmListaArtSust" name="frmListaArtSust" style="margin:0">
                        <input type="hidden" id="hddObjArtSust" name="hddObjArtSust"/>
                    	<table border="0" width="100%">
                        <tr>
                            <td>
                            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                	<td>
                                    <a class="modalImg" id="aInsertarArt" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaArtSustAlt', 'formArticuloSustituto');">
                                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/add.png" title="Agregar Artículo Sustituto"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
									</a>
                                    	<button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminarArticuloSustituto(xajax.getFormValues('frmListaArtSust'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/delete.png" title="Quitar Artículo Sustituto"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                                    </td>
                                </tr>
                                </table>
							</td>
                        </tr>
                        <tr>
                            <td>
                                <div style="max-height:300px; overflow:auto; width:100%;">
                                    <table border="0" width="100%">
                                    <tr align="center" class="tituloColumna">
                                        <td><input type="checkbox" id="cbxItmArtSust" onclick="selecAllChecks(this.checked,this.id,'frmListaArtSust');"/></td>
                                        <td width="20%">Código Artículo</td>
                                        <td width="80%">Descripción</td>
                                        <td></td>
                                    </tr>
                                    <tr id="trItmPieArtSust"></tr>
                                    </table>
								</div>
                            </td>
                        </tr>
                        </table>
					</form>
                    </div>
                    <div class="pane">
                    <form id="frmListaArtAlt" name="frmListaArtAlt" style="margin:0">
                        <input type="hidden" id="hddObjArtAlt" name="hddObjArtAlt"/>
                        <table border="0" width="100%">
                        <tr>
                            <td>
                            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                	<td>
                                    <a class="modalImg" id="aInsertarArtAlt" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaArtSustAlt', 'formArticuloAlterno');">
                                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/add.png" title="Agregar Artículo Alterno"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
									</a>
                                    	<button type="button" id="btnEliminarArtAlt" name="btnEliminarArtAlt" onclick="xajax_eliminarArticuloAlterno(xajax.getFormValues('frmListaArtAlt'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/delete.png" title="Quitar Artículo Alterno"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                                    </td>
                                </tr>
                                </table>
							</td>
                        </tr>
                        <tr>
                            <td>
                                <div style="max-height:300px; overflow:auto; width:100%;">
                                    <table border="0" width="100%">
                                    <tr align="center" class="tituloColumna">
                                        <td><input type="checkbox" id="cbxItmArtAlt" onclick="selecAllChecks(this.checked,this.id,'frmListaArtAlt');"/></td>
                                        <td width="20%">Código Artículo</td>
                                        <td width="80%">Descripción</td>
                                        <td></td>
                                    </tr>
                                    <tr id="trItmPieArtAlt"></tr>
                                    </table>
								</div>
                            </td>
                        </tr>
                        </table>
					</form>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
        	<td align="right"><hr />
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmArticulo();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('iv_articulo_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
                <input type="hidden" id="hddTipoVista" name="hddTipoVista" value="<?php echo $_GET['vw']; ?>" />
			</td>
        </tr>
        </table>
    </div>
	
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
	
<form id="frmPermiso" name="frmPermiso" style="margin:0px" onsubmit="return false;">
    <table border="0" id="tblPermiso" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="68%">
                	<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo"/>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr />
            <button type="submit" onclick="validarFormPermiso();">Aceptar</button>
            <button type="button" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
	
	<div id="tblListaArancelFamilia" style="max-height:520px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmBuscarArancelFamilia" name="frmBuscarArancelFamilia" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarArancelFamilia" name="txtCriterioBuscarArancelFamilia" class="inputHabilitado" onkeyup="byId('btnBuscarArancelFamilia').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarArancelFamilia" name="btnBuscarArancelFamilia" onclick="xajax_buscarArancelFamilia(xajax.getFormValues('frmBuscarArancelFamilia'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarArancelFamilia'].reset(); byId('btnBuscarArancelFamilia').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td><div id="divListaArancelFamilia" style="width:100%"></div></td>
        </tr>
        <tr>
            <td align="right"><hr />
                <button type="button" id="btnCancelarArancelFamilia" name="btnCancelarArancelFamilia" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>

	<table border="0" id="tblListaArtSustAlt" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArticulo" name="frmBuscarArticulo" onsubmit="return false;" style="margin:0">
			<input type="hidden" id="hddModoArticulo" name="hddModoArticulo"/>
            <table align="right">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="120">Código:</td>
                <td id="tdCodigoArtBuscar"></td>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarArticulo" name="txtCriterioBuscarArticulo" class="inputHabilitado"/></td>
                <td>
                	<button type="submit" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmArticulo'));">Buscar</button>
                	<button type="button" onclick="document.forms['frmBuscarArticulo'].reset(); byId('btnBuscarArticulo').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td>
        <form id="frmListaArtSustAlt" name="frmListaArtSustAlt" style="margin:0">
        	<div id="divListaArtSustAlt" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr />
            <button type="button" id="btnCancelarArtSustAlt" name="btnCancelaArtSustAlt" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
   
    <table id="tblFlotanteContenido" width="960">
    <tr>
    	<td id="tdFlotanteContenido"></td>
    </tr>
	<tr>
		<td align="right"><hr />
			 <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo" class="close">Cerrar</button>
		</td>
	</tr>
    </table>
    
	<table border="0" id="tblListaImpuesto" width="760">
    <tr>
    	<td>
        <form id="frmBuscarImpuesto" name="frmBuscarImpuesto" onsubmit="return false;" style="margin:0">
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
    	<td align="right"><hr />
            <button type="button" id="btnCancelarListaImpuesto" name="btnCancelarListaImpuesto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('imgArticulo').src = "<?php echo (strlen($_SESSION['logoEmpresaSysGts']) > 5) ? "../".$_SESSION['logoEmpresaSysGts'] : ""; ?>";

byId('btnGuardar').style.display = 'none';

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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>

<?php if ($_GET['vw'] == "v") { ?>
	<?php
	if (!(validaAcceso("iv_articulo_list"))) {
		echo "alert('Acceso Denegado'); top.history.back();";
	} ?>
	<script>xajax_cargarArticulo('<?php echo $_GET['id']; ?>', '<?php echo $_GET['ide']; ?>', '<?php echo $_GET['vw']; ?>', true);</script>
<?php } else { ?>
	<?php
	if (!(validaAcceso("iv_articulo_list","editar")) && $_GET['id'] > 0) {
		echo "alert('Acceso Denegado'); top.history.back();";
	} else if (!(validaAcceso("iv_articulo_list","insertar"))) {
		echo "alert('Acceso Denegado'); top.history.back();";
	} ?>
	<script>xajax_cargarArticulo('<?php echo $_GET['id']; ?>', '<?php echo $_GET['ide']; ?>', '<?php echo $_GET['vw']; ?>', true);</script>
<?php } ?>