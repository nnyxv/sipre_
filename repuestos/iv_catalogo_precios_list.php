<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_catalogo_precios_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_catalogo_precios_list.php");
	
/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Catálogo de Precios</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2, valor3, valor4) {
		byId('tblArticuloPrecio').style.display = 'none';
		byId('tblImportarArchivo').style.display = 'none';
		
		if (verTabla == "tblArticuloPrecio") {
			document.forms['frmArticuloPrecio'].reset();
			byId('hddIdArticuloPrecio').value = '';
			
			byId('txtPrecioArt').className = 'inputHabilitado';
			
			xajax_formArticuloPrecio(valor, valor2, valor3, valor4);
			
			tituloDiv1 = 'Editar Precio';
		} else if (verTabla == "tblImportarArchivo") {
			document.forms['frmImportarArchivo'].reset();
			byId('hddUrlArchivo').value = '';
			
			byId('txtIdEmpresaImportarArchivo').className = 'inputHabilitado';
			byId('fleUrlArchivo').className = 'inputHabilitado';
			
			xajax_formImportarPrecio(xajax.getFormValues('frmImportarArchivo'));
			
			tituloDiv1 = 'Importar Artículo';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblArticuloPrecio") {
			byId('txtPrecioArt').focus();
			byId('txtPrecioArt').select();
		} else if (verTabla == "tblImportarArchivo") {
			byId('fleUrlArchivo').focus();
			byId('fleUrlArchivo').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaEmpresa').style.display = 'none';
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		}
	}
	
	function validarFrmArticuloPrecio() {
		if (validarCampo('txtPrecioArt','t','monto') == true) {
			xajax_guardarArticuloPrecio(xajax.getFormValues('frmArticuloPrecio'), xajax.getFormValues('frmBuscar'), xajax.getFormValues('frmListaArticulos'));
		} else {
			validarCampo('txtPrecioArt','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmImportarArchivo() {
		if (validarCampo('hddUrlArchivo','t','') == true) {
			xajax_importarPrecio(xajax.getFormValues('frmImportarArchivo'), xajax.getFormValues('frmListaArticulos'));
		} else {
			validarCampo('hddUrlArchivo','t','');
			
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
            <td class="tituloPaginaRepuestos">Catálogo de Precios</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_exportarCatalogo(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                    <a class="modalImg" id="aImportar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImportarArchivo');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
					</td>
                </tr>
                </table>
       			
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Aplica Impuesto:</td>
                    <td>
                    	<select id="lstAplicaIva" name="lstAplicaIva" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="0">No</option>
                        	<option value="1">Si</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Tipo de Artículo:</td>
                    <td id="tdlstTipoArticulo"></td>
                    <td align="right" class="tituloCampo">Clasificación:</td>
                    <td>
                        <select id="lstVerClasificacion" name="lstVerClasificacion" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Ver:</td>
                    <td colspan="3">
                        <select multiple id="lstSaldos" name="lstSaldos" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">Disponible</option>
                            <option value="2">No Disponible</option>
                        </select>
					</td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaArticulos" name="frmListaArticulos" style="margin:0">
            	<div id="divListaArticulos" style="width:100%"></div>
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
							<td><img src="../img/iconos/accept.png"/></td><td>Si Aplica Impuesto</td>
                        </tr>
                        </table>
                        
                        <table>
                        <tr>
							<td>Unid. Disponible = Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada</td>
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
	
<form id="frmArticuloPrecio" name="frmArticuloPrecio" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblArticuloPrecio" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
                <td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span><?php echo $spanPrecioUnitario; ?>:</td>
            	<td width="82%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                    	<td><input type="text" id="txtDescripcion" name="txtDescripcion" readonly="readonly" size="30"/></td>
                        <td><input type="text" id="txtPrecioArt" name="txtPrecioArt" maxlength="17" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" size="10" style="text-align:right"/></td>
                        <td id="tdlstMoneda"></td>
					</tr>
                    </table>
				</td>
            </tr>
            </table>
        </td>
	</tr>
    <tr>
    	<td align="right"><hr>
        	<input type="hidden" id="hddIdArticuloPrecio" name="hddIdArticuloPrecio"/>
        	<input type="hidden" id="hddIdArticulo" name="hddIdArticulo"/>
        	<input type="hidden" id="hddIdArticuloCosto" name="hddIdArticuloCosto" title="Lote"/>
        	<input type="hidden" id="hddIdPrecio" name="hddIdPrecio"/>
            <button type="submit" id="btnGuardarArticuloPrecio" name="btnGuardarArticuloPrecio" onclick="validarFrmArticuloPrecio();">Aceptar</button>
            <button type="button" id="btnCancelarArticuloPrecio" name="btnCancelarArticuloPrecio" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
</form>

<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarArchivo" name="frmImportarArchivo" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarArchivo" width="960">
    <tr align="left">
        <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empresa:</td>
        <td width="85%">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="txtIdEmpresaImportarArchivo" name="txtIdEmpresaImportarArchivo" onblur="xajax_asignarEmpresaUsuario(this.value, 'EmpresaImportarArchivo', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                <td>
                <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                    <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                </a>
                </td>
                <td><input type="text" id="txtEmpresaImportarArchivo" name="txtEmpresaImportarArchivo" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr align="left">
    	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Archivo de Excel:</td>
        <td>
        	<input type="file" id="fleUrlArchivo" name="fleUrlArchivo" class="inputHabilitado" onchange="javascript: submit();"/>
			<iframe name="iframeUpload" style="display:none"></iframe>
            <input type="hidden" id="hddUrlArchivo" name="hddUrlArchivo" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table width="100%">
                    <tr>
                        <td>El Formato del Archivo Excel a Importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr>
                    	<td>
                            <div style="max-height:300px; overflow:auto; width:100%;">
                                <table width="100%">
                                <tr align="center" class="tituloColumna">
                                	<td width="5%"></td>
                                    <td width="30%">A</td>
                                    <td width="40%">B</td>
                                    <td width="25%">C</td>
                                </tr>
                                <tr align="center">
                                	<td class="tituloCampo"></td>
                                    <td class="tituloCampo">Código</td>
                                    <td class="tituloCampo">Id Precio</td>
                                    <td class="tituloCampo">Precio</td>
                                </tr>
                				<tr id="trItmPieArchivo"></tr>
                                </table>
							</div>
						</td>
					</tr>
                    </table>
                </td>
            </tr>
            </table>
            <div id="divMsjImportar"></div>
        </td>
    </tr>
    <tr>
    	<td align="right" colspan="2"><hr>
        	<button type="submit" id="btnGuardarImportarArchivo" name="btnGuardarImportarArchivo" onclick="validarFrmImportarArchivo();">Aceptar</button>
        	<button type="button" id="btnCancelarImportarArchivo" name="btnCancelarImportarArchivo" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana"/>
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpresa" name="frmListaEmpresa" onsubmit="return false;" style="margin:0">
            <div id="divListaEmpresa" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('lstAplicaIva').className = 'inputHabilitado';
byId('lstVerClasificacion').className = 'inputHabilitado';
byId('lstSaldos').className = 'inputHabilitado';
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstTipoArticulo();
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaCatalogoPrecio(0,'art.codigo_articulo','ASC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>