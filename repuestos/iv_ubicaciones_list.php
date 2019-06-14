<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_ubicaciones_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_ubicaciones_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);
	
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
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Listado de Ubicaciones</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function validarFrmImportarArchivo() {
		if (validarCampo('hddUrlArchivo','t','') == true) {
			xajax_importarAlmacen(xajax.getFormValues('frmImportarArchivo'), xajax.getFormValues('frmListaUbicacion'));
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
        	<td class="tituloPaginaRepuestos">Listado de Ubicaciones</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_imprimirUbicacion(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                        <button type="button" onclick="xajax_exportarUbicacion(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					<a class="modalImg" id="aImportar" rel="#divFlotante" onclick="xajax_formImportarAlmacen(this.id, xajax.getFormValues('frmImportarArchivo'));">
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
                    <td align="right" class="tituloCampo">Artículos:</td>
                	<td>
                        <select multiple id="lstVerArticuloUbic" name="lstVerArticuloUbic" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">Con Una Ubicación</option>
                            <option value="2">Con Múltiple Ubicación</option>
                        </select>
					</td>
                    <td align="right" class="tituloCampo">Ubicaciones:</td>
                	<td>
                        <select multiple id="lstVerUbic" name="lstVerUbic" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">Libres</option>
                            <option value="2">Ocupadas</option>
                            <option value="3">Con Disponibilidad</option>
                            <option value="4">Sin Disponibilidad</option>
                        </select>
					</td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Estatus:</td>
                    <td>
                        <select id="lstEstatus" name="lstEstatus" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Ubicación Inactiva</option>
                            <option selected="selected" value="1">Ubicación Activa</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Ubicación:</td>
                	<td colspan="3">
                        <table>
                        <tr align="center">
                            <td class="tituloCampo">Almacen</td>
                            <td class="tituloCampo">Calle</td>
                            <td class="tituloCampo">Estante</td>
                            <td class="tituloCampo">Tramo</td>
                            <td class="tituloCampo">Casilla</td>
                        </tr>
                        <tr>
                            <td id="tdlstAlmacenBusqueda">
                                <select id="lstAlmacenBusqueda" name="lstAlmacenBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstCalleBusqueda">
                                <select id="lstCalleBusqueda" name="lstCalleBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstEstanteBusqueda">
                                <select id="lstEstanteBusqueda" name="lstEstanteBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstTramoBusqueda">
                                <select id="lstTramoBusqueda" name="lstTramoBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstCasillaBusqueda">
                                <select id="lstCasillaBusqueda" name="lstCasillaBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarUbicacion(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaUbicacion" name="frmListaUbicacion" style="margin:0">
                <div id="divListaUbicacion" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_verde.gif"/></td><td>Ubicación Activa</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_gris.gif"/></td><td>Ubicación Inactiva</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	Unid. Disponible = Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarArchivo" name="frmImportarArchivo" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarArchivo" width="960">
    <tr align="left">
        <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Archivo de Excel:</td>
        <td width="85%">
            <input type="file" id="fleUrlArchivo" name="fleUrlArchivo" class="inputHabilitado" onchange="javascript:submit();" size="100"/>
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
                                    <td width="35%">Código</td>
                                    <td width="35%">Almacén</td>
                                    <td width="25%">Ubicación</td>
                                </tr>
                				<tr id="trItmPie"></tr>
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

<script>
byId('lstVerArticuloUbic').className = "inputHabilitado";
byId('lstVerUbic').className = "inputHabilitado";
byId('lstEstatus').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>',"onchange=\"xajax_objetoCodigoDinamico('tdCodigoArt',this.value); xajax_cargaLstBusqueda('almacenes', 'lstPadre', 'Busqueda', this.value, 'null', 'null'); byId('btnBuscar').click();\"");
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstBusqueda('almacenes', 'lstPadre', 'Busqueda', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'null', 'null');
xajax_listaUbicacion(0, 'CONCAT(descripcion_almacen, ubicacion, IFNULL(query.id_articulo_costo, 0))', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|||' + byId('lstEstatus').value);

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>